<?php


namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Groups\Add;
use App\Module\Admin\Core\Groups\Delete;
use App\Module\Admin\Core\Groups\Edit;
use App\Module\Admin\Core\Groups\GroupsList;


class Groups extends BaseController
{



    public function list(): string
    {
        return $this->view(
            '@a/groups/list.twig',
            $this->getContext($this->getContainer()->get(GroupsList::class))
        );
    }

    public function edit(): string
    {
        return $this->view(
            '@a/groups/edit.twig',
            $this->getContext($this->getContainer()->get(Edit::class))
        );
    }


    public function add(): string
    {
        return $this->view(
            '@a/groups/add.twig',
            $this->getContext($this->getContainer()->get(Add::class))
        );
    }

    public function delete(): string
    {
        return $this->view(
            '@a/groups/delete.twig',
            $this->getContext($this->getContainer()->get(Delete::class))
        );
    }

}
