<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Admin\Core\Settings;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Exception\CannotRemoveEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteSetting
{

    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws CannotRemoveEntity
     * @throws ORMException
     * @throws NoResultException
     */
    public function __invoke()
    {
        if (null === $setting = $this->em->getRepository(\EnjoysCMS\Core\Entities\Setting::class)->find(
                $this->requestWrapper->getQueryData('id')
            )) {
            throw new NoResultException();
        }

        if (!$setting->isRemovable()) {
            throw new CannotRemoveEntity('This the setting not removable');
        }


        $this->em->remove($setting);
        $this->em->flush();


        Redirect::http($this->urlGenerator->generate('admin/setting'));
    }
}
