<?php


namespace App\Module\Admin\Core\Blocks;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Entities\Blocks;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetupBlocks implements ModelInterface
{

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
        $this->blocksRepository = $entityManager->getRepository(Blocks::class);
    }

    public function getContext(): array
    {
        $installedBlocks = array_map(
            function ($block) {
                return $block->getClass();
            },
            $this->blocksRepository->findAll()
        );

        $allBlocks = new Config();
        $configs = glob($_ENV['PROJECT_DIR'] . '/modules/*/blocks.yml');
        foreach ($configs as $config) {
            $allBlocks->addConfig($config, [], YAML::class);
        }
        $activeBlocks = (array_filter(
            $allBlocks->getConfig(),
            function ($k) use ($installedBlocks) {
                if (in_array($k, $installedBlocks)) {
                    return true;
                }
                return false;
            },
            ARRAY_FILTER_USE_KEY
        ));
        $notActiveBlocks = array_diff_key($allBlocks->getConfig(), $activeBlocks);

        return ['activeBlocks' => $activeBlocks, 'notActiveBlocks' => $notActiveBlocks];
    }
}