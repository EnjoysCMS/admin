<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Users\Add;
use App\Module\Admin\Core\Users\ChangePassword;
use App\Module\Admin\Core\Users\Delete;
use App\Module\Admin\Core\Users\Edit;
use App\Module\Admin\Core\Users\UsersList;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Users extends BaseController
{

    #[Route(
        path: '/admin/users/list',
        name: 'admin/users',
        options: [
            'comment' => 'Доступ к просмотру списка пользователей'
        ]
    )]
    public function list(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/users/users-list.twig',
                $this->getContext($this->getContainer()->get(UsersList::class))
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
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/users/edituser.twig',
                $this->getContext($this->getContainer()->get(Edit::class))
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
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/users/adduser.twig',
                $this->getContext($this->getContainer()->get(Add::class))
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
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/users/deleteuser.twig',
                $this->getContext($this->getContainer()->get(Delete::class))
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
    public function changePassword(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/users/change-password.twig',
                $this->getContext($this->getContainer()->get(ChangePassword::class))
            )
        );
    }

}
