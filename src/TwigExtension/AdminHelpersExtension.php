<?php


namespace App\Module\Admin\TwigExtension;


use Symfony\Component\Routing\RouteCollection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class AdminHelpersExtension extends AbstractExtension
{
    private RouteCollection $routeCollection;

    public function __construct(RouteCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
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
            \EnjoysCMS\Core\Components\Helpers\Modules::installed(),
            function ($m) {
                if (!empty($m->adminLinks)) {
                    return true;
                }
                return false;
            }
        );
    }


}
