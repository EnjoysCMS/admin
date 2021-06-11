<?php

declare(strict_types=1);


namespace App\Module\Admin\Core\Settings;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;
    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private UrlGeneratorInterface $urlGenerator;
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

        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
    }

    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            '_title' => 'Добавление настройки | Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get(
                    'sitename'
                )
        ];
    }

    private function getForm()
    {
        $form = new Form(['method' => 'post']);
        $form->text('var', 'var')->addRule(Rules::REQUIRED)->addRule(
            Rules::CALLBACK,
            'Настройка с таким id уже существует',
            function () {
                $check = $this->settingRepository->findOneBy(['var' => $this->serverRequest->post('var')]);
                if ($check === null) {
                    return true;
                }
                return false;
            }
        )
        ;
        $form->text('value', 'value');
        $form->select('type', 'type')->fill(
            [
                'text',
                'select',
                'radio',
                'textarea'
            ],
            true
        )->addRule(Rules::REQUIRED)
        ;;
        $form->text('params', 'params')->setDescription('json');
        $form->text('name', 'name')->addRule(Rules::REQUIRED);;
        $form->text('description', 'description');
        $form->submit('add');
        return $form;
    }

    private function doAction()
    {
        $setting = new \EnjoysCMS\Core\Entities\Setting();
        $setting->setVar($this->serverRequest->post('var'));
        $setting->setValue($this->serverRequest->post('value'));
        $setting->setType($this->serverRequest->post('type'));
        $setting->setParams($this->serverRequest->post('params'));
        $setting->setName($this->serverRequest->post('name'));
        $setting->setDescription($this->serverRequest->post('description'));
        $setting->setRemovable(true);

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}