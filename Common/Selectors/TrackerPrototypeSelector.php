<?php
namespace axenox\Analytics\Common\Selectors;

use exface\Core\CommonLogic\Selectors\AbstractSelector;
use exface\Core\CommonLogic\Selectors\Traits\PrototypeSelectorTrait;
use exface\Core\Interfaces\Selectors\PrototypeSelectorInterface;

class TrackerPrototypeSelector extends AbstractSelector implements PrototypeSelectorInterface
{
    use PrototypeSelectorTrait;

    /**
     * @inheritDoc
     */
    public function getComponentType(): string
    {
        return 'analytics tracker';
    }
}