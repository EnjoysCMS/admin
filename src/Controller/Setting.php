<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Settings\AddSetting;
use App\Module\Admin\Core\Settings\DeleteSetting;
use App\Module\Admin\Core\Settings\EditSetting;
use Symfony\Component\Routing\Annotation\Route;

class Setting extends BaseController
{

    public function setting()
    {
        return $this->view(
            '@a/setting/setting.twig',
            $this->getContext($this->getContainer()->get(\App\Module\Admin\Core\Settings\Setting::class))
        );
    }

    /**
     * @Route(
     *     path="/admin/setting/add",
     *     name="admin/setting/add",
     *     options={
     *          "aclComment": "Добаление глобальной настройки"
     *     }
     * )
     */
    public function addSetting()
    {
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(AddSetting::class))
        );
    }

    /**
     * @Route(
     *     path="/admin/setting/edit",
     *     name="admin/setting/edit",
     *     options={
     *          "aclComment": "Изменение глобальной настройки"
     *     }
     * )
     */
    public function editSetting()
    {
        return $this->view(
            '@a/setting/add.twig',
            $this->getContext($this->getContainer()->get(EditSetting::class))
        );
    }

    /**
     * @Route(
     *     path="/admin/setting/delete",
     *     name="admin/setting/delete",
     *     options={
     *          "aclComment": "Удаление глобальной настройки"
     *     }
     * )
     */
    public function deleteSetting()
    {
        $this->getContainer()->get(DeleteSetting::class)();
    }
}
