<?php


namespace EnjoysCMS\Module\Admin\Controller;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Core\Widgets\Widgets;
use EnjoysCMS\Module\Admin\AdminBaseController;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFunction;

class Dashboard extends AdminBaseController
{

    /**
     * @throws SyntaxError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     * @throws Exception
     */
    #[Route(
        path: '/admin',
        name: 'admin/index',
        options: [
            'comment' => 'Доступ к главной странице в админке (dashboard)'
        ]
    )]
    public function dashboard(
        Identity $identity,
        Widgets $widgets
    ): ResponseInterface {
        $this->twig->addFunction(
            new TwigFunction('ViewWidget', function (int $id) use ($widgets): ?string {
                return $widgets->getWidget($id);
            }, ['is_safe' => ['html']])
        );

        $this->breadcrumbs->setLastBreadcrumb('Dashboard');

        return $this->response(
            $this->twig->render(
                '@a/dashboard/dashboard.twig',
                [
                    '_title' => 'Dashboard | Admin | ' . $this->setting->get('sitename'),
                    'widgets' => $this->container->get(EntityManager::class)->getRepository(
                        Widget::class
                    )->getSortWidgets($identity->getUser())
                ]
            )
        );
    }


}
