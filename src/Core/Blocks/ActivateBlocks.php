<?php


namespace App\Module\Admin\Core\Blocks;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Entities\Blocks;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateBlocks
{


    private string $class;
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    public function __construct(string $class, EntityManager $entityManager)
    {
        if(!class_exists($class)){
            throw new \InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = $class;
        $this->entityManager = $entityManager;
    }

    public function activate()
    {
        $data = $this->class::getMeta();
        $block = new Blocks();
        $block->setName($data['name']);
        $block->setClass($this->class);
        $block->setCloned(false);
        $block->setRemovable(true);
        $block->setOptions($data['options']);


        $this->entityManager->persist($block);
        $this->entityManager->flush();


        ACL::registerAcl(
            $block->getBlockActionAcl(),
            $block->getBlockCommentAcl()
        );

        return $block->getId();
    }


}