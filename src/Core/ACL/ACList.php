<?php

namespace EnjoysCMS\Module\Admin\Core\ACL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Extensions\Composer\Utils;
use EnjoysCMS\Core\Modules\ModuleCollection;
use Symfony\Component\Routing\RouteCollection;

class ACList
{
    private \EnjoysCMS\Core\Repositories\ACL|EntityRepository $repositoryAcl;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly RouteCollection $routeCollection,
        private readonly ModuleCollection $moduleCollection
    ) {
        $this->repositoryAcl = $em->getRepository(ACL::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function getActiveACL(): array
    {

        /** @var Block[] $blocks */
        $blocks = $this->em->getRepository(Block::class)->findAll();
        $allActiveBlocksController = [];
        foreach ($blocks as $block) {
            $allActiveBlocksController[] = $block->getBlockActionAcl();
        }

        $allActiveControllers = [];
        foreach ($this->routeCollection as $route) {
            $allActiveControllers[] = implode('::', (array)$route->getDefault('_controller'));
        }

        $allAcl = $this->repositoryAcl->findAll();
        /** @var ACL $acl */
        foreach ($allAcl as $key => $acl) {
            if (!in_array($acl->getAction(), array_merge($allActiveControllers, $allActiveBlocksController))) {
                unset($allAcl[$key]);
                $this->em->remove($acl);
            }
        }
        $this->em->flush();

        return $allAcl;
    }


    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function getArrayForCheckboxForm(): array
    {
        $ret = [];
        $groupedAcl = $this->getGroupedAcl();
        foreach ($groupedAcl as $group => $acls) {
            /**
             * @var ACL $acl
             */
            foreach ($acls as $acl) {
                $ret[$group][' ' . $acl->getId()] = [
                    $acl->getComment() . '<br><small>' . $acl->getAction() . '</small>',
                    ['id' => $acl->getId()]
                ];
            }
        }

        return $ret;
    }


    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function getGroupedAcl(): array
    {
        $activeAcl = $this->getActiveACL();
        $groupedAcl = [];
        /**
         * Группировка ACL по модулям
         */
        foreach ($this->moduleCollection->all() as $module) {
            foreach ($module->namespaces as $ns) {

                $groupedAcl[$module->moduleName] = array_filter(
                    $activeAcl,
                    function ($v) use ($ns) {
                        return str_starts_with(ltrim($v->getAction(), '\\'), $ns);
                    }
                );
                break;
            }

            $activeAcl = array_diff_key($activeAcl, $groupedAcl[$module->moduleName]);

            uasort($groupedAcl[$module->moduleName], function (ACL $a, ACL $b) {
                return $a->getAction() <=> $b->getAction();
            });
        }

        /**
         * Добавление остальных ACL в системный модуль
         */
        $systemNamespaces = Utils::parseComposerJson(getenv('ROOT_PATH') . '/composer.json')->namespaces;
        foreach ($systemNamespaces as $ns) {
            $groupedAcl['@Application'] = array_filter(
                $activeAcl,
                function ($v) use ($ns) {
                    return str_starts_with(ltrim($v->getAction(), '\\'), $ns);
                }
            );
            rsort($groupedAcl['@Application']);
        }

        return $groupedAcl;
    }
}
