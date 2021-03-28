<?php


namespace App\Module\Admin\Core\Widgets;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use EnjoysCMS\Core\Entities\Widgets;
use Psr\Container\ContainerInterface;

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

    public function getContext(): array
    {
        $installedWidgets = array_map(
            function ($widget) {
                return $widget->getClass();
            },
            $this->container->get(EntityManager::class)->getRepository(Widgets::class)->findAll()
        );

        $allWidgets = new Config();
        $configs = glob($_ENV['PROJECT_DIR'] . '/modules/*/widgets.yml');
        foreach ($configs as $config) {
            $allWidgets->addConfig($config, [], YAML::class);
        }
        $activeWidgets = (array_filter(
            $allWidgets->getConfig(),
            function ($k) use ($installedWidgets) {
                if (in_array($k, $installedWidgets)) {
                    return true;
                }
                return false;
            },
            ARRAY_FILTER_USE_KEY
        ));
        $notActiveWidgets = array_diff_key($allWidgets->getConfig(), $activeWidgets);

        return ['activeWidgets' => $activeWidgets, 'notActiveWidgets' => $notActiveWidgets];
    }
}