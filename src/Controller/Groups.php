<?php


namespace EnjoysCMS\Module\Admin\Controller;


use EnjoysCMS\Module\Admin\BaseController;
use EnjoysCMS\Module\Admin\Core\Groups\Add;
use EnjoysCMS\Module\Admin\Core\Groups\Delete;
use EnjoysCMS\Module\Admin\Core\Groups\Edit;
use EnjoysCMS\Module\Admin\Core\Groups\GroupsList;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;


class Groups extends BaseController
{

    #[Route(
        path: '/admin/groups',
        name: 'admin/groups',
        options: [
            'comment' => 'Доступ к просмотру списка групп'
        ]
    )]
    public function list(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/groups/list.twig',
                $this->getContext($this->getContainer()->get(GroupsList::class))
            )
        );
    }


    #[Route(
        path: '/admin/groups/edit/{id}',
        name: 'admin/editgroup',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'comment' => 'Редактирование групп пользователей'
        ]
    )]
    public function edit(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/groups/edit.twig',
                $this->getContext($this->getContainer()->get(Edit::class))
            )
        );
    }

    #[Route(
        path: '/admin/groups/add',
        name: 'admin/addgroup',
        options: [
            'comment' => 'Добаление групп пользователей'
        ]
    )]
    public function add(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/groups/add.twig',
                $this->getContext($this->getContainer()->get(Add::class))
            )
        );
    }

    #[Route(
        path: '/admin/deletegroups@{id}',
        name: 'admin/deletegroup',
        requirements: [
            'id' => '\d+'
        ],
        options: [
            'comment' => 'Удаление групп пользователей'
        ]
    )]
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@a/groups/delete.twig',
                $this->getContext($this->getContainer()->get(Delete::class))
            )
        );
    }

}
