<?php


namespace App\Module\Admin\Core\Users;


use App\Components\Helpers\Redirect;
use App\Module\Admin\Core\ModelInterface;
use App\Entities\Groups;
use App\Entities\Users;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{
    /**
     * @var EntityManager
     */
    private EntityManager $em;
    /**
     * @var ServerRequestInterface
     */
    private ServerRequestInterface $serverRequest;
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    private ObjectRepository $usersRepository;

    public function __construct(
        EntityManager $em,
        ServerRequestInterface $serverRequest,
        UrlGeneratorInterface $urlGenerator,
        ObjectRepository $usersRepository
    ) {
        $this->em = $em;
        $this->serverRequest = $serverRequest;
        $this->urlGenerator = $urlGenerator;
        $this->usersRepository = $usersRepository;
    }


    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->addUser();
        }

        return [
            'form' => new Bootstrap4([], $form),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/users') => 'Список пользователей',
                'Добавить нового пользователя'
            ],
            'title' => 'Добавление пользователя | Пользователи | Admin | ' . \App\Components\Helpers\Setting::get('sitename')
        ];
    }

    private function addUser()
    {
        $newUser = new Users();
        $newUser->setName($this->serverRequest->post('name'));
        $newUser->setLogin($this->serverRequest->post('login'));
        $newUser->genAdnSetPasswordHash($this->serverRequest->post('password'));

        $groups = $this->em->getRepository(Groups::class)->findBy(
            ['id' => $this->serverRequest->post('groups', [])]
        );
        foreach ($groups as $group) {
            $newUser->setGroups($group);
        }

        $this->em->persist($newUser);
        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('admin/users'));
    }

    private function getForm(): Form
    {
        $form = new Form(
            [
                'method' => 'POST'
            ]
        );
        $form->setDefaults(['groups' => [2]]);
        $form->text('name', 'Имя')->addRule(Rules::REQUIRED);
        $form->text('login', 'Логин')
            ->addRule(
                Rules::CALLBACK,
                'Такой логин уже занят',
                function () {
                    if (null === $this->em->getRepository(Users::class)->findOneBy(
                            ['login' => $this->serverRequest->post('login')]
                        )) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);
        $form->text('password', 'Пароль')->addRule(Rules::REQUIRED);


        $form->checkbox('groups', 'Группа')->fill($this->getGroupsArray())->addRule(Rules::REQUIRED);

        $form->submit('sbmt1', 'Добавить');

        return $form;
    }

    private function getGroupsArray(): array
    {
        $groupsArray = [];
        $groups = $this->em->getRepository(Groups::class)->findAll();
        foreach ($groups as $group) {
            $groupsArray[$group->getId() . ' '] = $group->getName();
        }
        return $groupsArray;
    }
}
