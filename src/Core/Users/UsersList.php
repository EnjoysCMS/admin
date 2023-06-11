<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Users;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\AssetsCollector\Assets;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Core\Users\Entity\User;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UsersList implements ModelInterface
{

    private ObjectRepository|EntityRepository $usersRepository;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Assets $assets,
        private readonly Setting $setting,
    ) {
        $this->assets->add('css',
            [
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css',
            ]
        );
        $this->assets->add('js',
            [
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables/jquery.dataTables.min.js',
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables-responsive/js/dataTables.responsive.min.js',
                __DIR__ . '/../../../node_modules/admin-lte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
            ]
        );

        $this->usersRepository = $this->entityManager->getRepository(User::class);
    }

    public function getContext(): array
    {
        return [
            'users' => $this->usersRepository->findAll(),
            '_title' => 'Пользователи | Admin | ' . $this->setting->get('sitename'),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                'Список пользователей',
            ],
        ];
    }
}
