<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Types\ConversionException;
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
use EnjoysCMS\Core\Block\Options;
use EnjoysCMS\Core\Block\UserBlock;
use EnjoysCMS\Core\AccessControl\ACL;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
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
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RendererInterface $renderer,
        private readonly ContentEditor $contentEditor,
        private readonly RedirectInterface $redirect,
        private readonly ACL $ACL,
        private readonly Config $config
    ) {
        $this->blockRepository = $this->em->getRepository(Block::class);
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
            $this->redirect->toRoute('admin/blocks', emit: true);
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
                    try {
                        /** @var string $id */
                        $id = $this->request->getParsedBody()['id'] ?? '';

                        $block = $this->blockRepository->find($id);

                        if ($block === null) {
                            return true;
                        }

                        return false;
                    } catch (ConversionException) {
                        return true;
                    }
                }
            );
        $form->text('name', 'Название')->addRule(Rules::REQUIRED);
        $form->textarea('body', 'Контент')->addRule(Rules::REQUIRED);

        $form->checkbox('groups', 'Группа')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->em->getRepository(Group::class)->getGroupsArray()
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
        $block->setOptions(Options::createFromArray(UserBlock::META['options']));

        $this->em->beginTransaction();
        $this->em->persist($block);

        $this->em->flush();

        $acl = $this->ACL->addACL(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );
        foreach ($groups as $group) {
            $acl->setGroups($group);
        }
        $this->em->persist($acl);
        $this->em->flush();
        $this->em->commit();
    }
}
