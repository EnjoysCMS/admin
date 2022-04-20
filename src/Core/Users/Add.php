<?php


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Entities\User;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Add implements ModelInterface
{
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
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
        $newUser->setName($this->requestWrapper->getPostData('name'));
        $newUser->setLogin($this->requestWrapper->getPostData('login'));
        $newUser->genAdnSetPasswordHash($this->requestWrapper->getPostData('password'));

        $groups = $this->em->getRepository(Group::class)->findBy(
            ['id' => $this->requestWrapper->getPostData('groups', [])]
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
        $form->text('name', 'Имя')->addRule(Rules::REQUIRED);
        $form->text('login', 'Логин')
            ->addRule(
                Rules::CALLBACK,
                'Такой логин уже занят',
                function () {
                    if (null === $this->em->getRepository(User::class)->findOneBy(
                        ['login' => $this->requestWrapper->getPostData('login')]
                    )
                    ) {
                        return true;
                    }
                    return false;
                }
            )->addRule(Rules::REQUIRED);
        $form->text('password', 'Пароль')->addRule(Rules::REQUIRED);


        $form->checkbox('groups', 'Группа')->fill(
            $this->em->getRepository(Group::class)->getGroupsArray()
        )->addRule(Rules::REQUIRED);

        $form->submit('sbmt1', 'Добавить');

        return $form;
    }

}
