<?php


namespace App\Module\Admin\Core\Widgets;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Entities\Widgets;
use Psr\Container\ContainerInterface;

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
