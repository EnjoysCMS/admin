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
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CloneBlock
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
            $this->requestWrapper->getQueryData('id')
        );

        if ($block === null) {
            throw new NoResultException();
        }

        $cloned = clone $block;
        $cloned->setAlias((string)Uuid::uuid4());
        $cloned->setRemovable(true);
        $cloned->setCloned(true);
        $this->em->persist($cloned);
        $this->em->flush();

        ACL::registerAcl(
            $cloned->getBlockActionAcl(),
            $cloned->getBlockCommentAcl()
        );


        $container->make($block->getClass(), ['block' => $block])->postClone($cloned);

        Redirect::http($this->urlGenerator->generate('admin/blocks'));
    }
}
