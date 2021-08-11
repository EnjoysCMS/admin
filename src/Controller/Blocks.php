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

class Blocks extends BaseController
{

    public function manage(): string
    {
        return $this->view(
            '@a/blocks/manage.twig',
            $this->getContext($this->getContainer()->get(ManageBlocks::class))
        );
    }

    public function activate()
    {
        $this->getContainer()->get(ActivateBlock::class)();
    }

    public function delete()
    {
        $this->getContainer()->get(DeleteBlock::class)($this->getContainer());
    }

    public function clone(): void
    {
        $this->getContainer()->get(CloneBlock::class)($this->getContainer());
    }

    public function edit(ContainerInterface $container): string
    {
        return $this->view(
            '@a/blocks/edit.twig',
            $this->getContext($container->get(FactoryInterface::class)->make(EditBlock::class))
        );
    }

    public function add(): string
    {
        return $this->view(
            '@a/blocks/add.twig',
            $this->getContext($this->getContainer()->get(AddBlocks::class))
        );
    }


    public function location(): string
    {
        return $this->view(
            '@a/blocks/locations.twig',
            $this->getContext($this->getContainer()->get(BlockLocations::class))
        );
    }

    public function setUp(): string
    {
        return $this->view(
            '@a/blocks/setup.twig',
            $this->getContext($this->getContainer()->get(SetupBlocks::class))
        );
    }


}
