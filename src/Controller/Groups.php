<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Groups\Add;
use App\Module\Admin\Core\Groups\Delete;
use App\Module\Admin\Core\Groups\Edit;
use App\Module\Admin\Core\Groups\GroupsList;
use Symfony\Component\Routing\Annotation\Route;


class Groups extends BaseController
{

    #[Route(
        path: '/admin/groups',
        name: 'admin/groups',
        options: [
            'aclComment' => 'Доступ к просмотру списка групп'
        ]
    )]
    public function list(): string
    {
        return $this->view(
            '@a/groups/list.twig',
            $this->getContext($this->getContainer()->get(GroupsList::class))
        );
    }


    #[Route(
        path: '/admin/groups/edit/{id}',
        name: 'admin/editgroup',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Редактирование групп пользователей'
        ]
    )]
    public function edit(): string
    {
        return $this->view(
            '@a/groups/edit.twig',
            $this->getContext($this->getContainer()->get(Edit::class))
        );
    }

    #[Route(
        path: '/admin/groups/add',
        name: 'admin/addgroup',
        options: [
            'aclComment' => 'Добаление групп пользователей'
        ]
    )]
    public function add(): string
    {
        return $this->view(
            '@a/groups/add.twig',
            $this->getContext($this->getContainer()->get(Add::class))
        );
    }

    #[Route(
        path: '/admin/deletegroups@{id}',
        name: 'admin/deletegroup',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'aclComment' => 'Удаление групп пользователей'
        ]
    )]
    public function delete(): string
    {
        return $this->view(
            '@a/groups/delete.twig',
            $this->getContext($this->getContainer()->get(Delete::class))
        );
    }

}
