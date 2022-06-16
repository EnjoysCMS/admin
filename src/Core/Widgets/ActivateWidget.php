<?php


namespace EnjoysCMS\Module\Admin\Core\Widgets;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Auth\Identity;
use EnjoysCMS\Core\Components\Helpers\ACL;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Widget;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivateWidget
{


    private string $class;


    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator,
        private Identity $identity
    ) {
        $class = $this->requestWrapper->getQueryData('class');
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
        $widget->setOptions($data['options'] ?? []);
        $widget->setUser($this->identity->getUser());

        $this->em->persist($widget);
        $this->em->flush();


        ACL::registerAcl(
            $widget->getWidgetActionAcl(),
            $widget->getWidgetCommentAcl()
        );

        Redirect::http($this->urlGenerator->generate('admin/index'));
    }


}
