<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Widgets\WidgetsTwigExtension;
use EnjoysCMS\Core\Entities\Widget;
use Symfony\Component\Routing\Annotation\Route;

class Dashboard extends BaseController
{

    #[Route(
        path: '/admin',
        name: 'admin/index',
        options: [
            'aclComment' => 'Доступ к главной странице в админке (dashboard)'
        ]
    )]
    public function dashboard(): string
    {
        $this->getTwig()->addExtension($this->getContainer()->get(WidgetsTwigExtension::class));

        return $this->view(
            '@a/dashboard/dashboard.twig',
            [
                '_title' => 'Dashboard | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
                'widgets' => $this->getContainer()->get(EntityManager::class)->getRepository(
                    Widget::class
                )->getSortWidgets()
            ]
        );
    }


}