<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use EnjoysCMS\Core\Block\BlockCollection;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupBlocks implements ModelInterface
{


    private LoggerInterface $logger;

    public function __construct(
        private BlockCollection $blockCollection,
        private UrlGeneratorInterface $urlGenerator
    ) {

    }

    /**
     * @throws \Exception
     */
    public function getContext(): array
    {

        return [
            'blocks' => $this->blockCollection->getCollection(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                'Активация новых блоков'
            ],
        ];
    }
}
