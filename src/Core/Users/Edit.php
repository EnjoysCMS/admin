<?php


namespace App\Module\Admin\Core\Users;


use App\Module\Admin\Core\ModelInterface;
use App\Module\Admin\Exception\NotEditableUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Entities\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Edit implements ModelInterface
{

    private User $user;
    private ObjectRepository|EntityRepository $usersRepository;


    /**
     * @throws NotEditableUser
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
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
            $this->requestWrapper->getRequest()->getAttribute('id')
        );

        if ($user === null) {
            throw new NoResultException();
        }

        if (!$user->isEditable()){
            throw new NotEditableUser('User is not editable');
        }

        return $user;
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->editUser();
        }

        return [
            'form' => new Bootstrap4([], $form),
            'username' => $this->user->getLogin(),
            'user' => $this->user,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/users') => 'Список пользователей',
                'Редактирование пользователя',
            ],
            '_title' => 'Редактирование пользователя | Пользователи | Admin | ' . Setting::get('sitename')
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'POST'
            ]
        );
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
                        ['login' => $this->requestWrapper->getPostData('login')]
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

        $form->checkbox('groups', 'Группа')->fill($this->getGroupsArray())
            ->addRule(
                Rules::CALLBACK,
                'Т.к. больше нет администраторов, то у этого пользователя нельзя убрать права администратора',
                function () {
                    if (!$this->user->isAdmin()) {
                        return true;
                    }

                    if (in_array(
                        User::ADMIN_GROUP_ID,
                        $this->requestWrapper->getPostData('groups', [])
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
            ->addRule(Rules::REQUIRED);

        $form->submit('sbmt1', 'Изменить');

        return $form;
    }

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
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function editUser(): void
    {
        $this->user->setName($this->requestWrapper->getPostData('name'));
        $this->user->setLogin($this->requestWrapper->getPostData('login'));


        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('groups', [])]
        );

        $this->user->removeGroups();

        foreach ($groups as $group) {
            $this->user->setGroups($group);
        }

        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('admin/users'));
    }



}
