<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Widgets;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Widgets\AbstractWidgets;
use EnjoysCMS\Core\Entities\User;
use Symfony\Component\Yaml\Yaml;

final class RegisterUsers extends AbstractWidgets
{
    public static function getWidgetDefinitionFile(): string
    {
        return __DIR__ . '/../../widgets.yml';
    }

    public static function getMeta(): array
    {
        return Yaml::parseFile(static::getWidgetDefinitionFile())[static::class];
    }

    public function view(): string
    {
        return $this->twig->render('@a/widgets/template/register_users.twig', [
            'countUsers' => count($this->getContainer()->get(EntityManager::class)->getRepository(User::class)->findAll())
        ]);
    }
}
