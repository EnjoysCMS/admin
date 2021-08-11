<?php

declare(strict_types=1);

namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Blocks\ActivateBlock;
use App\Module\Admin\Core\Blocks\AddBlocks;
use App\Module\Admin\Core\Blocks\BlockLocations;
use App\Module\Admin\Core\Blocks\CloneBlock;
use App\Module\Admin\Core\Blocks\DeleteBlock;
use App\Module\Admin\Core\Blocks\EditBlock;
use App\Module\Admin\Core\Blocks\ManageBlocks;
use App\Module\Admin\Core\Blocks\SetupBlocks;
use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Annotation\Route;

class Blocks extends BaseController
{

    #[Route(
        path: '/admin/blocks/setting',
        name: 'admin/blocks',
        options: [
            'aclComment' => 'Просмотр активных блоков'
        ]
    )]
    public function manage(): string
    {
        return $this->view(
            '@a/blocks/manage.twig',
            $this->getContext($this->getContainer()->get(ManageBlocks::class))
        );
    }

    #[Route(
        path: '/admin/blocks/activate',
        name: 'admin/acivateblocks',
        options: [
            'aclComment' => 'Установка (активация) блоков'
        ]
    )]
    public function activate()
    {
        $this->getContainer()->get(ActivateBlock::class)();
    }

    #[Route(
        path: '/admin/blocks/delete/{id}',
        name: 'admin/deleteblocks',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Удаление блоков'
        ]
    )]
    public function delete()
    {
        $this->getContainer()->get(DeleteBlock::class)($this->getContainer());
    }

    #[Route(
        path: '/admin/blocks/clone/{id}',
        name: 'admin/cloneblocks',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Клонирование блоков'
        ]
    )]
    public function clone(): void
    {
        $this->getContainer()->get(CloneBlock::class)($this->getContainer());
    }

    #[Route(
        path: '/admin/blocks/edit/{id}',
        name: 'admin/editblock',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Редактирование блоков'
        ]
    )]
    public function edit(ContainerInterface $container): string
    {
        return $this->view(
            '@a/blocks/edit.twig',
            $this->getContext($container->get(FactoryInterface::class)->make(EditBlock::class))
        );
    }

    #[Route(
        path: '/admin/blocks/add',
        name: 'admin/addblock',
        options: [
            'aclComment' => 'Добавление пользовательского блока (простой текстовый блок)'
        ]
    )]
    public function add(): string
    {
        return $this->view(
            '@a/blocks/add.twig',
            $this->getContext($this->getContainer()->get(AddBlocks::class))
        );
    }

    #[Route(
        path: '/admin/blocks/locations/{id}',
        name: 'admin/blocklocation',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Установка расположения блоков'
        ]
    )]
    public function location(): string
    {
        return $this->view(
            '@a/blocks/locations.twig',
            $this->getContext($this->getContainer()->get(BlockLocations::class))
        );
    }

    #[Route(
        path: 'admin/setupblocks',
        name: '/admin/blocks/setup',
        options: [
            'aclComment' => 'Просмотре не активированных блоков'
        ]
    )]
    public function setUp(): string
    {
        return $this->view(
            '@a/blocks/setup.twig',
            $this->getContext($this->getContainer()->get(SetupBlocks::class))
        );
    }


}
