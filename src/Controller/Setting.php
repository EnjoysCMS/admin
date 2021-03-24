<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Setting extends BaseController
{
    /**
     * @var ObjectRepository
     */
    private ObjectRepository $settingRepository;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
    }

    public function setting()
    {
        return $this->view(
            '@a/setting/setting.twig', $this->getContext(
                new \App\Module\Admin\Core\Setting(
                    $this->settingRepository,
                    $this->entityManager,
                    $this->serverRequest,
                    $this->urlGenerator,
                    $this->renderer
                )
            )
        );
    }
}
