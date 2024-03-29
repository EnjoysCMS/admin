<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Entities\User;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer
    ) {
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
            $this->addUser();
        }

        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer,
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function addUser(): void
    {
        $newUser = new User();
        $newUser->setName($this->request->getParsedBody()['name'] ?? null);
        $newUser->setLogin($this->request->getParsedBody()['login'] ?? null);
        $newUser->genAdnSetPasswordHash($this->request->getParsedBody()['password'] ?? null);

        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->request->getParsedBody()['groups'] ?? []]
        );
        foreach ($groups as $group) {
            $newUser->setGroups($group);
        }

        $this->em->persist($newUser);
        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('admin/users'));
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults(['groups' => [2]]);
        $form->text('name', 'Имя')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(Rules::REQUIRED);
        $form->text('login', 'Логин')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(
                Rules::CALLBACK,
                'Такой логин уже занят',
                function () {
                    if (null === $this->em->getRepository(User::class)->findOneBy(
                            ['login' => $this->request->getParsedBody()['login'] ?? null]
                        )
                    ) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);
        $form->text('password', 'Пароль')
            ->setAttribute(AttributeFactory::create('autocomplete', 'off'))
            ->addRule(Rules::REQUIRED);


        $form->checkbox('groups', 'Группа')
            ->addRule(Rules::REQUIRED)
            ->fill(
            $this->em->getRepository(Group::class)->getGroupsArray()
        );

        $form->submit('sbmt1', 'Добавить');

        return $form;
    }

}
