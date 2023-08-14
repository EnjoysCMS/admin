<?php


namespace EnjoysCMS\Module\Admin\Controller;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Block\Entity\Widget;
use EnjoysCMS\Core\Block\WidgetModel;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFunction;

#[Route('/admin', '@admin_')]
class Dashboard extends AdminController
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
        name: 'index',
        comment: 'Доступ к главной странице в админке (dashboard)'
    )]
    public function dashboard(
        Identity $identity,
        WidgetModel $widgetModel
    ): ResponseInterface {
        $this->twig->addFunction(
            new TwigFunction('ViewWidget', function (int $id) use ($widgetModel): ?string {
                return $widgetModel->view($id);
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
