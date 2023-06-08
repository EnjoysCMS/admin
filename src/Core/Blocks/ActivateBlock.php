<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Block;
use EnjoysCMS\Core\Block\Annotation\Block as BlockAnnotation;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

class ActivateBlock
{
    private ReflectionClass $class;


    /**
     * @throws ReflectionException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RedirectInterface $redirect,
    ) {
        $class = $this->request->getQueryParams()['class'] ?? null;

        if (!class_exists((string)$class)) {
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
        $data = $this->getAnnotations($this->class);
        $block = new Block\Entity\Block();
        $block->setId($id);
        $block->setName($data->getName() ?? $this->class->getShortName());
        $block->setClassName($this->class->getName());
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($data->getOptions()->all());
        $this->em->persist($block);
        $this->em->flush();


        ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        return $this->redirect->toRoute('admin/editblock', ['id' => $id]);
    }

    private function getAnnotations(ReflectionClass $reflection): BlockAnnotation
    {
        foreach (
            $reflection->getAttributes(
                Block\Annotation\Block::class,
                ReflectionAttribute::IS_INSTANCEOF
            ) as $attribute
        ) {
            return $attribute->newInstance();
        }
        throw new InvalidArgumentException(sprintf('Class "%s" not supported', $reflection->getName()));
    }


}
