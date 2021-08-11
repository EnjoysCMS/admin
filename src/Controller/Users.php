<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\Core\Users\UsersList;
use EnjoysCMS\Core\Components\Helpers\Assets;
use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Users\Add;
use App\Module\Admin\Core\Users\ChangePassword;
use App\Module\Admin\Core\Users\Delete;
use App\Module\Admin\Core\Users\Edit;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Users extends BaseController
{

    #[Route(
        path: '/admin/users/list',
        name: 'admin/users',
        options: [
            'aclComment' => 'Доступ к просмотру списка пользователей'
        ]
    )]
    public function list(): string
    {
        return $this->view(
            '@a/users/users-list.twig',
            $this->getContext($this->getContainer()->get(UsersList::class))
        );
    }

    #[Route(
        path: '/admin/users/edit/@{id}',
        name: 'admin/edituser',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Редактирование пользователей'
        ]
    )]
    public function edit(): string
    {
        return $this->view(
            '@a/users/edituser.twig',
            $this->getContext($this->getContainer()->get(Edit::class))
        );
    }

    #[Route(
        path: '/admin/users/add',
        name: 'admin/adduser',
        options: [
            'aclComment' => 'Добавление пользователей'
        ]
    )]
    public function add(): string
    {
        return $this->view(
            '@a/users/adduser.twig',
            $this->getContext($this->getContainer()->get(Add::class))
        );
    }


    #[Route(
        path: '/admin/users/delete/{id}',
        name: 'admin/deleteuser',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Удаление пользователей'
        ]
    )]
    public function delete(): string
    {
        return $this->view(
            '@a/users/deleteuser.twig',
            $this->getContext($this->getContainer()->get(Delete::class))
        );
    }

    #[Route(
        path: '/admin/users/changepassword@{id}',
        name: 'admin/user/changepassword',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Изменение паролей у пользователей'
        ]
    )]
    public function changePassword(): string
    {
        return $this->view(
            '@a/users/change-password.twig',
            $this->getContext($this->getContainer()->get(ChangePassword::class))
        );
    }

}
