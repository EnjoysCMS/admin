<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Core\Widgets;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\AccessControl\ACL;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ActivateWidget
{


    private string $class;


    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Identity $identity,
        private readonly RedirectInterface $redirect,
        private readonly ACL $ACL,
    ) {
        $class = $this->request->getQueryParams()['class'] ?? null;
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }
        $this->class = $class;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(): ResponseInterface
    {
        $data = $this->class::getMeta();
        $widget = new Widget();
        $widget->setName($data['name']);
        $widget->setClass($this->class);
        $widget->setOptions($data['options'] ?? []);
        $widget->setUser($this->identity->getUser());

        $this->em->persist($widget);
        $this->em->flush();


        $this->ACL->addAcl(
            $widget->getWidgetActionAcl(),
            $widget->getWidgetCommentAcl()
        );
        return $this->redirect->toRoute('admin/index');
    }


}
