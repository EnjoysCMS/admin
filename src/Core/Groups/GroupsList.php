<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Groups;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\Group;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GroupsList implements ModelInterface
{

    private EntityRepository|\EnjoysCMS\Core\Users\Repository\Group $groupsRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly Setting $setting,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        $this->groupsRepository = $this->em->getRepository(Group::class);
    }


    /**
     * @throws NotSupported
     */
    public function getContext(): array
    {
        return [
            'groups' => $this->groupsRepository->findAll(),
            '_title' => 'Группы | Admin | ' . $this->setting->get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                'Список групп пользователей',
            ],
        ];
    }
}
