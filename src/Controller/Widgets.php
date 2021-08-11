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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Widgets extends BaseController
{

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
            $this->getContext($this->getContainer()->get(Manage::class))
        );
    }


    public function activate()
    {
        $this->getContainer()->get(ActivateWidget::class)();
    }

}
