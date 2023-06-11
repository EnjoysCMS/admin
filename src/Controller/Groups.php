<?php


namespace EnjoysCMS\Module\Admin\Controller;


use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Groups\Add;
use EnjoysCMS\Module\Admin\Core\Groups\Delete;
use EnjoysCMS\Module\Admin\Core\Groups\Edit;
use EnjoysCMS\Module\Admin\Core\Groups\GroupsList;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


class Groups extends AdminBaseController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/admin/groups',
        name: 'admin/groups',
        options: [
            'comment' => 'Доступ к просмотру списка групп'
        ]
    )]
    public function list(GroupsList $groupsList): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/groups/list.twig',
                $groupsList->getContext()
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
    public function edit(Edit $edit): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/groups/edit.twig',
                $edit->getContext()
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
    public function add(Add $add): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/groups/add.twig',
                $add->getContext()
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
    public function delete(Delete $delete): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                '@a/groups/delete.twig',
                $delete->getContext()
            )
        );
    }

}
