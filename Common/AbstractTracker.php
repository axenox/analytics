<?php
namespace axenox\Analytics\Common;

use axenox\Analytics\Interfaces\TrackerInterface;
use exface\Core\CommonLogic\Traits\ICanBeConvertedToUxonTrait;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\JsonDataType;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\DataSheetMapperFactory;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use exface\Core\Interfaces\WorkbenchInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sabre\DAV\Server;

/**
 * Trackers actions and navigation in the UI5 facade
 * 
 * @author Andrej Kabachnik
 */
abstract class AbstractTracker implements TrackerInterface
{
    use ICanBeConvertedToUxonTrait;
    
    private WorkbenchInterface $workbench;
    private string $uid;
    
    private ?UxonObject $actionMapperUxon = null;
    private array $expectedOrigins = [];
    
    public function __construct(WorkbenchInterface $workbench, UxonObject $uxon)
    {
        $this->workbench = $workbench;
        $this->importUxonObject($uxon);
    }

    /**
     * {@inheritDoc}
     * @see TrackerInterface::getUid()
     */
    public function getUid() : string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return TrackerInterface
     */
    protected function setUid(string $uid) : TrackerInterface
    {
        $this->uid = $uid;
        return $this;
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
    protected function setExpectedOrigins(UxonObject $uxon) : TrackerInterface
    {
        $this->expectedOrigins = $uxon->toArray();
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see TrackerInterface::getExpectedOrigins()
     */
    public function getExpectedOrigins() : array
    {
        return $this->expectedOrigins;
    }

    /**
     * {@inheritDoc}
     * @see TrackerInterface::saveRequest()
     */
    public function saveRequest(ServerRequestInterface $request) : TrackerInterface
    {
        $json = $request->getBody()->__toString();
        $trackerData = JsonDataType::decodeJson($json);
        foreach ($trackerData['events'] as $eventData) {
            $eventType = mb_strtolower($eventData['type']);
            $eventSheet = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Analytics.event');
            $eventSheet->addRow([
                'event_type' => $eventType,
                'tracker' => $this->getUid(),
                'timestamp' => $eventData['ts'],
                'date' => DateDataType::cast($eventData['ts']),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'request_id' => $eventData['xrId'] ?? null,
                'source_id' => $trackerData['dId'] ?? null,
                'properties_json' => $json
            ]);
            $eventSheet->dataCreate(false);
            $this->saveEvent($eventData, $eventType, $eventSheet->getUidColumn()->getValue(0), $request);
        }
        return $this;
    }

    /**
     * @param array $eventData
     * @param string $eventType
     * @param string $eventUid
     * @param ServerRequestInterface|null $request
     * @return TrackerInterface
     */
    abstract protected function saveEvent(array $eventData, string $eventType, string $eventUid, ?ServerRequestInterface $request) : TrackerInterface;

    /**
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\WorkbenchDependantInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->workbench;
    }
}