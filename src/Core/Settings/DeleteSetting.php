<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteSetting
{

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RedirectInterface $redirect,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws CannotRemoveEntity
     * @throws ORMException
     * @throws NoResultException
     */
    public function __invoke(): ResponseInterface
    {
        if (null === $setting = $this->em->getRepository(\EnjoysCMS\Core\Entities\Setting::class)->find(
                $this->request->getQueryParams()['id'] ?? 0
            )) {
            throw new NoResultException();
        }

        if (!$setting->isRemovable()) {
            throw new CannotRemoveEntity('This the setting not removable');
        }


        $this->em->remove($setting);
        $this->em->flush();

        return $this->redirect->toRoute('admin/setting');
    }
}
