<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Widgets\WidgetsTwigExtension;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Module\Admin\AdminBaseController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Dashboard extends AdminBaseController
{

    #[Route(
        path: '/admin',
        name: 'admin/index',
        options: [
            'comment' => 'Доступ к главной странице в админке (dashboard)'
        ]
    )]
    public function dashboard(UrlGeneratorInterface $urlGenerator): ResponseInterface
    {
        $this->getTwig()->addExtension($this->getContainer()->get(WidgetsTwigExtension::class));

        return $this->responseText($this->view(
            '@a/dashboard/dashboard.twig',
            [
                '_title' => 'Dashboard | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
                'breadcrumbs' => [
                    $urlGenerator->generate('admin/index') => 'Главная',
                    'Dashboard',
                ],
                'widgets' => $this->getContainer()->get(EntityManager::class)->getRepository(
                    Widget::class
                )->getSortWidgets()
            ]
        ));
    }


}
