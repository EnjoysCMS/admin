<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Widgets;

use Doctrine\ORM\EntityManager;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Manage implements ModelInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function getContext(): array
    {
        $installedWidgets = array_map(
            function ($widget) {
                return $widget->getClass();
            },
            $this->container->get(EntityManager::class)->getRepository(Widget::class)->findBy([
                'user' => $this->container->get(Identity::class)->getUser()
            ])
        );

        $allWidgets = new Config();

        $configs = array_merge(
            [getenv('ROOT_PATH') . '/app/widgets.yml'],
            glob(getenv('ROOT_PATH') . '/modules/*/widgets.yml'),
        );

        foreach ($configs as $config) {
            $allWidgets->addConfig($config, [], YAML::class);
        }


        return [
            'allowedWidgets' => $allWidgets->getConfig(),
            'installedWidgets' => $installedWidgets,
        ];
    }
}
