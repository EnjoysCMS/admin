<?php


namespace App\Modules\Admin\Controller;


use App\Components\Helpers\Assets;
use App\Modules\Admin\BaseController;
use App\Modules\Admin\Core\ModelInterface;
use App\Modules\Admin\Core\Users\Add;
use App\Modules\Admin\Core\Users\ChangePassword;
use App\Modules\Admin\Core\Users\Delete;
use App\Modules\Admin\Core\Users\Edit;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Users extends BaseController
{

    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    private $usersRepository;
    /**
     * @var RendererInterface
     */
    private RendererInterface $renderer;


    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $em,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $em, $urlGenerator);
        $this->usersRepository = $em->getRepository(\App\Entities\Users::class);
        $this->renderer = $renderer;
    }


    public function list(): string
    {
        Assets::css(
            [
                $_ENV['ADMINLTE'] . '/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
                $_ENV['ADMINLTE'] . '/plugins/datatables-responsive/css/responsive.bootstrap4.min.css',
            ]
        );
        Assets::js(
            [
                $_ENV['ADMINLTE'] . '/plugins/datatables/jquery.dataTables.min.js',
                $_ENV['ADMINLTE'] . '/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
                $_ENV['ADMINLTE'] . '/plugins/datatables-responsive/js/dataTables.responsive.min.js',
                $_ENV['ADMINLTE'] . '/plugins/datatables-responsive/js/responsive.bootstrap4.min.js',
            ]
        );

        return $this->view(
            '@a/users/users-list.twig',
            [
                'users' => $this->usersRepository->findAll()
            ]
        );
    }

    public function edit(): string
    {
        return $this->view(
            '@a/users/edituser.twig',
            $this->getContext(new Edit($this->em, $this->serverRequest, $this->urlGenerator, $this->usersRepository))
        );
    }

    public function add(): string
    {
        return $this->view(
            '@a/users/adduser.twig',
            $this->getContext(
                new Add($this->em, $this->serverRequest, $this->urlGenerator, $this->usersRepository)
            )
        );
    }


    public function delete(): string
    {
        return $this->view(
            '@a/users/deleteuser.twig',
            $this->getContext(
                new Delete(
                    $this->em, $this->serverRequest, $this->urlGenerator, $this->usersRepository, $this->renderer
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
                    $this->em,
                    $this->serverRequest,
                    $this->urlGenerator,
                    $this->usersRepository,
                    $this->renderer
                )
            )
        );
    }

    private function getContext(ModelInterface $model)
    {
        return $model->getContext();
    }

    private function view(string $twigTemplatePath, array $context)
    {
        return $this->twig->render($twigTemplatePath, $context);
    }
}
