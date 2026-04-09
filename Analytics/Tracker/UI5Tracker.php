<?php
namespace axenox\analytics\Analytics\Tracker;
use axenox\Analytics\Interfaces\TrackerInterface;
use exface\Core\CommonLogic\Traits\ICanBeConvertedToUxonTrait;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Factories\DataSheetMapperFactory;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use exface\Core\Interfaces\WorkbenchInterface;

/**
 * Trackers actions and navigation in the UI5 facade
 * 
 * @author Andrej Kabachnik
 */
class UI5Tracker implements TrackerInterface
{
    use ICanBeConvertedToUxonTrait;
    
    private $workbench;
    private string $uid;
    
    private ?UxonObject $actionMapperUxon = null;
    private array $expectedOrigins = [];
    
    public function __construct(WorkbenchInterface $workbench, UxonObject $uxon)
    {
        $this->workbench = $workbench;
        $this->importUxonObject($uxon);
    }

    /**
     * Apply data sheet mapper when saving action analytics data (e.g. translate UIDs to aliases)
     * 
     * @uxon-property action_mapper
     * @uxon-type \exface\Core\CommonLogic\DataSheets\DataSheetMapper
     * @uxon-template {"from_object_alias": "axenox.Analytics.action", "to_object_alias": "axenox.Analytics.action", "column_dictionary_mappings": [{"from": "action_object_alias", "to": "action_object_alias", "dictionary": {"": ""}}]}
     * 
     * @return $this
     */
    protected function setActionMapper(UxonObject $uxon) : UI5Tracker
    {
        $this->actionMapperUxon = $uxon;
        return $this;
    }
    
    protected function setUid(string $uid) : UI5Tracker
    {
        $this->uid = $uid;
        return $this;
    }
    
    public function getUid() : string
    {
        return $this->uid;
    }

    public function getActionMapper(): ?DataSheetMapperInterface
    {
        return DataSheetMapperFactory::createFromUxon($this->workbench, $this->actionMapperUxon);
    }
    
    public function hasActionMapper() : bool
    {
        return !is_null($this->actionMapperUxon);
    }

    /**
     * List of allowed origins that may send tracker events (e.g. the environment/s where the tracking is enabled in the config)
     * 
     * @uxon-property expected_origins
     * @uxon-type array
     * @uxon-template [""]
     * 
     * @param UxonObject $uxon
     * @return $this
     */
    protected function setExpectedOrigins(UxonObject $uxon) : UI5Tracker
    {
        $this->expectedOrigins = $uxon->toArray();
        return $this;
    }

    public function getExpectedOrigins() : array
    {
        return $this->expectedOrigins;
    }
}