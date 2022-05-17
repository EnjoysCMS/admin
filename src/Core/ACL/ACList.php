<?php

namespace EnjoysCMS\Module\Admin\Core\ACL;

use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Helpers\Modules;
use EnjoysCMS\Core\Entities\ACL;

class ACList
{
    private ObjectRepository $repositoryAcl;


    /**
     * ACList constructor.
     *
     * @param ObjectRepository $repositoryAcl
     */
    public function __construct(ObjectRepository $repositoryAcl)
    {
        $this->repositoryAcl = $repositoryAcl;
    }

    public function getActiveACL()
    {
        return $this->repositoryAcl->getAllActiveACL();
    }

    /**
     * @return (int[]|string)[][][]
     *
     * @psalm-return array<array<string, array{0: string, 1: array{id: int}}>>
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

    public function getGroupedAcl(): array
    {
        $activeAcl = $this->getActiveACL();
        $groupedAcl = [];
        /**
         * Группировка ACL по модулям
         */
        foreach (Modules::installed() as $module) {
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

            uasort($groupedAcl[$module->moduleName], function(ACL $a, ACL$b){
                return $a->getAction() <=> $b->getAction();

            });
        }

        /**
         * Добавление остальных ACL в системный модуль
         */
        $systemNamespaces = Utils::parseComposerJson($_ENV['PROJECT_DIR'] . '/composer.json')->namespaces;
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
