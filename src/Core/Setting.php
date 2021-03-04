<?php


namespace App\Module\Admin\Core;


class Setting implements ModelInterface
{

    public function getContext(): array
    {
        return [
            'sitename' => \App\Components\Helpers\Setting::get('sitename'),
            'sitename2' => \App\Components\Helpers\Setting::get('yandex-metrica'),
        ];
    }
}