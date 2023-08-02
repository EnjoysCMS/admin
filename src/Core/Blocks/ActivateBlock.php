<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\AccessControl\ACL;
use EnjoysCMS\Core\Block;
use EnjoysCMS\Core\Block\BlockCollection;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

class ActivateBlock
{
    private ReflectionClass $class;


    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RedirectInterface $redirect,
        private readonly BlockCollection $blockCollection,
        private readonly ACL $ACL
    ) {
        /** @var class-string $class */
        $class = $this->request->getQueryParams()['class'] ?? '';

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $this->class = new ReflectionClass($class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(): ResponseInterface
    {
        $id = Uuid::uuid4()->toString();

        $blockAnnotation = $this->blockCollection->getAnnotation(
            $this->class
        ) ?? throw new InvalidArgumentException(
            sprintf('Class "%s" not supported', $this->class->getName())
        );

        $block = new Block\Entity\Block();
        $block->setId($id);
        $block->setName($blockAnnotation->getName());
        $block->setClassName($blockAnnotation->getClassName());
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($blockAnnotation->getOptions());
        $this->em->persist($block);
        $this->em->flush();


        $this->ACL->addAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        return $this->redirect->toRoute('@admin_blocks_manage', ['id' => $id]);
    }

}
