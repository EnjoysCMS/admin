<?php


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4Renderer;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Modules\ModuleCollection;
use Exception;
use Symfony\Component\Routing\RouteCollection;

abstract class AdminController extends AbstractController
{

    public const UUID_RULE_REQUIREMENT = '[0-9a-f]{8}-[0-9a-f]{4}-[13-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

    protected RendererInterface $renderer;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function __construct(
        protected Container $container,
    ) {
        parent::__construct($container);
        $this->container->set(RendererInterface::class, new Bootstrap4Renderer());
        $this->renderer = $this->container->get(RendererInterface::class);

        $this->twig->getLoader()->addPath(__DIR__ . '/../template', 'a');

        $this->twig->addGlobal(
            'breadcrumbs',
            $this->breadcrumbs
                ->remove('system/index')
                ->add('@admin_index', 'Главная')
        );

        $this->twig->addGlobal(
            'moduleCollection',
            $this->container->get(ModuleCollection::class)
        );
        $this->twig->addGlobal(
            'routeCollection',
            $this->container->get(RouteCollection::class)
        );
    }
}
