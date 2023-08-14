<?php


namespace EnjoysCMS\Module\Admin\Settings;



use EnjoysCMS\Core\Extensions\Doctrine\EnumType;

class EnumSettingAllowedType extends EnumType
{
    protected $name = 'allowedSettingType';
    protected $values = ['text', 'select', 'textarea', 'radio'];
}
