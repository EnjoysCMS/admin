<?php


namespace EnjoysCMS\Module\Admin\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Core\Users\Entity\User;
use Psr\Http\Message\ServerRequestInterface;

class Edit
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
        private readonly ACList $ACList,
        private readonly AccessControl $accessControl,
    ) {
        $this->groupsRepository = $this->entityManager->getRepository(Group::class);

        $this->group = $this->groupsRepository->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();
    }


    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     * @throws NotSupported
     */
    public function getForm(): Form
    {
        $form = new Form();


        $form->setDefaults(
            [
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription(),
                'acl' => array_map(
                    function ($o): int {
                        return $o->getId();
                    },
                    $this->accessControl->getManage()->getAccessActionsForGroup($this->group)
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
    public function doAction(): void
    {
        $this->group->setName($this->request->getParsedBody()['name'] ?? '');
        $this->group->setDescription($this->request->getParsedBody()['description'] ?? '');

//dd($acls, $this->request->getParsedBody()['acl'] ?? []);
        foreach ($this->accessControl->getManage()->getList() as $acl) {
            if (in_array($acl->getId(), $this->request->getParsedBody()['acl'] ?? [])) {
                $acl->addGroup($this->group);
                continue;
            }
            $acl->removeGroup($this->group);
        }


        $this->entityManager->flush();
    }

    public function getGroup(): Group
    {
        return $this->group;
    }


}
