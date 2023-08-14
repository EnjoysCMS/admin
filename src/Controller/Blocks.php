<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Controller;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\Admin\Core\Blocks\ActivateBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\AddBlocks;
use EnjoysCMS\Module\Admin\Core\Blocks\BlockLocations;
use EnjoysCMS\Module\Admin\Core\Blocks\CloneBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\DeleteBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\EditBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\ManageBlocks;
use EnjoysCMS\Module\Admin\Core\Blocks\SetupBlocks;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/blocks', '@admin_blocks_')]
class Blocks extends AdminController
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
    public function manage(ManageBlocks $manageBlocks): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Менеджер блоков');
        return $this->response(
            $this->twig->render(
                '@a/blocks/manage.twig',
                $manageBlocks->getContext()
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
    public function activate(ActivateBlock $activateBlock): ResponseInterface
    {
        return $activateBlock();
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
    public function delete(DeleteBlock $deleteBlock): ResponseInterface
    {
        return $deleteBlock();
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
    public function clone(CloneBlock $cloneBlock): ResponseInterface
    {
        return $cloneBlock();
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
    public function edit(EditBlock $editBlock): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb(sprintf('Редактирование блока "%s"', $editBlock->getBlock()->getName()));
        return $this->response(
            $this->twig->render(
                '@a/blocks/edit.twig',
                $editBlock->getContext()
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
    public function add(AddBlocks $addBlocks): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb('Добавление блока (пользовательский)');
        return $this->response(
            $this->twig->render(
                '@a/blocks/add.twig',
                $addBlocks->getContext()
            )
        );
    }

    /**
     * @throws LoaderError
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
    public function location(BlockLocations $blockLocations): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->add(['@admin_blocks_edit', ['id' => $blockLocations->getBlock()->getId()]], 'Редактирование блока')
            ->setLastBreadcrumb(sprintf('Настройка расположения блока "%s"', $blockLocations->getBlock()->getName()));

        return $this->response(
            $this->twig->render(
                '@a/blocks/locations.twig',
                $blockLocations->getContext()
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
    public function setUp(SetupBlocks $setupBlocks): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_blocks_manage', 'Менеджер блоков')
            ->setLastBreadcrumb('Активация новых блоков');
        return $this->response(
            $this->twig->render(
                '@a/blocks/setup.twig',
                $setupBlocks->getContext()
            )
        );
    }


}
