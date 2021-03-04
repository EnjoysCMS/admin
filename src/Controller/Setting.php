<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;

class Setting extends BaseController
{
    public function setting()
    {
        return $this->view('@a/setting/setting.twig', $this->getContext(
            new \App\Module\Admin\Core\Setting()
        ));
    }
}