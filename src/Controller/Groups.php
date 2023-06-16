<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Groups\Add;
use EnjoysCMS\Module\Admin\Core\Groups\Delete;
use EnjoysCMS\Module\Admin\Core\Groups\Edit;
use EnjoysCMS\Module\Admin\Core\Groups\GroupsList;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @throws NotSupported
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
        $this->breadcrumbs
            ->setLastBreadcrumb('Группы пользователей');

        return $this->response(
            $this->twig->render(
                '@a/groups/list.twig',
                $groupsList->getContext()
            )
        );
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotSupported
     */
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
        $this->breadcrumbs
            ->add('admin/groups', 'Группы пользователей')
            ->setLastBreadcrumb(sprintf('Редактирование группы "%s"', $edit->getGroup()->getName()));

        return $this->response(
            $this->twig->render(
                '@a/groups/edit.twig',
                $edit->getContext()
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
        path: '/admin/groups/add',
        name: 'admin/addgroup',
        options: [
            'comment' => 'Добавление групп пользователей'
        ]
    )]
    public function add(Add $add): ResponseInterface
    {
        $this->breadcrumbs
            ->add('admin/groups', 'Группы пользователей')
            ->setLastBreadcrumb('Добавить новую группу');

        return $this->response(
            $this->twig->render(
                '@a/groups/add.twig',
                $add->getContext()
            )
        );
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
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
        $this->breadcrumbs
            ->add('admin/groups', 'Группы пользователей')
            ->setLastBreadcrumb('Удаление группы');

        return $this->response(
            $this->twig->render(
                '@a/groups/delete.twig',
                $delete->getContext()
            )
        );
    }

}
