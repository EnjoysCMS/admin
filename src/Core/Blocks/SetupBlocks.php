<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupBlocks implements ModelInterface
{


    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->logger = $logger->withName('Blocks Config');
    }

    /**
     * @throws \Exception
     */
    public function getContext(): array
    {
        $allBlocks = new Config($this->logger);
        $configs = array_merge(
            [getenv('ROOT_PATH') . '/app/blocks.yml'],
            glob(getenv('ROOT_PATH') . '/modules/*/blocks.yml'),
        );

        foreach ($configs as $config) {
            $allBlocks->addConfig($config, [], YAML::class);
        }

        return [
            'blocks' => $allBlocks->getConfig(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/blocks') => 'Менеджер блоков',
                'Активация новых блоков'
            ],
        ];
    }
}
