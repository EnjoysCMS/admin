<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
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
                $check = $this->settingRepository->findOneBy(['var' => $this->request->getParsedBody()['var'] ?? null]);
                if ($check === null) {
                    return true;
                }
                return false;
            }
        );
        $form->text('value', 'value');
        $form->select('type', 'type')
            ->addRule(Rules::REQUIRED)
            ->fill(
                [
                    'text',
                    'select',
                    'radio',
                    'textarea'
                ],
                true
            );
        $form->text('params', 'params')->setDescription('json');
        $form->text('name', 'name')->addRule(Rules::REQUIRED);;
        $form->text('description', 'description');
        $form->submit('add');
        return $form;
    }

    private function doAction(): void
    {
        $setting = new \EnjoysCMS\Core\Entities\Setting();
        $setting->setVar($this->request->getParsedBody()['var'] ?? null);
        $setting->setValue($this->request->getParsedBody()['value'] ?? null);
        $setting->setType($this->request->getParsedBody()['type'] ?? null);
        $setting->setParams($this->request->getParsedBody()['params'] ?? null);
        $setting->setName($this->request->getParsedBody()['name'] ?? null);
        $setting->setDescription($this->request->getParsedBody()['description'] ?? null);
        $setting->setRemovable(true);

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
