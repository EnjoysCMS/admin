<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Settings;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

final class Edit
{
    private \EnjoysCMS\Core\Setting\Repository\Setting|EntityRepository $settingRepository;
    private \EnjoysCMS\Core\Setting\Entity\Setting $settingEntity;

    /**
     * @throws NotFoundException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request
    ) {
        $this->settingRepository = $this->entityManager->getRepository(\EnjoysCMS\Core\Setting\Entity\Setting::class);
        $this->settingEntity = $this->settingRepository->find(
            $request->getQueryParams()['id'] ?? 0
        ) ?? throw new NotFoundException(
            sprintf(
                'Setting with name <u>%s</u> not found',
                htmlspecialchars(
                    $request->getQueryParams()['id'] ?? 0
                )
            ),
        );
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
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
    public function doAction(): void
    {
        $this->settingEntity->setVar($this->request->getParsedBody()['var'] ?? null);
        $this->settingEntity->setValue($this->request->getParsedBody()['value'] ?? null);
        $this->settingEntity->setType($this->request->getParsedBody()['type'] ?? null);
        $this->settingEntity->setParams($this->request->getParsedBody()['params'] ?? null);
        $this->settingEntity->setName($this->request->getParsedBody()['name'] ?? null);
        $this->settingEntity->setDescription($this->request->getParsedBody()['description'] ?? null);

        $this->entityManager->flush();
    }


    public function getSettingEntity(): \EnjoysCMS\Core\Setting\Entity\Setting
    {
        return $this->settingEntity;
    }
}
