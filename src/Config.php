<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;

final class Config
{

    private ModuleConfig $config;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->config = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/admin']);
    }


    public function getModuleConfig(): ModuleConfig
    {
        return $this->config;
    }

    public function getContentEditorConfigParamForCustomBlocks()
    {
        return $this->config->get('editor')['custom_blocks'] ?? null;
    }

}
