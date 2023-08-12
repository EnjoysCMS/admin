<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


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
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Exception\NotEditableUser;
use Psr\Http\Message\ServerRequestInterface;

class Edit
{

    private User $user;
    private EntityRepository $usersRepository;


    /**
     * @throws NoResultException
     * @throws NotEditableUser
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly RedirectInterface $redirect,
        private readonly Setting $setting,
    ) {
        $this->usersRepository = $this->em->getRepository(User::class);
        $this->user = $this->getUser();
    }

    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function getUser(): User
    {
        $user = $this->usersRepository->find(
            $this->request->getAttribute('id')
        );

        if ($user === null) {
            throw new NoResultException();
        }

        if (!$user->isEditable()) {
            throw new NotEditableUser('User is not editable');
        }

        return $user;
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
            $this->editUser();
            $this->redirect->toRoute('@admin_users_list');
        }

        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer,
            'username' => $this->user->getLogin(),
            'user' => $this->user,
            '_title' => 'Редактирование пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
        ];
    }

    /**
     * @throws ExceptionRule
     * @throws NotSupported
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(
            [
                'name' => $this->user->getName(),
                'login' => $this->user->getLogin(),
                'groups' => $this->user->getGroupIds()
            ]
        );
        $form->text('name', 'Имя')->addRule(Rules::REQUIRED);
        $form->text('login', 'Логин')
            ->addRule(
                Rules::CALLBACK,
                'Такой логин уже занят',
                function () {
                    if (null === $user = $this->em->getRepository(User::class)->findOneBy(
                            ['login' => $this->request->getParsedBody()['login'] ?? null]
                        )
                    ) {
                        return true;
                    }

                    if ($user->getLogin() === $this->user->getLogin()) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);

        $form->checkbox('groups', 'Группа')
            ->addRule(
                Rules::CALLBACK,
                'Т.к. больше нет администраторов, то у этого пользователя нельзя убрать права администратора',
                function () {
                    if (!$this->user->isAdmin()) {
                        return true;
                    }

                    if (in_array(
                        User::ADMIN_GROUP_ID,
                        $this->request->getParsedBody()['groups'] ?? []
                    )
                    ) {
                        return true;
                    }

                    $total_admins = $this->usersRepository->createQueryBuilder('u')
                        ->select('COUNT(u) as cnt')
                        ->join('u.groups', 'g')
                        ->where('g.id = :id')
                        ->setParameter('id', User::ADMIN_GROUP_ID)
                        ->getQuery()
                        ->getSingleResult()['cnt'];

                    if ($total_admins - 1 >= 1) {
                        return true;
                    }

                    return false;
                }
            )
            ->addRule(Rules::REQUIRED)
            ->fill($this->getGroupsArray());

        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

    /**
     * @throws NotSupported
     */
    private function getGroupsArray(): array
    {
        $groupsArray = [];
        $groups = $this->em->getRepository(Group::class)->findAll();
        foreach ($groups as $group) {
            $groupsArray[$group->getId() . ' '] = $group->getName();
        }
        return $groupsArray;
    }

    /**
     * @throws NotSupported
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function editUser(): void
    {
        $this->user->setName($this->request->getParsedBody()['name'] ?? null);
        $this->user->setLogin($this->request->getParsedBody()['login'] ?? null);


        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );

        $this->user->removeGroups();

        foreach ($groups as $group) {
            $this->user->setGroups($group);
        }

        $this->em->flush();
    }


}
