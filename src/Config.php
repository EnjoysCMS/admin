<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Modules\AbstractModuleConfig;

final class Config extends AbstractModuleConfig
{

    private string $package = 'enjoyscms/admin';

    public function __construct(
        private readonly \Enjoys\Config\Config $config,
        private readonly Container $container
    ) {
//        $this->package = Utils::parseComposerJson(__DIR__ . '/../composer.json')->packageName;
    }

    public function getConfig(): \Enjoys\Config\Config
    {
        return $this->config;
    }

    public function getModulePackageName(): string
    {
        return $this->package;
    }

    public function getContentEditorConfigParamForCustomBlocks(): string|array|null
    {
        return $this->get('editor->custom_blocks');
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRendererForm(): RendererInterface
    {
        return $this->container->make($this->get('renderer') ?? RendererInterface::class);
    }


}
