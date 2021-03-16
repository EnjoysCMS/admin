<?php

namespace App\Module\Admin\Core\Blocks;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Entities\Blocks;

class ManageBlocks implements ModelInterface
{

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;


    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $blocksRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->blocksRepository = $entityManager->getRepository(Blocks::class);
    }

    public function getContext(): array
    {
        return [
            'blocks' => $this->blocksRepository->findAll()
        ];
    }


}
