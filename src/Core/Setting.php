<?php


namespace App\Module\Admin\Core;


use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function DI\string;

class Setting implements ModelInterface
{


    /**
     * @var ObjectRepository
     */
    private ObjectRepository $settingRepository;
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
        ObjectRepository $settingRepository,
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        $this->settingRepository = $settingRepository;
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->renderer = $renderer;
    }

    public function getContext(): array
    {
        $form = $this->getForm();
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer
        ];
    }

    private function getForm()
    {
        $form = new Form();
        $form->setDefaults($this->settingRepository->findAllKeyVar());
        $settings = (array)$this->settingRepository->findAll();


        /** @var \App\Entities\Setting $setting */
        foreach ($settings as $setting) {
            switch ($setting->getType()) {
                case 'radio':
                    $form->radio($setting->getVar(), $setting->getName())
                        ->setDescription((string)$setting->getDescription())
                        ->fill(json_decode($setting->getParams(), true));

                    unset($data);
                    break;
                case 'select':
                    $form->select($setting->getVar(), $setting->getName())
                        ->setDescription((string)$setting->getDescription())
                        ->fill(json_decode($setting->getParams(), true));
                    break;
                case 'textarea':
                    $form->textarea($setting->getVar(), $setting->getName())
                        ->setDescription((string)$setting->getDescription());
                    break;
                case 'text':
                default:
                    $form->text($setting->getVar(), $setting->getName())
                        ->setDescription((string)$setting->getDescription());
                    break;
            }
        }
        return $form;
        // die();
    }

    private function doAction()
    {
    }
}