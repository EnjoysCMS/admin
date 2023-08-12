<?php


namespace EnjoysCMS\Module\Admin\Core\Blocks;


use EnjoysCMS\Core\Block\BlockCollection;
use Exception;

class SetupBlocks
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
