<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Modules\ModuleCollection;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

final class Config
{

    private const MODULE_NAME = 'enjoyscms/admin';


    /**
     * @throws Exception
     */
    public function __construct(
        private readonly \Enjoys\Config\Config $config,
        private Container $container,
        ModuleCollection $moduleCollection
    ) {
        $module = $moduleCollection->find(self::MODULE_NAME) ?? throw new InvalidArgumentException(
            sprintf(
                'Module %s not found. Name must be same like packageName in module composer.json',
                self::MODULE_NAME
            )
        );


        if (file_exists($module->path . '/config.yml')) {
            $config->addConfig(
                [
                    self::MODULE_NAME => file_get_contents($module->path . '/config.yml')
                ],
                ['flags' => Yaml::PARSE_CONSTANT],
                \Enjoys\Config\Config::YAML,
                false
            );
        }
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config->get(self::MODULE_NAME);
        }
        return $this->config->get(sprintf('%s->%s', self::MODULE_NAME, $key), $default);
    }


    public function all(): array
    {
        return $this->config->get();
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
        $rendererFormClassString = $this->get('renderer') ?? RendererInterface::class;
        return $this->container->make($rendererFormClassString);
    }

}
