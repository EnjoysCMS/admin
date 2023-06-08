<?php


namespace EnjoysCMS\Module\Admin;


use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4Renderer;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Helpers\Assets;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Admin\TwigExtension\AdminHelpersExtension;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

abstract class AdminBaseController extends BaseController
{

    public const UUID_RULE_REQUIREMENT = '[0-9a-f]{8}-[0-9a-f]{4}-[13-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

    private Environment $twig;


    public function __construct(private ContainerInterface $container, ResponseInterface $response = null)
    {
        parent::__construct($response);

        $this->container->set(RendererInterface::class, function (){
            return new Bootstrap4Renderer();
        });

        $this->twig = $this->container->get(Environment::class);
        $this->twig->addExtension(new AdminHelpersExtension($this->container->get('Router')->getRouteCollection()));

        $this->initAssets();


        /**
         * @var AssetsExtension $AssetsExtension
         */
        $AssetsExtension = $this->twig->getExtension(AssetsExtension::class);
        $AssetsExtension->getAssetsCollector()->getEnvironment()->setStrategy(
            \Enjoys\AssetsCollector\Assets::STRATEGY_MANY_FILES
        );


        $this->twig->getLoader()->addPath(__DIR__ . '/../template', 'a');


        Assets::css(
            [
                'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback',
                __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/css/all.min.css',
                __DIR__ . '/../node_modules/admin-lte/dist/css/adminlte.min.css',
            ]
        );

        Assets::js(
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
     * @throws \Exception
     */
    protected function initAssets(): void
    {
        $path = str_replace(getenv('ROOT_PATH'), '', realpath(__DIR__.'/../'));

        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/adminLTE/dist',
            __DIR__ . '/../node_modules/admin-lte/dist'
        );
        Assets::createSymlink(
            sprintf('%s/assets%s/webfonts', $_ENV['PUBLIC_DIR'], $path),
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
        Assets::createSymlink(
            sprintf('%s/assets%s/node_modules/admin-lte/plugins/fontawesome-free/webfonts', $_ENV['PUBLIC_DIR'], $path),
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return mixed|Environment
     */
    public function getTwig(): mixed
    {
        return $this->twig;
    }


    protected function getContext(ModelInterface $model): array
    {
        dd($model);
        return $model->getContext();
    }

    protected function view(string $twigTemplatePath, array $context): string
    {
        return $this->twig->render($twigTemplatePath, $context);
    }


}
