<?php


namespace EnjoysCMS\Module\Admin\Controller;


use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\Admin\Core\Settings\AddSetting;
use EnjoysCMS\Module\Admin\Core\Settings\DeleteSetting;
use EnjoysCMS\Module\Admin\Core\Settings\EditSetting;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

class Setting extends AdminBaseController
{


    #[Route(
        path: '/admin/setting',
        name: 'admin/setting',
        options: [
            'comment' => 'Настройки сайта'
        ]
    )]
    public function setting(): ResponseInterface
    {
        return $this->responseText($this->view(
            '@a/setting/setting.twig',
            $this->getContext($this->getContainer()->get(\EnjoysCMS\Module\Admin\Core\Settings\Setting::class))
        ));
    }


    #[Route(
        path: '/admin/setting/add',
        name: 'admin/setting/add',
        options: [
            'comment' => 'Добавление глобальной настройки'
        ]
    )]
    public function addSetting(): ResponseInterface
    {
        return $this->responseText($this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(AddSetting::class))
        ));
    }


    #[Route(
        path: '/admin/setting/edit',
        name: 'admin/setting/edit',
        options: [
            'comment' => 'Изменение глобальной настройки'
        ]
    )]
    public function editSetting(): ResponseInterface
    {
        return $this->responseText($this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(EditSetting::class))
        ));
    }


    #[Route(
        path: '/admin/setting/delete',
        name: 'admin/setting/delete',
        options: [
            'comment' => 'Удаление глобальной настройки'
        ]
    )]
    public function deleteSetting(): void
    {
        $this->getContainer()->get(DeleteSetting::class)();
    }
}
