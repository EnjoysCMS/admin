<?php


namespace App\Controller\Modules\Admin\Controller;


use App\Controller\Modules\Admin\BaseController;

class Index extends BaseController
{

    public function dashboard(): string
    {
        return $this->twig->render('@a/dashboard/dashboard.twig');
    }




}
