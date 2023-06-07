<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Block\UserBlock;
use EnjoysCMS\Core\Components\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Module\Admin\Config;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddBlocks implements ModelInterface
{

    private \EnjoysCMS\Core\Block\Repository\Block|EntityRepository $blockRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private ContentEditor $contentEditor,
        private Config $config
    ) {
        $this->blockRepository = $this->entityManager->getRepository(Block::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        return [
            'contentEditor' => $this->contentEditor->withConfig(
                $this->config->getContentEditorConfigParamForCustomBlocks()
            )->setSelector('#body')->getEmbedCode(),
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                'Добавление блока (пользовательский)'
            ],
        ];
    }


    /**
     * @throws ExceptionRule
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'id' => Uuid::uuid7()->toString()
        ]);
        $form->text('id', 'ID')
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
                    /** @var string $id */
                    $id = $this->request->getParsedBody()['id'] ?? '';

                    if (!Uuid::isValid($id)){
                        return true;
                    }

                    $block = $this->blockRepository->find($id);

                    if ($block === null) {
                        return true;
                    }

                    return false;
                }
            );
        $form->text('name', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED);

        $form->checkbox('groups', 'Группа')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->entityManager->getRepository(Group::class)->getGroupsArray()
            );

        $form->submit('addblock', 'Добавить блок');
        return $form;
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws OptimisticLockException
     * @throws NotSupported
     */
    private function doAction(): void
    {
        $block = new Block();
        $block->setName($this->request->getParsedBody()['name'] ?? null);
        $block->setId($this->request->getParsedBody()['id'] ?? '');
        $block->setClassName(UserBlock::class);
        $block->setBody($this->request->getParsedBody()['body'] ?? null);
        $block->setRemovable(true);
        $block->setOptions(UserBlock::META['options']);

        $this->entityManager->beginTransaction();
        $this->entityManager->persist($block);
        $this->entityManager->flush();

        /**
         * @var ACL $acl
         */
        $acl = \EnjoysCMS\Core\Components\Helpers\ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        $groups = $this->entityManager->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );
        foreach ($groups as $group) {
            $acl->setGroups($group);
        }
        $this->entityManager->persist($acl);
        $this->entityManager->flush();
        $this->entityManager->commit();

        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }
}
