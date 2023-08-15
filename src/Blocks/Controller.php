<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Blocks;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Block\BlockCollection;
use EnjoysCMS\Core\Block\BlockFactory;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\Admin\Config;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/blocks', '@admin_blocks_')]
class Controller extends AdminController
{

    /**
     * @throws NotSupported
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        name: 'manage',
        comment: 'Просмотр активных блоков'
    )]
    public function manage(EntityManager $em): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Менеджер блоков');
        return $this->response(
            $this->twig->render(
                '@a/blocks/manage.twig',
                [
                    'blocks' => $em->getRepository(Block::class)->findAll(),
                ]
            )
        );
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/activate',
        name: 'activate',
        comment: 'Установка (активация) блоков'
    )]
    public function activate(
        EntityManager $em,
        BlockCollection $blockCollection,
        AccessControl $accessControl
    ): ResponseInterface {
        /** @var class-string $class */
        $class = $this->request->getQueryParams()['class'] ?? '';

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $reflectionClass = new ReflectionClass($class);

        $id = Uuid::uuid4()->toString();

        $blockAnnotation = $blockCollection->getAnnotation(
            $reflectionClass
        ) ?? throw new InvalidArgumentException(
            sprintf('Class "%s" not supported', $reflectionClass->getName())
        );

        $block = new Block();
        $block->setId($id);
        $block->setName($blockAnnotation->getName());
        $block->setClassName($blockAnnotation->getClassName());
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($blockAnnotation->getOptions());
        $em->persist($block);
        $em->flush();


        $accessControl->getManage()->register($block->getId());

        return $this->redirect->toRoute('@admin_blocks_manage', ['id' => $id]);
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotSupported
     * @throws NoResultException
     */
    #[Route('/delete/{id}',
        name: 'delete',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        comment: 'Удаление блоков'
    )]
    public function delete(EntityManager $em, BlockFactory $blockFactory): ResponseInterface
    {
        /** @var Block $block */
        $block = $em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();


        if (!$block->isRemovable()) {
            throw new RuntimeException('Block is not removable');
        }

        try {
            $blockFactory->create($block->getClassName())->setEntity($block)->preRemove();
        } catch (DependencyException|NotFoundException) {
        }

        $em->remove($block);
        $em->flush();

        return $this->redirect->toRoute('@admin_blocks_manage');
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws OptimisticLockException
     * @throws NotFoundException
     * @throws NotSupported
     * @throws NoResultException
     */
    #[Route('/clone/{id}',
        name: 'clone',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        comment: 'Клонирование блоков'
    )]
    public function clone(
        EntityManager $em,
        BlockFactory $blockFactory,
        AccessControl $accessControl
    ): ResponseInterface {
        $block = $em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        );

        if ($block === null) {
            throw new NoResultException();
        }

        $cloned = clone $block;
        $cloned->setId(Uuid::uuid4()->toString());
        $cloned->setRemovable(true);
        $cloned->setCloned(true);
        $em->persist($cloned);
        $em->flush();

        $accessControl->getManage()->register($cloned->getId());

        $blockFactory->create($block->getClassName())->setEntity($block)->postClone();

        return $this->redirect->toRoute('@admin_blocks_manage');
    }

    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/edit/{id}',
        name: 'edit',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        comment: 'Редактирование блоков'
    )]
    public function edit(Edit $editBlock, Config $config, ContentEditor $contentEditor): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb(sprintf('Редактирование блока "%s"', $editBlock->getBlock()->getName()));

        $form = $editBlock->getForm();
        if ($form->isSubmitted()) {
            $editBlock->doAction();
            return $this->redirect->toRoute('@admin_blocks_manage');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/blocks/edit.twig',
                [
                    'form' => $rendererForm,
                    'block' => $editBlock->getBlock(),
                    'contentEditor' => $contentEditor->withConfig(
                        $config->getContentEditorConfigParamForCustomBlocks()
                    )->setSelector('#body')->getEmbedCode(),
                ]
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/add',
        name: 'add',
        comment: 'Добавление пользовательского блока (простой текстовый блок)'
    )]
    public function add(Add $addBlocks, Config $config, ContentEditor $contentEditor): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb('Добавление блока (пользовательский)');

        $form = $addBlocks->getForm();
        if ($form->isSubmitted()) {
            $addBlocks->doAction();
            return $this->redirect->toRoute('@admin_blocks_manage');
        }
        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/blocks/add.twig',
                [
                    'contentEditor' => $contentEditor->withConfig(
                        $config->getContentEditorConfigParamForCustomBlocks()
                    )->setSelector('#body')->getEmbedCode(),
                    'form' => $rendererForm,
                ]
            )
        );
    }

    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/locations/{id}',
        name: 'location',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        comment: 'Установка расположения блоков'
    )]
    public function location(Locations $blockLocations, Config $config): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->add(['@admin_blocks_edit', ['id' => $blockLocations->getBlock()->getId()]], 'Редактирование блока')
            ->setLastBreadcrumb(sprintf('Настройка расположения блока "%s"', $blockLocations->getBlock()->getName()));

        $form = $blockLocations->getForm();

        if ($form->isSubmitted()) {
            $blockLocations->doAction();
            return $this->redirect->toRoute('@admin_blocks_manage');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(

            $this->twig->render(
                '@a/blocks/locations.twig',
                [
                    'form' => $rendererForm,
                    'block' => $blockLocations->getBlock(),
                ]
            )
        );
    }


    /**
     * @throws Exception
     */
    #[Route('/setup',
        name: 'setup',
        comment: 'Просмотре не активированных блоков'
    )]
    public function setUp(BlockCollection $blockCollection): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb('Активация новых блоков');
        return $this->response(
            $this->twig->render(
                '@a/blocks/setup.twig',
                [
                    'blocks' => $blockCollection
                ]
            )
        );
    }


}
