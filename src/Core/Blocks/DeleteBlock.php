<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteBlock
{
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotFoundException
     * @throws DependencyException
     * @throws NoResultException
     */
    public function __invoke(FactoryInterface $container): void
    {
        $block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        );

        if ($block === null) {
            throw new NoResultException();
        }

        if (!$block->isRemovable()) {
            throw new \RuntimeException('Block is not removable');
        }

        try {
            $container->make($block->getClass(), ['block' => $block])->preRemove();
        } catch (DependencyException|NotFoundException) {
        }

        $this->em->remove($block);
        $this->em->flush();


        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }

}
