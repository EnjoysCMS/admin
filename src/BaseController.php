<?php


namespace App\Module\Admin;


use App\Module\Admin\Core\ModelInterface;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use EnjoysCMS\Core\Components\Helpers\Assets;
use Psr\Container\ContainerInterface;
use Twig\Environment;

abstract class BaseController
{


    private Environment $twig;

    public function __construct(private ContainerInterface $container)
    {
        $this->twig = $this->container->get(Environment::class);

        $this->initAssets();


        /**
         * @var AssetsExtension $AssetsExtension
         */
        $AssetsExtension = $this->twig->getExtension(AssetsExtension::class);
        $AssetsExtension->getAssetsCollector()->getEnvironment()->setStrategy(
            \Enjoys\AssetsCollector\Assets::STRATEGY_MANY_FILES
        );

        $twigLoader = $this->twig->getLoader();
        $twigLoader->addPath(__DIR__ . '/../template', 'a');


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
    protected function initAssets()
    {
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/adminLTE/dist',
            __DIR__ . '/../node_modules/admin-lte/dist'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/webfonts',
            __DIR__ . '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/node_modules/admin-lte/plugins/fontawesome-free/webfonts',
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


    protected function getContext(ModelInterface $model)
    {
        return $model->getContext();
    }

    protected function view(string $twigTemplatePath, array $context)
    {
        return $this->twig->render($twigTemplatePath, $context);
    }


}
