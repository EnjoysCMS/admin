<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\Core\Users\UsersList;
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
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Users extends BaseController
{

    public function list(): string
    {
        return $this->view(
            '@a/users/users-list.twig',
            $this->getContext($this->getContainer()->get(UsersList::class))
        );
    }

    public function edit(): string
    {
        return $this->view(
            '@a/users/edituser.twig',
            $this->getContext($this->getContainer()->get(Edit::class))
        );
    }

    public function add(): string
    {
        return $this->view(
            '@a/users/adduser.twig',
            $this->getContext($this->getContainer()->get(Add::class))
        );
    }


    public function delete(): string
    {
        return $this->view(
            '@a/users/deleteuser.twig',
            $this->getContext($this->getContainer()->get(Delete::class))
        );
    }

    public function changePassword(): string
    {
        return $this->view(
            '@a/users/change-password.twig',
            $this->getContext($this->getContainer()->get(ChangePassword::class))
        );
    }

}
