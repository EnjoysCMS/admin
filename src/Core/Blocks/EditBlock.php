<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use DI\Container;
use DI\DependencyException;
use DI\FactoryInterface;
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
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Block\UserBlock;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Config;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use InvalidArgumentException;
use Invoker\Exception\NotCallableException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EditBlock implements ModelInterface
{

    private Block $block;

    private ?\EnjoysCMS\Core\Entities\ACL $acl;
    private EntityRepository $groupsRepository;
    private \EnjoysCMS\Core\Block\Repository\Block|EntityRepository $blockRepository;


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws NotSupported
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private ContentEditor $contentEditor,
        private Container $container,
        private Config $config,
        private RedirectInterface $redirect,
    ) {
        $this->blockRepository = $this->em->getRepository(Block::class);
        $blockId = $this->request->getAttribute('id');
        $this->block = $this->blockRepository->find($blockId) ?? throw new InvalidArgumentException(
            sprintf('Invalid block ID: %s', $blockId)
        );
        $this->acl = ACL::getAcl($this->block->getBlockActionAcl());
        $this->groupsRepository = $this->em->getRepository(Group::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('admin/blocks', emit: true);
        }
        $this->renderer->setForm($form);


        return [
            'form' => $this->renderer,
            'block' => $this->block,
            'contentEditor' => $this->contentEditor->withConfig(
                $this->config->getContentEditorConfigParamForCustomBlocks()
            )->setSelector('#body')->getEmbedCode(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                sprintf('Редактирование блока `%s`', $this->block->getName())
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
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
                    function ($item) {
                        return $item->getId();
                    },
                    $this->acl->getGroups()->toArray()
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


        foreach ($this->block->getOptions()->all() as $key => $option) {
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

    private function getBlockOptions(array $options): array
    {
        if (empty($options)) {
            return [];
        }

        $blockOptions = $this->block->getOptions()->all();

        foreach ($blockOptions as $key => $value) {
            if (array_key_exists($key, $options)) {
                $blockOptions[$key]['value'] = $options[$key];
            } else {
                $blockOptions[$key]['value'] = null;
            }
        }

        return $blockOptions;
    }

    /**
     * @throws OptimisticLockException
     * @throws NotFoundException
     * @throws ORMException
     * @throws DependencyException
     */
    private function doAction(): void
    {
        $oldBlock = clone $this->block;
        $this->block->setName($this->request->getParsedBody()['name'] ?? null);
        $this->block->setId($this->request->getParsedBody()['id'] ?? null);
        $this->block->setAlias($this->request->getParsedBody()['alias'] ?? null);
        $this->block->setBody($this->request->getParsedBody()['body'] ?? null);
        $this->block->setOptions($this->getBlockOptions($this->request->getParsedBody()['options'] ?? []));


        /**
         *
         *
         * @var Group $group
         */
        foreach ($this->groupsRepository->findAll() as $group) {
            if (in_array($group->getId(), $this->request->getParsedBody()['groups'] ?? [])) {
                $this->acl->setGroups($group);
                continue;
            }
            $this->acl->removeGroups($group);
        }

        $this->container
            ->get(FactoryInterface::class)
            ->make($this->block->getClassName(), ['block' => $this->block])
            ->postEdit($oldBlock);

        $this->em->flush();
    }
}
