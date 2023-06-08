<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Controller;


use DI\FactoryInterface;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Blocks\ActivateBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\AddBlocks;
use EnjoysCMS\Module\Admin\Core\Blocks\BlockLocations;
use EnjoysCMS\Module\Admin\Core\Blocks\CloneBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\DeleteBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\EditBlock;
use EnjoysCMS\Module\Admin\Core\Blocks\ManageBlocks;
use EnjoysCMS\Module\Admin\Core\Blocks\SetupBlocks;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Blocks extends AdminBaseController
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/blocks/setting',
        name: 'admin/blocks',
        options: [
            'comment' => 'Просмотр активных блоков'
        ]
    )]
    public function manage(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/blocks/manage.twig',
                $this->getContext($this->getContainer()->get(ManageBlocks::class))
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/blocks/activate',
        name: 'admin/acivateblocks',
        options: [
            'comment' => 'Установка (активация) блоков'
        ]
    )]
    public function activate(): ResponseInterface
    {
        return $this->getContainer()->call(ActivateBlock::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
    public function delete(): ResponseInterface
    {
        return $this->getContainer()->get(DeleteBlock::class)($this->getContainer());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
    public function clone(): ResponseInterface
    {
       return $this->getContainer()->get(CloneBlock::class)($this->getContainer());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/blocks/edit.twig',
                $this->getContext($this->getContainer()->get(FactoryInterface::class)->make(EditBlock::class))
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/blocks/add',
        name: 'admin/addblock',
        options: [
            'comment' => 'Добавление пользовательского блока (простой текстовый блок)'
        ]
    )]
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/blocks/add.twig',
                $this->getContext($this->getContainer()->get(AddBlocks::class))
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
    public function location(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/blocks/locations.twig',
                $this->getContext($this->getContainer()->get(BlockLocations::class))
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: '/admin/blocks/setup',
        name: 'admin/setupblocks',
        options: [
            'comment' => 'Просмотре не активированных блоков'
        ]
    )]
    public function setUp(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/blocks/setup.twig',
                $this->getContext($this->getContainer()->get(SetupBlocks::class))
            )
        );
    }


}
