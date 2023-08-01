<?php


namespace EnjoysCMS\Module\Admin\Controller;


use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Settings\AddSetting;
use EnjoysCMS\Module\Admin\Core\Settings\DeleteSetting;
use EnjoysCMS\Module\Admin\Core\Settings\EditSetting;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/setting', name: 'admin/setting')]
class Setting extends AdminBaseController
{

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('', '', comment: 'Настройки сайта')]
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


    #[Route('/add',
        name: '/add',
        comment: 'Добавление глобальной настройки'
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
     * @throws ContainerExceptionInterface
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/edit',
        name: '/edit',
        comment: 'Изменение глобальной настройки'
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
    #[Route('/delete',
        name: '/delete',
        comment: 'Удаление глобальной настройки'
    )]
    public function deleteSetting(DeleteSetting $deleteSetting): ResponseInterface
    {
        return $deleteSetting();
    }
}
