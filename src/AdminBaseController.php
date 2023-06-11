<?php


namespace EnjoysCMS\Module\Admin;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use EnjoysCMS\Core\Components\Helpers\Assets;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\TwigExtension\AdminHelpersExtension;
use Exception;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
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
        protected readonly Container $container,
        protected readonly Environment $twig,
        protected readonly \Enjoys\AssetsCollector\Assets $assets,
        protected ResponseInterface $response,
    ) {
        $this->twig->addExtension($this->container->get(AdminHelpersExtension::class));

        $this->makeSymlink();

        /**
         * @var AssetsExtension $AssetsExtension
         */
        $AssetsExtension = $this->twig->getExtension(AssetsExtension::class);
        $AssetsExtension->getAssetsCollector()->getEnvironment()->setStrategy(
            \Enjoys\AssetsCollector\Assets::STRATEGY_MANY_FILES
        );


        $this->twig->getLoader()->addPath(__DIR__ . '/../template', 'a');

        $this->assets->add('css', [
            'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback',
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/css/all.min.css',
            __DIR__ . '/../node_modules/admin-lte/dist/css/adminlte.min.css',
        ]);

        $this->assets->add(
            'js',
            [
                __DIR__ . '/../node_modules/admin-lte/plugins/jquery/jquery.min.js',
                __DIR__ . '/../node_modules/admin-lte/plugins/jquery-ui/jquery-ui.min.js',
                __DIR__ . '/../node_modules/admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js',
                __DIR__ . '/../node_modules/admin-lte/dist/js/adminlte.js',
//                __DIR__ . '/../node_modules/admin-lte/dist/js/demo.js',
                __DIR__ . '/../template/assets/custom.js',
            ]
        );
    }

    /**
     * @throws Exception
     */
    protected function makeSymlink(): void
    {
        $path = str_replace(getenv('ROOT_PATH'), '', realpath(__DIR__ . '/../'));

        makeSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/adminLTE/dist',
            __DIR__ . '/../node_modules/admin-lte/dist'
        );
        makeSymlink(
            sprintf('%s/assets%s/webfonts', $_ENV['PUBLIC_DIR'], $path),
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
        makeSymlink(
            sprintf('%s/assets%s/node_modules/admin-lte/plugins/fontawesome-free/webfonts', $_ENV['PUBLIC_DIR'], $path),
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
    }

    protected function response(string $body): ResponseInterface
    {
        $this->response->getBody()->write($body);
        return $this->response;
    }
}
