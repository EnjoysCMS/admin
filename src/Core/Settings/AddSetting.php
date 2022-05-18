<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
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
                ),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/setting') => 'Глобальные параметры сайта',
                'Добавление нового глобального параметра'
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->text('var', 'var')->addRule(Rules::REQUIRED)->addRule(
            Rules::CALLBACK,
            'Настройка с таким id уже существует',
            function () {
                $check = $this->settingRepository->findOneBy(['var' => $this->requestWrapper->getPostData('var')]);
                if ($check === null) {
                    return true;
                }
                return false;
            }
        );
        $form->text('value', 'value');
        $form->select('type', 'type')->fill(
            [
                'text',
                'select',
                'radio',
                'textarea'
            ],
            true
        )->addRule(Rules::REQUIRED);;
        $form->text('params', 'params')->setDescription('json');
        $form->text('name', 'name')->addRule(Rules::REQUIRED);;
        $form->text('description', 'description');
        $form->submit('add');
        return $form;
    }

    private function doAction(): void
    {
        $setting = new \EnjoysCMS\Core\Entities\Setting();
        $setting->setVar($this->requestWrapper->getPostData('var'));
        $setting->setValue($this->requestWrapper->getPostData('value'));
        $setting->setType($this->requestWrapper->getPostData('type'));
        $setting->setParams($this->requestWrapper->getPostData('params'));
        $setting->setName($this->requestWrapper->getPostData('name'));
        $setting->setDescription($this->requestWrapper->getPostData('description'));
        $setting->setRemovable(true);

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
