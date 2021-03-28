<?php


namespace App\Module\Admin\Core\Widgets;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Config\Config;
use Enjoys\Config\Parse\YAML;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Entities\Blocks;
use EnjoysCMS\Core\Entities\Widgets;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateWidgets
{


    private string $class;
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    public function __construct(string $class, ContainerInterface $container)
    {
        if(!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = $class;
        $this->entityManager = $container->get(EntityManager::class);
    }

    public function activate()
    {
        $data = $this->class::getMeta();
        $widget = new Widgets();
        $widget->setName($data['name']);
        $widget->setClass($this->class);
        $widget->setOptions($data['options']);


        $this->entityManager->persist($widget);
        $this->entityManager->flush();


        ACL::registerAcl(
            $widget->getWidgetActionAcl(),
            $widget->getWidgetCommentAcl()
        );

        return $widget->getId();
    }


}
