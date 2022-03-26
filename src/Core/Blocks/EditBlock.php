<?php


namespace App\Module\Admin\Core\Blocks;


use App\Module\Admin\Core\ModelInterface;
use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Blocks\Custom;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Core\Entities\Block;
use EnjoysCMS\Core\Entities\Group;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EditBlock implements ModelInterface
{

    private Block $block;

    private ?\EnjoysCMS\Core\Entities\ACL $acl;
    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $groupsRepository;


    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private ContainerInterface $container
    ) {
        if (null === $block = $entityManager->getRepository(Block::class)->find($this->requestWrapper->getQueryData('id'))) {
            throw new \InvalidArgumentException('Invalid block ID');
        }
        if (!($block instanceof Block)) {
            throw new \InvalidArgumentException('Invalid block');
        }
        $this->block = $block;
        $this->acl = ACL::getAcl($this->block->getBlockActionAcl());
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);
    }

    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        $wysiwyg = WYSIWYG::getInstance('summernote', $this->container);

        return [
            'form' => $this->renderer,
            'block' => $this->block,
            'wysiwyg' => $wysiwyg->selector('#body')
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->setDefaults(
            [
                'name' => $this->block->getName(),
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

        $form->text('alias', 'Alias')
            ->setDescription('Псевдоним идентификатора')
            ->addRule(
                Rules::CALLBACK,
                'Числа нельзя использовать в качестве псевдонима',
                function () {
                    $alias = $this->requestWrapper->getPostData('alias');
                    if ($alias === null) {
                        return true;
                    }
                    return !is_numeric($alias);
                }
            )
            ->addRule(
                Rules::CALLBACK,
                'Такой идентификатор уже существует',
                function () {
                    $alias = $this->requestWrapper->getPostData('alias');
                    if ($alias === null) {
                        return true;
                    }

                    $qb = $this->entityManager->createQueryBuilder();
                    $qb->select('b')
                        ->from(Block::class, 'b')
                        ->where('b.alias = :alias')
                        ->setParameter('alias', $alias)
                    ;
                    $block = $qb->getQuery()->getOneOrNullResult();

                    if ($block === null) {
                        return true;
                    }

                    if ($block->getId() === $this->block->getId()) {
                        return true;
                    }
                    return false;
                }
            )
        ;

        $form->text('name', 'Название');


        if ($this->block->getClass() === Custom::class) {
            $form->textarea('body', 'Контент');
        }


        if (null !== $this->block->getOptions()) {
            foreach ($this->block->getOptions() as $key => $option) {
                if (isset($option['form']['type'])) {
                    switch ($option['form']['type']) {
                        case 'radio':
                            $form->radio(
                                "options[{$key}]",
                                (isset($option['name'])) ? $option['name'] : $key
                            )->setDescription(
                                $option['description'] ?? ''
                            )->fill($option['form']['data']);
                            break;
                        case 'checkbox':
                            $form->checkbox(
                                "options[{$key}]",
                                (isset($option['name'])) ? $option['name'] : $key
                            )->setDescription(
                                $option['description'] ?? ''
                            )->fill($option['form']['data']);
                            break;
                        case 'select':
                            $form->select(
                                "options[{$key}]",
                                (isset($option['name'])) ? $option['name'] : $key
                            )->setDescription(
                                $option['description'] ?? ''
                            )->fill($option['form']['data']);
                            break;
                        case 'textarea':
                            $form->textarea(
                                "options[{$key}]",
                                (isset($option['name'])) ? $option['name'] : $key
                            )->setDescription($option['description'] ?? '');
                            break;
                    }

                    continue;
                }
                $form->text("options[{$key}]", (isset($option['name'])) ? $option['name'] : $key)->setDescription(
                    $option['description'] ?? ''
                );
            }
        }

        $form->checkbox('groups', 'Права доступа')->fill(
            $this->groupsRepository->getGroupsArray()
        )->addRule(Rules::REQUIRED);

        $form->submit('send');

        return $form;
    }

    private function getBlockOptions(array $options): ?array
    {
        if (empty($options)) {
            return null;
        }

        $blockOptions = $this->block->getOptions();

        foreach ($blockOptions as $key => $value) {
            if (array_key_exists($key, $options)) {
                $blockOptions[$key]['value'] = $options[$key];
            } else {
                $blockOptions[$key]['value'] = null;
            }
        }

        return $blockOptions;
    }

    private function doAction(): void
    {
        $oldBlock = clone $this->block;
        $this->block->setName($this->requestWrapper->getPostData('name'));
        $this->block->setAlias(
            empty($this->requestWrapper->getPostData('alias')) ? null : $this->requestWrapper->getPostData('alias')
        );
        $this->block->setBody($this->requestWrapper->getPostData('body'));
        $this->block->setOptions($this->getBlockOptions($this->requestWrapper->getPostData('options', [])));


        /**
         *
         *
         * @var Group $group
         */
        foreach ($this->groupsRepository->findAll() as $group) {
            if (in_array($group->getId(), $this->requestWrapper->getPostData('groups', []))) {
                $this->acl->setGroups($group);
                continue;
            }
            $this->acl->removeGroups($group);
        }

        $this->entityManager->flush();

        $this->container
            ->get(FactoryInterface::class)
            ->make($this->block->getClass(), ['block' => $this->block])
            ->postEdit($oldBlock)
        ;

        Redirect::http($this->urlGenerator->generate('admin/blocks'));
        //        Redirect::http();
    }
}
