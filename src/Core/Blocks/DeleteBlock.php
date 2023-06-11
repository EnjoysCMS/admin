<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Block\BlockFactory;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class DeleteBlock
{
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly BlockFactory $blockFactory,
        private readonly RedirectInterface $redirect,
    ) {
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotSupported
     * @throws NoResultException
     */
    public function __invoke(): ResponseInterface
    {
        /** @var Block $block */
        $block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();


        if (!$block->isRemovable()) {
            throw new RuntimeException('Block is not removable');
        }

        try {
            $this->blockFactory->create($block->getClassName())->setEntity($block)->preRemove();
        } catch (DependencyException|NotFoundException) {
        }

        $this->em->remove($block);
        $this->em->flush();

        return $this->redirect->toRoute('admin/blocks');
    }

}
