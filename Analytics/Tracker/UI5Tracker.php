<?php
namespace axenox\Analytics\Analytics\Tracker;

use axenox\Analytics\Common\AbstractTracker;
use axenox\Analytics\Interfaces\TrackerInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\JsonDataType;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\DataSheetMapperFactory;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Trackers actions and navigation in the UI5 facade
 * 
 * TODO use the JsTrackerInterface
 * 
 * @author Andrej Kabachnik
 */
class UI5Tracker extends AbstractTracker
{    
    private ?UxonObject $actionMapperUxon = null;

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

    protected function getActionMapper(): ?DataSheetMapperInterface
    {
        return DataSheetMapperFactory::createFromUxon($this->getWorkbench(), $this->actionMapperUxon);
    }
    
    protected function hasActionMapper() : bool
    {
        return !is_null($this->actionMapperUxon);
    }

    protected function saveEvent(array $eventData, string $eventType, string $eventUid, ?ServerRequestInterface $request) : TrackerInterface
    {
        switch ($eventType) {
            case 'action':
            case 'widget':
                $this->saveEventForActions($eventData, $eventUid);
                break;
        }
        return $this;
    }

    protected function saveEventForActions(array $eventData, string $eventUid)
    {
        $actionSheet = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Analytics.action');
        $actionSheet->addRow([
            'tracker' => $this->getUid(),
            'event' => $eventUid,
            'action_alias' => $eventData['action']['alias'],
            'action_object_alias' => $eventData['action']['object'],
            'page_alias' => $eventData['page'],
            'widget_id' => $eventData['widget'],
            'user' => $eventData['user'],
            'request_data_object_alias' => $eventData['request']['object'],
            'request_data_columns' => $eventData['request']['columns'] ? JsonDataType::encodeJson($eventData['request']['columns']) : null,
            'request_data_filters' => $eventData['request']['filters'] ? JsonDataType::encodeJson($eventData['request']['filters']) : null,
            'request_data_sorters' => $eventData['request']['sorters'] ? JsonDataType::encodeJson($eventData['request']['sorters']) : null,
            'request_data_aggregators' => $eventData['request']['aggregators'] ? JsonDataType::encodeJson($eventData['request']['aggregators']) : null,
            'request_data_row_count' => $eventData['request']['rows'],
            'response_data_columns' => $eventData['response']['columns'] ? JsonDataType::encodeJson($eventData['response']['columns']) : null,
            'response_data_row_count' => $eventData['response']['rows'],
            'duration_ms' => $eventData['duration'],
            'duration_server_ms' => $eventData['duration_server'],
            'duration_network_ms' => $eventData['duration'] - $eventData['duration_server'],
        ]);

        if ($this->hasActionMapper()) {
            $actionSheet = $this->getActionMapper()->map($actionSheet);
        }

        $actionSheet->dataCreate(false);
    }
}