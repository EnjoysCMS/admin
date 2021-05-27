<?php


namespace App\Module\Admin\Core\Users;


use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use App\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Core\Entities\Groups;
use EnjoysCMS\Core\Entities\Users;
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
    private EntityManager $entityManager;
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
        $this->entityManager = $em;
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
            '_title' => 'Добавление пользователя | Пользователи | Admin | ' . Setting::get(
                'sitename'
            )
        ];
    }

    private function addUser()
    {
        $newUser = new Users();
        $newUser->setName($this->serverRequest->post('name'));
        $newUser->setLogin($this->serverRequest->post('login'));
        $newUser->genAdnSetPasswordHash($this->serverRequest->post('password'));

        $groups = $this->entityManager->getRepository(Groups::class)->findBy(
            ['id' => $this->serverRequest->post('groups', [])]
        );
        foreach ($groups as $group) {
            $newUser->setGroups($group);
        }

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

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
                    if (null === $this->entityManager->getRepository(Users::class)->findOneBy(
                        ['login' => $this->serverRequest->post('login')]
                    )
                    ) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);
        $form->text('password', 'Пароль')->addRule(Rules::REQUIRED);


        $form->checkbox('groups', 'Группа')->fill(
            $this->entityManager->getRepository(Groups::class)->getGroupsArray()
        )->addRule(Rules::REQUIRED);

        $form->submit('sbmt1', 'Добавить');

        return $form;
    }

}
