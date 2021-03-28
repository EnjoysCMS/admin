<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use EnjoysCMS\Core\Components\Widgets\WidgetsTwigExtension;
use EnjoysCMS\Core\Entities\Widgets;

class Index extends BaseController
{

    public function dashboard(): string
    {
        $this->twig->addExtension(
            new WidgetsTwigExtension(
                $this->entityManager,
                $this->twig
            )
        );

        return $this->twig->render(
            '@a/dashboard/dashboard.twig',
            [
                'title' => 'Dashboard | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename'),
                'widgets' => $this->entityManager->getRepository(Widgets::class)->getSortWidgets()
            ]
        );
    }


}
