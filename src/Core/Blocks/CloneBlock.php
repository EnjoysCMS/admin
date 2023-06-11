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
use EnjoysCMS\Core\Components\AccessControl\ACL;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

final class CloneBlock
{
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly BlockFactory $blockFactory,
        private readonly RedirectInterface $redirect,
        private readonly ACL $ACL
    ) {
    }

    /**
     * @throws DependencyException
     * @throws NoResultException
     * @throws NotFoundException
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(): ResponseInterface
    {
        $block = $this->em->getRepository(Block::class)->find(
            $this->request->getAttribute('id')
        );

        if ($block === null) {
            throw new NoResultException();
        }

        $cloned = clone $block;
        $cloned->setId(Uuid::uuid4()->toString());
        $cloned->setRemovable(true);
        $cloned->setCloned(true);
        $this->em->persist($cloned);
        $this->em->flush();

        $this->ACL->addAcl(
            $cloned->getBlockActionAcl(),
            $cloned->getBlockCommentAcl()
        );


        $this->blockFactory->create($block->getClassName())->setEntity($block)->postClone($cloned);

        return $this->redirect->toRoute('admin/blocks');
    }
}
