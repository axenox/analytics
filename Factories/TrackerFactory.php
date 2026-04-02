<?php
namespace axenox\Analytics\Factories;

use axenox\Analytics\Common\Selectors\TrackerPrototypeSelector;
use axenox\Analytics\Interfaces\TrackerInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\PhpFilePathDataType;
use exface\Core\Factories\AbstractStaticFactory;
use exface\Core\Interfaces\WorkbenchInterface;

class TrackerFactory extends AbstractStaticFactory
{
    public static function createTrackerFromPrototype(WorkbenchInterface $workbench, string $ClassOrPath, UxonObject $uxon) : TrackerInterface
    {
        $selector = new TrackerPrototypeSelector($workbench, $ClassOrPath);
        if ($selector->isClassname()) {
            $class = $selector->__toString();
        } else {
            $class = PhpFilePathDataType::findClassInVendorFile($workbench, $selector->__toString());
        }
        return new $class($workbench, $uxon);
    }
    
    public static function createTrackerFromUid(WorkbenchInterface $workbench, string $uid) : TrackerInterface
    {
        // TODO
    }
}