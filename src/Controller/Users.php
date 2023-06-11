<?php


namespace EnjoysCMS\Module\Admin\Controller;


use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Users\Add;
use EnjoysCMS\Module\Admin\Core\Users\ChangePassword;
use EnjoysCMS\Module\Admin\Core\Users\Delete;
use EnjoysCMS\Module\Admin\Core\Users\Edit;
use EnjoysCMS\Module\Admin\Core\Users\UsersList;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Users extends AdminBaseController
{

    #[Route(
        path: '/admin/users/list',
        name: 'admin/users',
        options: [
            'comment' => 'Доступ к просмотру списка пользователей'
        ]
    )]
    public function list(UsersList $usersList): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/users/users-list.twig',
                $usersList->getContext()
            )
        );
    }

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
        return $this->response(
            $this->twig->render(
                '@a/users/edituser.twig',
                $edit->getContext()
            )
        );
    }

    #[Route(
        path: '/admin/users/add',
        name: 'admin/adduser',
        options: [
            'comment' => 'Добавление пользователей'
        ]
    )]
    public function add(Add $add): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/users/adduser.twig',
                $add->getContext()
            )
        );
    }


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
        return $this->response(
            $this->twig->render(
                '@a/users/deleteuser.twig',
                $delete->getContext()
            )
        );
    }

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
