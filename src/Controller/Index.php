<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use EnjoysCMS\Core\Components\Widgets\WidgetsTwigExtension;
use EnjoysCMS\Core\Entities\Widget;
use Psr\Container\ContainerInterface;

class Index extends BaseController
{

    public function dashboard(ContainerInterface $container): string
    {
        $this->twig->addExtension($container->get(WidgetsTwigExtension::class));

        return $this->twig->render(
            '@a/dashboard/dashboard.twig',
            [
                '_title' => 'Dashboard | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
                'widgets' => $this->entityManager->getRepository(Widget::class)->getSortWidgets()
            ]
        );
    }


}
