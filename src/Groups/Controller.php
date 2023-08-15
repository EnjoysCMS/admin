<?php


namespace EnjoysCMS\Module\Admin\Groups;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\Admin\Config;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


#[Route('/admin/users/groups', '@admin_groups_')]
class Controller extends AdminController
{

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotSupported
     */
    #[Route(
        name: 'list',
        comment: 'Доступ к просмотру списка групп'
    )]
    public function list(EntityManager $em): ResponseInterface
    {
        $this->breadcrumbs
            ->setLastBreadcrumb('Группы пользователей');

        return $this->response(
            $this->twig->render(
                '@a/groups/list.twig',
                [
                    'groups' => $em->getRepository(Group::class)->findAll(),
                    '_title' => 'Группы | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }


    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/edit/{id}',
        name: 'edit',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Редактирование групп пользователей'
    )]
    public function edit(Edit $edit, Config $config): ResponseInterface
    {
        $this->breadcrumbs
            ->add('@admin_groups_list', 'Группы пользователей')
            ->setLastBreadcrumb(sprintf('Редактирование группы "%s"', $edit->getGroup()->getName()));

        $form = $edit->getForm();

        if ($form->isSubmitted()) {
            $edit->doAction();
            return $this->redirect->toRoute('@admin_groups_list');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/groups/edit.twig',
                [
                    'form' => $rendererForm,
                    '_title' => 'Редактирование группы | Группы | Admin | ' . $this->setting->get('sitename')
                ]
            )
        );
    }

    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/add',
        name: 'add',
        comment: 'Добавление групп пользователей'
    )]
    public function add(Add $add, Config $config): ResponseInterface
    {
        $this->breadcrumbs
            ->add('@admin_groups_list', 'Группы пользователей')
            ->setLastBreadcrumb('Добавить новую группу');

        $form = $add->getForm();

        if ($form->isSubmitted()) {
            $add->doAction();
            return $this->redirect->toRoute('@admin_groups_list');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/groups/add.twig',
                [
                    'form' => $rendererForm,
                    '_title' => 'Добавление группы | Группы | Admin | ' . $this->setting->get('sitename')
                ]
            )
        );
    }

    /**
     * @throws LoaderError
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Route('/delete@{id}',
        name: 'delete',
        requirements: [
            'id' => '\d+'
        ],
        comment: 'Удаление групп пользователей'
    )]
    public function delete(Delete $delete, Config $config): ResponseInterface
    {
        $this->breadcrumbs
            ->add('@admin_groups_list', 'Группы пользователей')
            ->setLastBreadcrumb('Удаление группы');

        $form = $delete->getForm();

        if ($form->isSubmitted()) {
            $delete->doAction();
            return $this->redirect->toRoute('@admin_groups_list');
        }

        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/groups/delete.twig',
                [
                    'form' => $rendererForm,
                    'group' => $delete->getGroup(),
                    '_title' => 'Удаление группы | Группы | Admin | ' . $this->setting->get('sitename')
                ]
            )
        );
    }

}
