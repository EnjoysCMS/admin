<?php

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Module\Admin\Core\ModelInterface;

class ManageBlocks implements ModelInterface
{

    public function __construct(
        private readonly EntityManager $em,
    ) {
    }

    /**
     * @throws NotSupported
     */
    public function getContext(): array
    {
        return [
            'blocks' => $this->em->getRepository(Block::class)->findAll(),
        ];
    }


}
