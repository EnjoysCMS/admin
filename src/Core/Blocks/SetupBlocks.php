<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use EnjoysCMS\Core\Block\Collection;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupBlocks implements ModelInterface
{


    public function __construct(
        private Collection $blockCollection,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getContext(): array
    {
        return [
            'blocks' => $this->blockCollection,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                'Активация новых блоков'
            ],
        ];
    }
}
