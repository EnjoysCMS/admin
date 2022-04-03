<?php

declare(strict_types=1);


namespace App\Module\Admin\Core\Blocks;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteBlock
{
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
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
    public function __invoke(FactoryInterface $container)
    {
        $block = $this->em->getRepository(Block::class)->find(
            $this->requestWrapper->getRequest()->getAttribute('id')
        );

        if ($block === null) {
            throw new NoResultException();
        }

        if (!$block->isRemovable()) {
            throw new \RuntimeException('Block is not removable');
        }

        $container->make($block->getClass(), ['block' => $block])->preRemove();

        $this->em->remove($block);
        $this->em->flush();


        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }

}
