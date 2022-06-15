<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Widgets;


use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getContext(): array
    {
        return [
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('admin/managewidgets') => 'Менеджер виджетов',
                'Редактирование виджета',
            ],
        ];
    }
}
