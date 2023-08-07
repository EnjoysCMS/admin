<?php


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\AssetsCollector\Assets;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4Renderer;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Admin\TwigExtension\AdminHelpersExtension;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

use function Enjoys\FileSystem\makeSymlink;

abstract class AdminBaseController
{

    public const UUID_RULE_REQUIREMENT = '[0-9a-f]{8}-[0-9a-f]{4}-[13-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function __construct(
        protected Container $container,
        protected readonly Environment $twig,
        protected readonly Setting $setting,
        protected ResponseInterface $response,
        protected BreadcrumbCollection $breadcrumbs,
    ) {

        $this->container->set(RendererInterface::class, new Bootstrap4Renderer());

        $this->twig->addExtension($this->container->get(AdminHelpersExtension::class));
        $this->twig->getLoader()->addPath(__DIR__ . '/../template', 'a');

        $this->twig->addGlobal('breadcrumbs', $this->breadcrumbs
            ->remove('system/index')
            ->add('@admin_index', 'Главная')
        );
    }


    protected function response(string $body): ResponseInterface
    {
        $this->response->getBody()->write($body);
        return $this->response;
    }

    protected function jsonResponse(mixed $payload): ResponseInterface
    {
        $this->response = $this->response->withHeader('Content-Type', 'application/json');
        $this->response->getBody()->write(json_encode($payload));
        return $this->response;
    }
}
