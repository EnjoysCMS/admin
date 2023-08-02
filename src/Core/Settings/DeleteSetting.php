<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteSetting
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RedirectInterface $redirect,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws CannotRemoveEntity
     * @throws ORMException
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __invoke(): ResponseInterface
    {
        $setting = $this->em->getRepository(\EnjoysCMS\Core\Setting\Entity\Setting::class)->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();


        if (!$setting->isRemovable()) {
            throw new CannotRemoveEntity('This the setting not removable');
        }


        $this->em->remove($setting);
        $this->em->flush();

        return $this->redirect->toRoute('@admin_setting_manage');
    }
}
