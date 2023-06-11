<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Widgets\WidgetsTwigExtension;
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
    public function dashboard(UrlGeneratorInterface $urlGenerator, Identity $identity): ResponseInterface
    {
        $this->twig->addExtension($this->container->get(WidgetsTwigExtension::class));

        return $this->response(
            $this->twig->render(
                '@a/dashboard/dashboard.twig',
                [
                    '_title' => 'Dashboard | Admin | ' . $this->setting->get('sitename'),
                    'breadcrumbs' => [
                        $urlGenerator->generate('admin/index') => 'Главная',
                        'Dashboard',
                    ],
                    'widgets' => $this->container->get(EntityManager::class)->getRepository(
                        Widget::class
                    )->getSortWidgets($identity->getUser())
                ]
            )
        );
    }


}
