<?php


namespace App\Module\Admin;


use App\Components\Helpers\Assets;
use Doctrine\ORM\EntityManager;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

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
    protected EntityManager $em;
    /**
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(Environment $twig, ServerRequestInterface $serverRequest, EntityManager $em, UrlGeneratorInterface $urlGenerator)
    {
        $this->initAssets();

        /** @var AssetsExtension $AssetsExtension */
        $AssetsExtension = $twig->getExtension(AssetsExtension::class);
        $AssetsExtension->getAssetsCollector()->getEnvironment()->setStrategy(
            \Enjoys\AssetsCollector\Assets::STRATEGY_MANY_FILES
        );
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__.'/template', 'a');
        $this->twig = $twig;

        Assets::css(
            [
                'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback',
                $_ENV['ADMINLTE'] . '/plugins/fontawesome-free/css/all.min.css',
                $_ENV['ADMINLTE'] . '/dist/css/adminlte.min.css',
            ]
        );

        Assets::js(
            [
                $_ENV['ADMINLTE'] . '/plugins/jquery/jquery.min.js',
                $_ENV['ADMINLTE'] . '/plugins/jquery-ui/jquery-ui.min.js',
                $_ENV['ADMINLTE'] . '/plugins/bootstrap/js/bootstrap.bundle.min.js',
                $_ENV['ADMINLTE'] . '/dist/js/adminlte.js',
            ]
        );
        $this->serverRequest = $serverRequest;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    protected function initAssets()
    {


        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/dist',
            $_ENV['ADMINLTE'] . '/dist'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/webfonts',
            $_ENV['ADMINLTE'] . '/plugins/fontawesome-free/webfonts'
        );
        Assets::createSymlink(
            $_ENV['PUBLIC_DIR'] . '/assets/vendor/almasaeed2010/adminlte/plugins/fontawesome-free/webfonts',
            $_ENV['ADMINLTE'] . '/plugins/fontawesome-free/webfonts'
        );
    }


}
