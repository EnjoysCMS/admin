<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GroupsList implements ModelInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    public function __construct(private EntityManager $em, private UrlGeneratorInterface $urlGenerator)
    {
        $this->groupsRepository = $this->em->getRepository(Group::class);
    }


    public function getContext(): array
    {
        return [
            'groups' => $this->groupsRepository->findAll(),
            '_title' => 'Группы | Admin | ' . Setting::get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                'Список групп пользователей',
            ],
        ];
    }
}
