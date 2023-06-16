<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Users\Add;
use EnjoysCMS\Module\Admin\Core\Users\ChangePassword;
use EnjoysCMS\Module\Admin\Core\Users\Delete;
use EnjoysCMS\Module\Admin\Core\Users\Edit;
use EnjoysCMS\Module\Admin\Core\Users\UsersList;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Users extends AdminBaseController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws NotSupported
     * @throws LoaderError
     */
    #[Route(
        path: '/admin/users/list',
        name: 'admin/users',
        options: [
            'comment' => 'Доступ к просмотру списка пользователей'
        ]
    )]
    public function list(UsersList $usersList): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Список пользователей');
        return $this->response(
            $this->twig->render(
                '@a/users/users-list.twig',
                $usersList->getContext()
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
    #[Route(
        path: '/admin/users/edit/@{id}',
        name: 'admin/edituser',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'comment' => 'Редактирование пользователей'
        ]
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        $this->breadcrumbs->add('admin/users', 'Список пользователей')
            ->setLastBreadcrumb('Редактирование пользователя');
        return $this->response(
            $this->twig->render(
                '@a/users/edituser.twig',
                $edit->getContext()
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
    #[Route(
        path: '/admin/users/add',
        name: 'admin/adduser',
        options: [
            'comment' => 'Добавление пользователей'
        ]
    )]
    public function add(Add $add): ResponseInterface
    {
        $this->breadcrumbs->add('admin/users', 'Список пользователей')
            ->setLastBreadcrumb('Добавить нового пользователя');
        return $this->response(
            $this->twig->render(
                '@a/users/adduser.twig',
                $add->getContext()
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
    #[Route(
        path: '/admin/users/delete/{id}',
        name: 'admin/deleteuser',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'comment' => 'Удаление пользователей'
        ]
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        $this->breadcrumbs->add('admin/users', 'Список пользователей')
            ->setLastBreadcrumb('Удаление пользователя');
        return $this->response(
            $this->twig->render(
                '@a/users/deleteuser.twig',
                $delete->getContext()
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
    #[Route(
        path: '/admin/users/changepassword@{id}',
        name: 'admin/user/changepassword',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'comment' => 'Изменение паролей у пользователей'
        ]
    )]
    public function changePassword(ChangePassword $changePassword): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/users/change-password.twig',
                $changePassword->getContext()
            )
        );
    }

}
