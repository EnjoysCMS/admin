<?php
declare(strict_types=1);

namespace App\Module\Admin\Controller;


use App\Module\Admin\BaseController;
use App\Module\Admin\Core\Blocks\ManageBlocks;

class Blocks extends BaseController
{
    public function manage()
    {
        return $this->view('@a/blocks/manage.twig', $this->getContext(new ManageBlocks()));
    }

    public function add()
    {
    }

    public function setUp()
    {
    }

}