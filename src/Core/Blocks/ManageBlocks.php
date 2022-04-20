<?php

namespace EnjoysCMS\Module\Admin\Core\Blocks;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Entities\Block;
use EnjoysCMS\Module\Admin\Core\ModelInterface;

class ManageBlocks implements ModelInterface
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $blocksRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->blocksRepository = $entityManager->getRepository(Block::class);
    }

    public function getContext(): array
    {
        return [
            'blocks' => $this->blocksRepository->findAll()
        ];
    }


}
