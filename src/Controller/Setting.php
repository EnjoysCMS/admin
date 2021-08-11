<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Settings\AddSetting;
use App\Module\Admin\Core\Settings\DeleteSetting;
use App\Module\Admin\Core\Settings\EditSetting;
use Symfony\Component\Routing\Annotation\Route;

class Setting extends BaseController
{


    #[Route(
        path: '/admin/setting',
        name: 'admin/setting',
        options: [
            'aclComment' => 'Настройки сайта'
        ]
    )]
    public function setting(): string
    {
        return $this->view(
            '@a/setting/setting.twig',
            $this->getContext($this->getContainer()->get(\App\Module\Admin\Core\Settings\Setting::class))
        );
    }


    #[Route(
        path: '/admin/setting/add',
        name: 'admin/setting/add',
        options: [
            'aclComment' => 'Добавление глобальной настройки'
        ]
    )]
    public function addSetting(): string
    {
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(AddSetting::class))
        );
    }


    #[Route(
        path: '/admin/setting/edit',
        name: 'admin/setting/edit',
        options: [
            'aclComment' => 'Изменение глобальной настройки'
        ]
    )]
    public function editSetting(): string
    {
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(EditSetting::class))
        );
    }


    #[Route(
        path: '/admin/setting/delete',
        name: 'admin/setting/delete',
        options: [
            'aclComment' => 'Удаление глобальной настройки'
        ]
    )]
    public function deleteSetting()
    {
        $this->getContainer()->get(DeleteSetting::class)();
    }
}
