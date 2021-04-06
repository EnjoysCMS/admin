<?php
namespace App\Module\Admin\Core\ACL;

use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Helpers\Modules;
use EnjoysCMS\Core\Repositories\ACL;
use Doctrine\Persistence\ObjectRepository;

class ACList
{


    /**
     * ACList constructor.
     *
     * @param ObjectRepository|ACL $repositoryAcl
     */
    public function __construct(ObjectRepository $repositoryAcl)
    {
        $this->repositoryAcl = $repositoryAcl;
    }

    public function getActiveACL()
    {
        return $this->repositoryAcl->getAllActiveACL();
    }

    public function getArrayForCheckboxForm()
    {
        $ret = [];
        $groupedAcl = $this->getGroupedAcl();
        foreach ($groupedAcl as $group => $acls) {
            /**
* 
             *
 * @var \EnjoysCMS\Core\Entities\ACL $acl 
*/
            foreach ($acls as $acl) {
                $ret[$group][' ' . $acl->getId()]  = [
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
                        return str_starts_with($v->getAction(), $ns);
                    }
                );
            }

            $activeAcl = array_diff_key($activeAcl, $groupedAcl[$module->moduleName]);

            rsort($groupedAcl[$module->moduleName]);
        }

        /**
         * Добавление остальных ACL в системный модуль
         */
        $systemNamespaces = Utils::parseComposerJson($_ENV['PROJECT_DIR'] . '/composer.json')->namespaces;
        foreach ($systemNamespaces as $ns) {
            $groupedAcl['@Application'] = array_filter(
                $activeAcl,
                function ($v) use ($ns) {
                    return str_starts_with($v->getAction(), $ns);
                }
            );
            rsort($groupedAcl['@Application']);
        }



        return $groupedAcl;
    }
}
