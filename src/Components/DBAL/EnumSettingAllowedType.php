<?php


namespace App\Module\Admin\Components\DBAL;


use App\Components\Doctrine\EnumType;

class EnumSettingAllowedType extends EnumType
{
    protected $name = 'allowedSettingType';
    protected $values = ['text', 'select', 'textarea', 'radio'];
}