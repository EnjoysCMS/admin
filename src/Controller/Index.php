<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;

class Index extends BaseController
{

    public function dashboard(): string
    {
        return $this->twig->render('@a/dashboard/dashboard.twig', [
            'title' => 'Dashboard | Admin | ' . \App\Components\Helpers\Setting::get('sitename')
        ]);
    }




}
