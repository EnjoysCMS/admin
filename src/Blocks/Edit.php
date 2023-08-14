<?php


namespace EnjoysCMS\Module\Admin\Blocks;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Block\BlockFactory;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Block\UserBlock;
use EnjoysCMS\Core\Users\Entity\Group;
use InvalidArgumentException;
use Invoker\Exception\NotCallableException;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class Edit
{

    private Block $block;

    private EntityRepository $groupsRepository;
    private \EnjoysCMS\Core\Block\Repository\Block|EntityRepository $blockRepository;


    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Container $container,
        private readonly BlockFactory $blockFactory,
        private readonly AccessControl $accessControl,
    ) {
        $this->blockRepository = $this->em->getRepository(Block::class);
        $blockId = $this->request->getAttribute('id', '');
        $this->block = $this->blockRepository->find($blockId) ?? throw new InvalidArgumentException(
            sprintf('Invalid block ID: %s', $blockId)
        );

        $this->groupsRepository = $this->em->getRepository(Group::class);
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'name' => $this->block->getName(),
                'id' => $this->block->getId(),
                'alias' => $this->block->getAlias(),
                'body' => $this->block->getBody(),
                'options' => $this->block->getOptionsKeyValue(),
                'groups' => array_map(
                    function (Group $item) {
                        return $item->getId();
                    },
                    $this->accessControl->getAuthorizedGroups($this->block->getId())
                ),
            ]
        );

        $form->text('id', 'Id')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'UUID is wrong',
                function () {
                    return Uuid::isValid($this->request->getParsedBody()['id'] ?? '');
                }
            )
            ->addRule(
                Rules::CALLBACK,
                'Такой идентификатор уже существует',
                function () {
                    try {
                        $id = $this->request->getParsedBody()['id'] ?? null;

                        if ($id === null) {
                            return true;
                        }

                        $block = $this->blockRepository->find($id);

                        if ($block === null) {
                            return true;
                        }

                        if ($block->getId() === $this->block->getId()) {
                            return true;
                        }
                        return false;
                    } catch (ConversionException) {
                        return true;
                    }
                }
            );

        $form->text('alias', 'Alias')
            ->setDescription('Псевдоним идентификатора')
            ->addRule(
                Rules::CALLBACK,
                'Числа нельзя использовать в качестве псевдонима',
                function () {
                    $alias = $this->request->getParsedBody()['alias'] ?? null;
                    if ($alias === null) {
                        return true;
                    }
                    return !is_numeric($alias);
                }
            )
            ->addRule(
                Rules::CALLBACK,
                'Такой alias или идентификатор уже существует',
                function () {
                    $alias = $this->request->getParsedBody()['alias'] ?? null;
                    if ($alias === null) {
                        return true;
                    }

                    $block = $this->blockRepository->find($alias);

                    if ($block === null) {
                        return true;
                    }

                    if ($block->getId() === $this->block->getId()) {
                        return true;
                    }
                    return false;
                }
            );

        $form->text('name', 'Название');


        if ($this->block->getClassName() === UserBlock::class) {
            $form->textarea('body', 'Контент');
        }


        foreach ($this->block->getOptions() as $key => $option) {
            $type = $option['form']['type'] ?? null;

            if ($type) {
                $data = $option['form']['data'] ?? [null];
                try {
                    if (is_array($data) && !array_key_exists(0, $data)) {
                        throw new NotCallableException();
                    }
                    $data = $this->container->call($data);
                } catch (NotCallableException) {
                    //skip
                }
                switch ($type) {
                    case 'radio':
                        $form->radio(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data);
                        break;
                    case 'checkbox':
                        $form->checkbox(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data);
                        break;
                    case 'select':
                        $form->select(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription(
                            $option['description'] ?? ''
                        )->fill($data);
                        break;
                    case 'textarea':
                        $form->textarea(
                            "options[$key]",
                            (isset($option['name'])) ? $option['name'] : $key
                        )->setDescription($option['description'] ?? '');
                        break;
                    case 'file':
                        $form->file("options[$key]", $option['name'] ?? $key)
                            ->setDescription($option['description'] ?? '')
                            ->setMaxFileSize(
                                $data['max_file_size'] ?? iniSize2bytes(ini_get('upload_max_filesize'))
                            )
                            ->setAttributes(AttributeFactory::createFromArray($data['attributes'] ?? []));
                        break;
                }

                continue;
            }
            $form->text("options[$key]", (isset($option['name'])) ? $option['name'] : $key)->setDescription(
                $option['description'] ?? ''
            );
        }


        $form->checkbox('groups', 'Права доступа')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->groupsRepository->getGroupsArray()
            );

        $form->submit('send');

        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws NotFoundException
     * @throws ORMException
     * @throws DependencyException
     */
    public function doAction(): void
    {
        $oldBlock = clone $this->block;


        $this->block->setName($this->request->getParsedBody()['name'] ?? null);
        $this->block->setId($this->request->getParsedBody()['id'] ?? null);
        $this->block->setAlias($this->request->getParsedBody()['alias'] ?? null);
        $this->block->setBody($this->request->getParsedBody()['body'] ?? null);

        $options = $this->block->getOptions();
        $options->setValues($this->request->getParsedBody()['options'] ?? []);

        $this->block->setOptions($options);


        /**
         * @var Group $group
         */

        $accessAction = $this->accessControl->getManage()->getAccessAction($this->block->getId());
        $accessAction->setGroups(
            $this->groupsRepository->findBy([
                'id' => $this->request->getParsedBody()['groups'] ?? []
            ])
        );


        $this->blockFactory->create($this->block->getClassName())->setEntity($this->block)->postEdit($oldBlock);


        $this->em->flush();
    }


    public function getBlock(): Block
    {
        return $this->block;
    }
}
