<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Blocks;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Block;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateBlock
{
    private string $class;


    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $class = $this->request->getQueryParams()['class'] ?? null;

        if (!class_exists((string)$class)) {
            throw new \InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = $class;
    }

    public function __invoke()
    {
        $data = $this->class::getMeta();
        $block = new Block();
        $block->setName($data['name']);
        $block->setAlias((string)Uuid::uuid4());
        $block->setClass($this->class);
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($data['options']);


        $this->em->persist($block);
        $this->em->flush();


        ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        Redirect::http($this->urlGenerator->generate('admin/editblock', ['id' => $block->getId()]));
    }


}
