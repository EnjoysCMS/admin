<?php

namespace EnjoysCMS\Module\Admin\Groups;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\AccessControl\ACL\Entity\ACLEntity;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Extensions\Composer\Utils;
use EnjoysCMS\Core\Modules\ModuleCollection;
use Symfony\Component\Routing\RouteCollection;

class ACList
{

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ModuleCollection $moduleCollection,
        private readonly AccessControl $accessControl,
        private readonly RouteCollection $routeCollection
    ) {
        $this->synchronize();
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function synchronize(): void
    {
        /** @var Block[] $blocks */
        $blocks = $this->em->getRepository(Block::class)->findAll();
        $active = [];
        foreach ($blocks as $block) {
            $this->accessControl->getManage()->register(
                $block->getId(),
                sprintf("%s <br>[Блок][%s]", $block->getName(), $block->getClassName()),
                false
            );
            $active[] = $block->getId();
        }

        foreach ($this->routeCollection as $routeName => $route) {
            $this->accessControl->getManage()->register(
                $routeName,
                sprintf(
                    '%s<div><strong>%s</strong></div>',
                    implode(':', (array)$route->getDefault('_controller')),
                    $route->getOption('comment'),

                ),
                false
            );

            $active[] = $routeName;
        }


        foreach ($this->accessControl->getManage()->getList() as $acl) {
            if (!in_array($acl->getAction(), $active)) {
                $this->em->remove($acl);
            }
        }

        $this->em->flush();
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
            foreach ($acls as $acl) {
                $ret[$group][' ' . $acl->getId()] = [
                    sprintf(
                        "<div class='h5'>%s</div><div>%s</div>",
                        $acl->getAction(),
                        $acl->getComment()
                    ),
                    $acl->getAction(),
                    ['id' => $acl->getId()]
                ];
            }
        }

        return $ret;
    }


    /**
     * @return array<string, ACLEntity[]>
     */
    public function getGroupedAcl(): array
    {
        $activeAcl = $this->accessControl->getManage()->getList();
        $groupedAcl = [];

        /**
         * Группировка ACL по модулям
         */
        foreach ($this->moduleCollection->all() as $module) {
            if ($module->namespaces === []) {
                continue;
            }

            $groupedAcl[$module->moduleName] = array_filter(
                $activeAcl,
                function ($v) use ($module) {
                    return str_contains(ltrim($v->getComment(), '\\'), current($module->namespaces));
                }
            );

            $activeAcl = array_diff_key($activeAcl, $groupedAcl[$module->moduleName]);

            uasort($groupedAcl[$module->moduleName], function ($a, $b) {
                return $a->getAction() <=> $b->getAction();
            });
        }

        /**
         * Добавление остальных ACL в системный модуль
         */
        $systemNamespaces = Utils::parseComposerJson(getenv('ROOT_PATH') . '/composer.json')->namespaces;
        foreach ($systemNamespaces as $ns) {
            $groupedAcl['Application'] = array_filter(
                $activeAcl,
                function ($v) use ($ns) {
                    return str_starts_with(ltrim($v->getAction(), '\\'), $ns);
                }
            );
            rsort($groupedAcl['Application']);
        }
        $groupedAcl['Application'] = $activeAcl;
        return $groupedAcl;
    }
}
