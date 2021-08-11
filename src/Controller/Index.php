<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Widgets\WidgetsTwigExtension;
use EnjoysCMS\Core\Entities\Widget;

class Index extends BaseController
{

    public function dashboard(): string
    {
        $this->getTwig()->addExtension($this->getContainer()->get(WidgetsTwigExtension::class));

        return $this->view(
            '@a/dashboard/dashboard.twig',
            [
                '_title' => 'Dashboard | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
                'widgets' => $this->getContainer()->get(EntityManager::class)->getRepository(
                    Widget::class
                )->getSortWidgets()
            ]
        );
    }


}
