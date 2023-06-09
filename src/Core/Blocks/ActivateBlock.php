<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Block\Collection;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateBlock
{
    private \ReflectionClass $class;


    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private Collection $blockCollection,
    ) {
        $class = $this->request->getQueryParams()['class'] ?? null;

        if (!class_exists((string)$class)) {
            throw new \InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = new \ReflectionClass($class);
    }

    public function __invoke()
    {
        $blockAnnotation = $this->blockCollection->getBlockAnnotation($this->class) ?? throw new \InvalidArgumentException(
            sprintf('Class "%s" not supported', $this->class->getName())
        );

        $block = new Block();
        $block->setName($blockAnnotation->getName());
        $block->setAlias((string)Uuid::uuid4());
        $block->setClass($blockAnnotation->getClassName());
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($blockAnnotation->getOptions()->getIterator()->getArrayCopy());


        $this->em->persist($block);
        $this->em->flush();


        ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        Redirect::http($this->urlGenerator->generate('admin/editblock', ['id' => $block->getId()]));
    }


}
