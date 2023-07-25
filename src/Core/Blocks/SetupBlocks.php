<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use EnjoysCMS\Core\Block\BlockCollection;
use EnjoysCMS\Core\Block\Collection;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Exception;

class SetupBlocks implements ModelInterface
{

    public function __construct(
        private readonly BlockCollection $blockCollection,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getContext(): array
    {
        return [
            'blocks' => $this->blockCollection
        ];
    }
}
