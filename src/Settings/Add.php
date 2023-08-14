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
use Psr\Http\Message\ServerRequestInterface;

final class Add
{
    private \EnjoysCMS\Core\Setting\Repository\Setting|EntityRepository $settingRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->settingRepository = $this->em->getRepository(\EnjoysCMS\Core\Setting\Entity\Setting::class);
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
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
        $setting = new \EnjoysCMS\Core\Setting\Entity\Setting();
        $setting->setVar($this->request->getParsedBody()['var'] ?? null);
        $setting->setValue($this->request->getParsedBody()['value'] ?? null);
        $setting->setType($this->request->getParsedBody()['type'] ?? null);
        $setting->setParams($this->request->getParsedBody()['params'] ?? null);
        $setting->setName($this->request->getParsedBody()['name'] ?? null);
        $setting->setDescription($this->request->getParsedBody()['description'] ?? null);
        $setting->setRemovable(true);

        $this->em->persist($setting);
        $this->em->flush();
    }
}
