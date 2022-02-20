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
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EditSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;
    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private UrlGeneratorInterface $urlGenerator;
    private RendererInterface $renderer;
    private ?\EnjoysCMS\Core\Entities\Setting $settingEntity;

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
        $this->settingEntity = $this->settingRepository->find($serverRequest->get('id'));

        if ($this->settingEntity === null) {
            Error::code(404);
        }
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
            '_title' => 'Изменение настройки | Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get(
                    'sitename'
                )
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->setDefaults(
            [
                'var' => $this->settingEntity->getVar(),
                'value' => $this->settingEntity->getValue(),
                'type' => $this->settingEntity->getType(),
                'params' => $this->settingEntity->getParams(),
                'name' => $this->settingEntity->getName(),
                'description' => $this->settingEntity->getDescription(),
            ]
        );
        $form->text('var', 'var')->addRule(Rules::REQUIRED)->addRule(
            Rules::CALLBACK,
            'Настройка с таким id уже существует',
            function () {
                $newVar = $this->serverRequest->post('var');
                if ($newVar === $this->settingEntity->getVar()) {
                    return true;
                }
                $check = $this->settingRepository->find($newVar);
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

    private function doAction(): void
    {
        $this->settingEntity->setVar($this->serverRequest->post('var'));
        $this->settingEntity->setValue($this->serverRequest->post('value'));
        $this->settingEntity->setType($this->serverRequest->post('type'));
        $this->settingEntity->setParams($this->serverRequest->post('params'));
        $this->settingEntity->setName($this->serverRequest->post('name'));
        $this->settingEntity->setDescription($this->serverRequest->post('description'));

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}