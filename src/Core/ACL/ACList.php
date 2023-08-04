<?php

namespace EnjoysCMS\Module\Admin\Core\ACL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\AccessControl\AccessControl;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Entities\ACL;
use EnjoysCMS\Core\Extensions\Composer\Utils;
use EnjoysCMS\Core\Modules\ModuleCollection;
use Symfony\Component\Routing\RouteCollection;

class ACList
{

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly ModuleCollection $moduleCollection,
        private readonly AccessControl $accessControl
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function getActiveACL(): array
    {
//        /** @var Block[] $blocks */
//        $blocks = $this->em->getRepository(Block::class)->findAll();
//        $allActiveBlocksController = [];
//        foreach ($blocks as $block) {
//            $allActiveBlocksController[] = $block->getId();
//        }
//
//        $allActiveControllers = [];
//        foreach ($this->routeCollection as $route) {
//            $allActiveControllers[] = implode('::', (array)$route->getDefault('_controller'));
//        }

        //        foreach ($allAcl as $key => $acl) {
//            if (!in_array($acl->getAction(), array_merge($allActiveControllers, $allActiveBlocksController))) {
//                unset($allAcl[$key]);
//                $this->em->remove($acl);
//            }
//        }
//        $this->em->flush();

        return $this->accessControl->getManage()->getList();
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
                        "%s<span class='font-weight-bold'>%s</span>",
                        $acl->getComment() ? $acl->getComment() . '<br>' : '',
                        $acl->getAction()
                    ),
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

            uasort($groupedAcl[$module->moduleName], function ($a, $b) {
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
        $groupedAcl['@Application'] = $activeAcl;
        return $groupedAcl;
    }
}
