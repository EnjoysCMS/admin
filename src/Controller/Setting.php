<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Settings\AddSetting;
use EnjoysCMS\Module\Admin\Core\Settings\DeleteSetting;
use EnjoysCMS\Module\Admin\Core\Settings\EditSetting;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Setting extends AdminBaseController
{


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(
        path: '/admin/setting',
        name: 'admin/setting',
        options: [
            'comment' => 'Настройки сайта'
        ]
    )]
    public function setting(\EnjoysCMS\Module\Admin\Core\Settings\Setting $setting): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Глобальные настройки сайта');

        return $this->response(
            $this->twig->render(
                '@a/setting/setting.twig',
                $setting->getContext()
            )
        );
    }


    #[Route(
        path: '/admin/setting/add',
        name: 'admin/setting/add',
        options: [
            'comment' => 'Добавление глобальной настройки'
        ]
    )]
    public function addSetting(AddSetting $addSetting): ResponseInterface
    {
        $this->breadcrumbs->add('admin/setting', 'Глобальные параметры сайта')
            ->setLastBreadcrumb('Добавление нового глобального параметра');
        return $this->response(
            $this->twig->render(
                '@a/setting/add.twig',
                $addSetting->getContext()
            )
        );
    }


    /**
     * @throws ExceptionRule
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(
        path: '/admin/setting/edit',
        name: 'admin/setting/edit',
        options: [
            'comment' => 'Изменение глобальной настройки'
        ]
    )]
    public function editSetting(EditSetting $editSetting): ResponseInterface
    {
        $this->breadcrumbs->add('admin/setting', 'Глобальные параметры сайта')
            ->setLastBreadcrumb(sprintf('Редактирование параметра `%s`', $editSetting->getSettingEntity()->getName()));
        return $this->response(
            $this->twig->render(
                '@a/setting/add.twig',
                $editSetting->getContext()
            )
        );
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NoResultException
     * @throws CannotRemoveEntity
     */
    #[Route(
        path: '/admin/setting/delete',
        name: 'admin/setting/delete',
        options: [
            'comment' => 'Удаление глобальной настройки'
        ]
    )]
    public function deleteSetting(DeleteSetting $deleteSetting): ResponseInterface
    {
        return $deleteSetting();
    }
}
