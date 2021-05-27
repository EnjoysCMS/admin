<?php


namespace App\Module\Admin\Controller;


use EnjoysCMS\Core\Components\Helpers\Assets;
use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Users\Add;
use App\Module\Admin\Core\Users\ChangePassword;
use App\Module\Admin\Core\Users\Delete;
use App\Module\Admin\Core\Users\Edit;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Users extends BaseController
{

    /**
     * @var ObjectRepository
     */
    private $usersRepository;

    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->usersRepository = $entityManager->getRepository(\EnjoysCMS\Core\Entities\Users::class);
    }


    public function list(): string
    {
        Assets::css(
            [
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css',
            ]
        );
        Assets::js(
            [
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables/jquery.dataTables.min.js',
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables-responsive/js/dataTables.responsive.min.js',
                __DIR__. '/../../node_modules/admin-lte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
            ]
        );

        return $this->view(
            '@a/users/users-list.twig',
            [
                'users' => $this->usersRepository->findAll(),
                '_title' => 'Пользователи | Admin | ' . \EnjoysCMS\Core\Components\Helpers\Setting::get('sitename')
            ]
        );
    }

    public function edit(): string
    {
        return $this->view(
            '@a/users/edituser.twig',
            $this->getContext(
                new Edit($this->entityManager, $this->serverRequest, $this->urlGenerator, $this->usersRepository)
            )
        );
    }

    public function add(): string
    {
        return $this->view(
            '@a/users/adduser.twig',
            $this->getContext(
                new Add($this->entityManager, $this->serverRequest, $this->urlGenerator, $this->usersRepository)
            )
        );
    }


    public function delete(): string
    {
        return $this->view(
            '@a/users/deleteuser.twig',
            $this->getContext(
                new Delete(
                    $this->entityManager,
                    $this->serverRequest,
                    $this->urlGenerator,
                    $this->usersRepository,
                    $this->renderer
                )
            )
        );
    }

    public function changePassword(): string
    {
        return $this->view(
            '@a/users/change-password.twig',
            $this->getContext(
                new ChangePassword(
                    $this->entityManager,
                    $this->serverRequest,
                    $this->urlGenerator,
                    $this->usersRepository,
                    $this->renderer
                )
            )
        );
    }

}
