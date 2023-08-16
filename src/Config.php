<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Modules\AbstractModuleConfig;

final class Config extends AbstractModuleConfig
{

    public function __construct(\Enjoys\Config\Config $config, private readonly Container $container)
    {
        parent::__construct($config);
    }

    public function getModulePackageName(): string
    {
        return 'enjoyscms/admin';
    }

    public function getContentEditorConfigParamForCustomBlocks(): string|array|null
    {
        return $this->get('editor->custom_blocks');
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRendererForm(?Form $form = null): RendererInterface
    {
        /** @var RendererInterface $renderer */
        $renderer = $this->container->make($this->get('renderer') ?? RendererInterface::class);
        if ($form !== null){
            $renderer->setForm($form);
        }
        return $renderer;
    }


}
