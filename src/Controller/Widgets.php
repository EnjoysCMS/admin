<?php

declare(strict_types=1);

namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Widgets\ActivateWidget;
use App\Module\Admin\Core\Widgets\Manage;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Widgets extends BaseController
{

    #[Route(
        path: '/admin/widgets/delete/{id}',
        name: 'admin/deletewidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Удаление виджетов'
        ]
    )]

    public function delete()
    {
    }

    #[Route(
        path: '/admin/widgets/clone/{id}',
        name: 'admin/clonewidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Клонирование виджетов'
        ]
    )]

    public function clone()
    {
    }

    #[Route(
        path: '/admin/widgets/edit/{id}',
        name: 'admin/editwidget',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Редактирование виджетов'
        ]
    )]

    public function edit()
    {
    }

    #[Route(
        path: '/admin/widgets/manage',
        name: 'admin/managewidgets',
        options: [
            'aclComment' => 'Просмотр не активированных виджетов'
        ]
    )]

    public function manage()
    {
        return $this->view(
            '@a/widgets/manage.twig',
            $this->getContext($this->getContainer()->get(Manage::class))
        );
    }

    #[Route(
        path: '/admin/widgets/activate',
        name: 'admin/acivatewidget',
        options: [
            'aclComment' => 'Установка (активация) виджетов'
        ]
    )]

    public function activate()
    {
        $this->getContainer()->get(ActivateWidget::class)();
    }

}
