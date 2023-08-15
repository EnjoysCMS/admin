<?php


namespace EnjoysCMS\Module\Admin\Settings;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\Admin\Config;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/setting', name: '@admin_setting_')]
class Controller extends AdminController
{

    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        name: 'manage',
        comment: 'Настройки сайта'
    )]
    public function setting(Setting $setting, Config $config): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Глобальные настройки сайта');

        $form = $setting->getForm();
        if ($form->isSubmitted()) {
            $setting->doAction();
            return $this->redirect->toRoute('@admin_setting_manage');
        }
        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/setting/setting.twig',
                [
                    'form' => $rendererForm,
                    '_title' => 'Настройки | Admin | ' . $this->setting->get('sitename')
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
        comment: 'Добавление глобальной настройки'
    )]
    public function add(Add $add, Config $config): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_setting_manage', 'Глобальные параметры сайта')
            ->setLastBreadcrumb('Добавление нового глобального параметра');

        $form = $add->getForm();
        if ($form->isSubmitted()) {
            $add->doAction();
            return $this->redirect->toRoute('@admin_setting_manage');
        }
        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/setting/add.twig',
                [
                    'form' => $rendererForm,
                    '_title' => 'Добавление настройки | Настройки | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }


    /**
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[Route('/edit',
        name: 'edit',
        comment: 'Изменение глобальной настройки'
    )]
    public function edit(Edit $edit, Config $config): ResponseInterface
    {
        $this->breadcrumbs->add('@admin_setting_manage', 'Глобальные параметры сайта')
            ->setLastBreadcrumb(sprintf('Редактирование параметра `%s`', $edit->getSettingEntity()->getName()));

        $form = $edit->getForm();
        if ($form->isSubmitted()) {
            $edit->doAction();
            return $this->redirect->toRoute('@admin_setting_manage');
        }
        $rendererForm = $config->getRendererForm();
        $rendererForm->setForm($form);

        return $this->response(
            $this->twig->render(
                '@a/setting/add.twig',
                [
                    'form' => $rendererForm,
                    '_title' => 'Изменение настройки | Настройки | Admin | ' . $this->setting->get('sitename'),
                ]
            )
        );
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NoResultException
     * @throws CannotRemoveEntity
     */
    #[Route('/delete',
        name: 'delete',
        comment: 'Удаление глобальной настройки'
    )]
    public function delete(EntityManager $em): ResponseInterface
    {
        $setting = $em->getRepository(\EnjoysCMS\Core\Setting\Entity\Setting::class)->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();


        if (!$setting->isRemovable()) {
            throw new CannotRemoveEntity('This the setting not removable');
        }


        $em->remove($setting);
        $em->flush();

        return $this->redirect->toRoute('@admin_setting_manage');
    }
}
