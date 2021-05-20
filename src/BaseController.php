<?php


namespace App\Module\Admin;


use EnjoysCMS\Core\Components\Helpers\Assets;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

abstract class BaseController
{
    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $serverRequest;
    /**
     * @var EntityManager
     */
    protected EntityManager $entityManager;
    /**
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $urlGenerator;
    /**
     * @var RendererInterface
     */
    protected RendererInterface $renderer;
    protected LoaderInterface $twigLoader;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->initAssets();

        /**
         *
         *
         * @var AssetsExtension $AssetsExtension
         */
        $AssetsExtension = $twig->getExtension(AssetsExtension::class);
        $AssetsExtension->getAssetsCollector()->getEnvironment()->setStrategy(
            \Enjoys\AssetsCollector\Assets::STRATEGY_MANY_FILES
        );
        $this->twigLoader = $twig->getLoader();
        $this->twigLoader->addPath(__DIR__ . '/../template', 'a');
        $this->twig = $twig;

        Assets::css(
            [
                'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback',
                __DIR__. '/../node_modules/admin-lte/plugins/fontawesome-free/css/all.min.css',
                __DIR__. '/../node_modules/admin-lte/dist/css/adminlte.min.css',
            ]
        );

        Assets::js(
            [
                __DIR__. '/../node_modules/admin-lte/plugins/jquery/jquery.min.js',
                __DIR__. '/../node_modules/admin-lte/plugins/jquery-ui/jquery-ui.min.js',
                __DIR__. '/../node_modules/admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js',
                __DIR__. '/../node_modules/admin-lte/dist/js/adminlte.js',
            ]
        );
        $this->serverRequest = $serverRequest;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
    }

    protected function initAssets()
    {
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/dist',
            __DIR__. '/../node_modules/admin-lte/dist'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/webfonts',
            __DIR__. '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/modules/admin/node_modules/admin-lte/plugins/fontawesome-free/webfonts',
            __DIR__. '/../node_modules/admin-lte/plugins/fontawesome-free/webfonts'
        );
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
