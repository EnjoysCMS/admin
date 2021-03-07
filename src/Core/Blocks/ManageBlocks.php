<?php

namespace App\Module\Admin\Core\Blocks;

use App\Entities\Blocks;
use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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