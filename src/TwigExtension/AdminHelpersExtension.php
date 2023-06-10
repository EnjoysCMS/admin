<?php


namespace EnjoysCMS\Module\Admin\TwigExtension;


use EnjoysCMS\Core\Modules\ModuleCollection;
use Symfony\Component\Routing\RouteCollection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class AdminHelpersExtension extends AbstractExtension
{

    public function __construct(
        private RouteCollection $routeCollection,
        private ModuleCollection $moduleCollection
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getModules', [$this, 'getModules']),
            new TwigFunction('getApplicationAdminLinks', [$this, 'getApplicationAdminLinks']),

        ];
    }

    public function getApplicationAdminLinks()
    {
        return array_filter(
            $this->routeCollection->getIterator()->getArrayCopy(),
            function ($r) {
                if (!empty($r->getOption('admin'))) {
                    return true;
                }
                return false;
            }
        );
    }

    public function getModules(): array
    {
        return array_filter(
            $this->moduleCollection->getCollection(),
            function ($m) {
                if (!empty($m->adminLinks)) {
                    return true;
                }
                return false;
            }
        );
    }


}
