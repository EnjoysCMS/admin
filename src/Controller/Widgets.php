<?php

declare(strict_types=1);

namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Widgets\ActivateWidgets;
use App\Module\Admin\Core\Widgets\Manage;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Widgets extends BaseController
{

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct(
            $container->get(Environment::class),
            $container->get(ServerRequestInterface::class),
            $container->get(EntityManager::class),
            $container->get(UrlGeneratorInterface::class),
            $container->get(RendererInterface::class)
        );
        $this->container = $container;
    }


    /**
     * @throws Exception
     */
    public function delete()
    {
    }

    /**
     * @throws Exception
     */
    public function clone()
    {
    }


    public function edit()
    {
    }


    public function manage()
    {
        return $this->view(
            '@a/widgets/manage.twig',
            $this->getContext(new Manage($this->container))
        );
    }


    public function activate()
    {
        $widget = new ActivateWidgets($this->serverRequest->get('class'), $this->container);
        $id = $widget->activate();
        Redirect::http($this->urlGenerator->generate('admin/editwidget', ['id' => $id]));
    }

}
