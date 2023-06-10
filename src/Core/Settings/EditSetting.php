<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Settings;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EditSetting implements ModelInterface
{
    private ObjectRepository $settingRepository;
    private ?\EnjoysCMS\Core\Entities\Setting $settingEntity;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect,
    ) {
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Entities\Setting::class);
        $this->settingEntity = $this->settingRepository->find($request->getQueryParams()['id'] ?? 0);

        if ($this->settingEntity === null) {
            throw new NotFoundException(
                sprintf(
                    'Setting with name <u>%s</u> not found',
                    htmlspecialchars(
                        $request->getQueryParams()['id'] ?? 0
                    )
                ),
            );
        }
    }

    /**
     * @throws ExceptionRule
     * @throws OptimisticLockException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();
        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('admin/setting', emit: true);
        }
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer,
            '_title' => 'Изменение настройки | Настройки | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get(
                    'sitename'
                ),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/setting') => 'Глобальные параметры сайта',
                sprintf('Редактирование параметра `%s`', $this->settingEntity->getName()),
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
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
                $newVar = $this->request->getParsedBody()['var'] ?? null;
                if ($newVar === $this->settingEntity->getVar()) {
                    return true;
                }
                $check = $this->settingRepository->find($newVar);
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
        $this->settingEntity->setVar($this->request->getParsedBody()['var'] ?? null);
        $this->settingEntity->setValue($this->request->getParsedBody()['value'] ?? null);
        $this->settingEntity->setType($this->request->getParsedBody()['type'] ?? null);
        $this->settingEntity->setParams($this->request->getParsedBody()['params'] ?? null);
        $this->settingEntity->setName($this->request->getParsedBody()['name'] ?? null);
        $this->settingEntity->setDescription($this->request->getParsedBody()['description'] ?? null);

        $this->entityManager->flush();
    }
}
