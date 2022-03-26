<?php

declare(strict_types=1);


namespace App\Module\Admin\Core\Settings;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EditSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;
    private ?\EnjoysCMS\Core\Entities\Setting $settingEntity;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
        $this->settingEntity = $this->settingRepository->find($requestWrapper->getQueryData('id'));

        if ($this->settingEntity === null) {
            Error::code(404);
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ExceptionRule
     */
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

    /**
     * @throws ExceptionRule
     */
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
                $newVar = $this->requestWrapper->getPostData('var');
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
        ;
        $form->text('params', 'params')->setDescription('json');
        $form->text('name', 'name')->addRule(Rules::REQUIRED);
        $form->text('description', 'description');
        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->settingEntity->setVar($this->requestWrapper->getPostData('var'));
        $this->settingEntity->setValue($this->requestWrapper->getPostData('value'));
        $this->settingEntity->setType($this->requestWrapper->getPostData('type'));
        $this->settingEntity->setParams($this->requestWrapper->getPostData('params'));
        $this->settingEntity->setName($this->requestWrapper->getPostData('name'));
        $this->settingEntity->setDescription($this->requestWrapper->getPostData('description'));

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
