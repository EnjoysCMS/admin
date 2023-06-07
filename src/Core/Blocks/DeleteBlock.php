<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteBlock
{
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RedirectInterface $redirect,
    ) {
    }

    /**
     * @throws NoResultException
     * @throws NotSupported
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(FactoryInterface $container): ResponseInterface
    {
        $block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        ) ?? throw new NoResultException();


        if (!$block->isRemovable()) {
            throw new \RuntimeException('Block is not removable');
        }

        try {
            $container->make($block->getClassName(), ['block' => $block])->preRemove();
        } catch (DependencyException|NotFoundException) {
        }

        $this->em->remove($block);
        $this->em->flush();

        return $this->redirect->toRoute('admin/blocks');
    }

}
