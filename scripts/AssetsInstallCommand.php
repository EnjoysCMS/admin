<?php

namespace EnjoysCMS\Module\Admin\Composer\Scripts;

use EnjoysCMS\Core\Console\Command\AbstractAssetsInstallCommand;

class AssetsInstallCommand extends AbstractAssetsInstallCommand
{
    protected string $cwd = __DIR__ . '/..';
}
