<?php

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Block\Entity\Block;

class ManageBlocks
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
