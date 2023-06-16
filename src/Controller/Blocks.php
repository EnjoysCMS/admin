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
use EnjoysCMS\Module\Admin\AdminBaseController;
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
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Blocks extends AdminBaseController
{


    /**
     * @throws NotSupported
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/admin/blocks/setting',
        name: 'admin/blocks',
        options: [
            'comment' => 'Просмотр активных блоков'
        ]
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
    #[Route(
        path: '/admin/blocks/activate',
        name: 'admin/acivateblocks',
        options: [
            'comment' => 'Установка (активация) блоков'
        ]
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
    #[Route(
        path: '/admin/blocks/delete/{id}',
        name: 'admin/deleteblocks',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        options: [
            'comment' => 'Удаление блоков'
        ]
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
    #[Route(
        path: '/admin/blocks/clone/{id}',
        name: 'admin/cloneblocks',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        options: [
            'comment' => 'Клонирование блоков'
        ]
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
    #[Route(
        path: '/admin/blocks/edit/{id}',
        name: 'admin/editblock',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        options: [
            'comment' => 'Редактирование блоков'
        ]
    )]
    public function edit(EditBlock $editBlock): ResponseInterface
    {
        $this->breadcrumbs->add('admin/blocks', 'Менеджер блоков')
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
    #[Route(
        path: '/admin/blocks/add',
        name: 'admin/addblock',
        options: [
            'comment' => 'Добавление пользовательского блока (простой текстовый блок)'
        ]
    )]
    public function add(AddBlocks $addBlocks): ResponseInterface
    {
        $this->breadcrumbs->add('admin/blocks', 'Менеджер блоков')
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
    #[Route(
        path: '/admin/blocks/locations/{id}',
        name: 'admin/blocklocation',
        requirements: [
            'id' => self::UUID_RULE_REQUIREMENT
        ],
        options: [
            'comment' => 'Установка расположения блоков'
        ]
    )]
    public function location(BlockLocations $blockLocations): ResponseInterface
    {
        $this->breadcrumbs->add('admin/blocks', 'Менеджер блоков')
            ->add(['admin/editblock', ['id' => $blockLocations->getBlock()->getId()]], 'Редактирование блока')
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
    #[Route(
        path: '/admin/blocks/setup',
        name: 'admin/setupblocks',
        options: [
            'comment' => 'Просмотре не активированных блоков'
        ]
    )]
    public function setUp(SetupBlocks $setupBlocks): ResponseInterface
    {
        $this->breadcrumbs->add('admin/blocks', 'Менеджер блоков')
            ->setLastBreadcrumb('Активация новых блоков');
        return $this->response(
            $this->twig->render(
                '@a/blocks/setup.twig',
                $setupBlocks->getContext()
            )
        );
    }


}
