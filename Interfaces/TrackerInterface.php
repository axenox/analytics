<?php

namespace axenox\Analytics\Interfaces;

use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use exface\Core\Interfaces\iCanBeConvertedToUxon;

interface TrackerInterface extends iCanBeConvertedToUxon
{
    public function getUid() : string;
    
    public function getActionMapper() : ?DataSheetMapperInterface;

    public function hasActionMapper() : bool;
}