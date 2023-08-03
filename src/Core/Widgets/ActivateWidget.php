<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Widgets;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\AccessControl\ACL;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Block\Entity\Widget;
use EnjoysCMS\Core\Block\WidgetCollection;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

class ActivateWidget
{


    private ReflectionClass $class;


    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Identity $identity,
        private readonly RedirectInterface $redirect,
        private readonly WidgetCollection $widgetCollection,
        private readonly ACL $ACL,
    ) {
        $class = $this->request->getQueryParams()['class'] ?? null;
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = new ReflectionClass($class);
    }

    /**
     * @return ResponseInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(): ResponseInterface
    {
        $widgetAnnotation = $this->widgetCollection->getAnnotation(
            $this->class
        ) ?? throw new InvalidArgumentException(
            sprintf('Class "%s" not supported', $this->class->getName())
        );

        $widget = new Widget();
        $widget->setName($widgetAnnotation->getName());
        $widget->setClass($widgetAnnotation->getClassName());
        $widget->setOptions($widgetAnnotation->getOptions());
        $widget->setUser($this->identity->getUser());

        $this->em->persist($widget);
        $this->em->flush();


        $this->ACL->addAcl(
            $widget->getWidgetActionAcl(),
            $widget->getWidgetCommentAcl()
        );
        return $this->redirect->toRoute('@admin_index');
    }


}
