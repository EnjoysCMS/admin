<?php


namespace App\Module\Admin\Core\Widgets;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Widget;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateWidget
{


    private string $class;


    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $class = $this->serverRequest->get('class');
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = $class;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke()
    {
        $data = $this->class::getMeta();
        $widget = new Widget();
        $widget->setName($data['name']);
        $widget->setClass($this->class);
        $widget->setOptions($data['options']);


        $this->em->persist($widget);
        $this->em->flush();


        ACL::registerAcl(
            $widget->getWidgetActionAcl(),
            $widget->getWidgetCommentAcl()
        );

        Redirect::http($this->urlGenerator->generate('admin/editwidget', ['id' => $widget->getId()]));
    }


}
