<?php

declare(strict_types=1);


namespace App\Module\Admin\Core\Groups;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Entities\Group;

final class GroupsList implements ModelInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Core\Repositories\Group $groupsRepository;

    public function __construct(private EntityManager $em)
    {
        $this->groupsRepository = $this->em->getRepository(Group::class);
    }


    public function getContext(): array
    {
        return [
            'groups' => $this->groupsRepository->findAll(),
            '_title' => 'Группы | Admin | ' . Setting::get('sitename')
        ];
    }
}