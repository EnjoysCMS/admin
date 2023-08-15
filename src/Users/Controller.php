<?php


namespace EnjoysCMS\Module\Admin\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Core\Users\Events\AfterAddUserEvent;
use EnjoysCMS\Core\Users\Events\AfterChangePasswordUserEvent;
use EnjoysCMS\Core\Users\Events\AfterDeleteUserEvent;
use EnjoysCMS\Core\Users\Events\AfterEditUserEvent;
use EnjoysCMS\Core\Users\Events\BeforeChangePasswordUserEvent;
use EnjoysCMS\Core\Users\Events\BeforeDeleteUserEvent;
use EnjoysCMS\Core\Users\Events\BeforeEditUserEvent;
use EnjoysCMS\Module\Admin\AdminController;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/users', '@admin_users_')]
class Controller extends AdminController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws NotSupported
     * @throws LoaderError
     */
    #[Route(
        name: 'list',
        comment: 'Доступ к просмотру списка пользователей'
    )]
    public function list(EntityManager $em): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Список пользователей');

        return $this->response(
            $this->twig->render(
                '@a/users/users-list.twig',
                [
                    'users' => $em->getRepository(User::class)->findAll(),
                    '_title' => 'Пользователи | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }

    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotSupported
     */
    #[Route('/edit/@{id}',
        name: 'edit',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Редактирование пользователей'
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_users_list', 'Список пользователей')
            ->setLastBreadcrumb('Редактирование пользователя');

        $form = $edit->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new BeforeEditUserEvent($edit->getUser()));
            $edit->editUser();
            $this->dispatcher->dispatch(new AfterEditUserEvent($edit->getUser()));
            return $this->redirect->toRoute('@admin_users_list');
        }

        $this->renderer->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/users/edituser.twig',
                [
                    'form' => $this->renderer,
                    'username' => $edit->getUser()->getLogin(),
                    'user' => $edit->getUser(),
                    '_title' => 'Редактирование пользователя | Пользователи | Admin | ' . $this->setting->get(
                            'sitename'
                        ),
                ]
            )
        );
    }


    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotSupported
     */
    #[Route('/add',
        name: 'add',
        comment: 'Добавление пользователей'
    )]
    public function add(Add $add): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_users_list', 'Список пользователей')
            ->setLastBreadcrumb('Добавить нового пользователя');

        $form = $add->getForm();

        if ($form->isSubmitted()) {
            $user = $add->doAction();
            $this->dispatcher->dispatch(new AfterAddUserEvent($user));
            $this->redirect->toRoute('@admin_users_list', emit: true);
        }

        $this->renderer->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/users/adduser.twig',
                [
                    'form' => $this->renderer,
                    '_title' => 'Добавление пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }


    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotSupported
     */
    #[Route('/delete/{id}',
        name: 'delete',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Удаление пользователей'
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_users_list', 'Список пользователей')
            ->setLastBreadcrumb('Удаление пользователя');

        $form = $delete->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new BeforeDeleteUserEvent($delete->getUser()));
            $delete->doAction();
            $this->dispatcher->dispatch(new AfterDeleteUserEvent());
            return $this->redirect->toRoute('@admin_users_list');
        }

        $this->renderer->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/users/deleteuser.twig',
                [
                    'form' => $this->renderer,
                    'username' => $delete->getUser()->getLogin(),
                    'user' => $delete->getUser(),
                    '_title' => 'Удаление пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/changepassword@{id}',
        name: 'changepassword',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Изменение паролей у пользователей'
    )]
    public function changePassword(ChangePassword $changePassword): ResponseInterface
    {
        $form = $changePassword->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new BeforeChangePasswordUserEvent($changePassword->getUser()));
            $changePassword->doAction();
            $this->dispatcher->dispatch(new AfterChangePasswordUserEvent($changePassword->getUser()));
            return $this->redirect->toRoute('@admin_users_list');
        }

        $this->renderer->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/users/change-password.twig',
                [
                    '_title' => 'Смена пароля пользователя | Пользователи | Admin | ' . $this->setting->get('sitename'),
                    'form' => $this->renderer,
                    'username' => $changePassword->getUser()->getLogin(),
                    'user' => $changePassword->getUser()
                ]
            )
        );
    }

}
