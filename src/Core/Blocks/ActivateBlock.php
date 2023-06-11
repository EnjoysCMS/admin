<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Block;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
        private readonly Block\Collection $blockCollection
    ) {
        /** @var class-string $class */
        $class = $this->request->getQueryParams()['class'] ?? '';

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $this->class = new ReflectionClass($class);
    }

    /**
     * @throws OptimisticLockException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(): ResponseInterface
    {
        $id = Uuid::uuid4()->toString();

        $blockAnnotation = $this->blockCollection->getBlockAnnotation($this->class) ?? throw new InvalidArgumentException(
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


        ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        return $this->redirect->toRoute('admin/editblock', ['id' => $id]);
    }

}
