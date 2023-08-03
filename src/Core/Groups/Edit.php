<?php


namespace EnjoysCMS\Module\Admin\Core\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Core\ACL\ACList;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Http\Message\ServerRequestInterface;

class Edit implements ModelInterface
{
    private Group $group;
    private EntityRepository|\EnjoysCMS\Core\Users\Repository\Group $groupsRepository;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
        private readonly ACList $ACList,
        private readonly Setting $setting,
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);

        $this->group = $this->groupsRepository->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();
    }


    /**
     * @throws ExceptionRule
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->redirect->toRoute('@admin_groups_list', emit: true);
        }

        $this->renderer->setForm($form);


        return [
            'form' => $this->renderer,
            '_title' => 'Редактирование группы | Группы | Admin | ' . $this->setting->get('sitename')
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription(),
                'acl' => array_map(
                    function (ACL $o): int {
                        return $o->getId();
                    },
                    $this->group->getAcl()->toArray()
                )
            ]
        );

        $form->text('name', 'Название')
            ->addRule(
                Rules::CALLBACK,
                'Название группы должно быть уникальным',
                function () {
                    if (null === $group = $this->entityManager->getRepository(Group::class)->findOneBy(
                            ['name' => $this->request->getParsedBody()['name'] ?? null]
                        )
                    ) {
                        return true;
                    }

                    if ($group->getName() === $this->group->getName()) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->textarea('description', 'Описание группы');


        if ($this->group->getId() === User::ADMIN_GROUP_ID) {
            $form->header('Группа имеет все привилегии (доступ ко всему)');
        } else {
            $form->header('Права доступа');

            $i = 0;
            $aclsForCheckbox = $this->ACList->getArrayForCheckboxForm();
            foreach ($aclsForCheckbox as $label => $item) {
                $fill = array_map(function ($i) {
                    if (str_contains($i[0], '@')) {
                        $i[0] = sprintf('<span class="font-italic">%s</span>', $i[0]);
                    }
                    return $i;
                }, $item);
                $form->checkbox(str_repeat(' ', $i++) . "acl", $label)->fill($fill);
            }
        }

        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    private function doAction(): void
    {
        $acls = $this->entityManager->getRepository(ACL::class)->findBy(
            ['id' => $this->request->getParsedBody()['acl'] ?? []]
        );


        $this->group->setName($this->request->getParsedBody()['name'] ?? '');
        $this->group->setDescription($this->request->getParsedBody()['description'] ?? '');

        $this->group->removeAcl();

        foreach ($acls as $acl) {
            $this->group->setAcl($acl);
        }

        $this->entityManager->flush();
    }

    public function getGroup(): Group
    {
        return $this->group;
    }


}
