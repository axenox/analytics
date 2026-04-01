<?php
namespace axenox\Analytics\Facades;

use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\JsonDataType;
use exface\Core\Exceptions\RuntimeException;
use exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade;
use exface\Core\DataTypes\StringDataType;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Exceptions\Facades\HttpBadRequestError;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

/**
 * Web API for RUM (real user monitoring) - handles analytics trackers calls
 * 
 * ## Usage
 * 
 * To collect analytics from a web UI facade, create a corresponding project and tracker 
 * in `Analytics > Projects & trackers` and include its URL as an external script in the
 * corresponding facade config - e.g. in `config/exface.UI5Facade.config.json` in your
 * installation folder: 
 * 
 * ```
 * {
 *  "FACADE.EXTERNAL.SCRIPTS": [
 *      "api/analytics/0x11f183fb8905991083fb025041000001/tracker.js"
 *  ]
 * }
 * 
 * ```
 * 
 * Make sure to select a tracker prototype compatible with the facade used!
 * 
 * ## Routes
 * 
 * - `GET api/analytics/<tracker_uid>/tracker` - download an update
 * - `POST api/analytics/<tracker_uid>/event` - sent an event to the analytics platform
 * 
 * ## Event structure
 * 
 * ```
 * {
 *      "v": 1,
 *      "dId": "abc123", // device id
 *      "events": [
 *          {
 *              "xrId": "", // X-Request-ID
 *              "ts": "" // timestamp
 *              "type": "action",
 *              "page": "exface.core.apps",
 *              "widget": "DataTable",
 *              "action": {
 *                  "alias": "exface.Core.ReadData",
 *                  "object": "exface.Core.APP"
 *              },
 *              "request": {
 *                  "object": "exface.Core.APP",
 *                  "columns": ["NAME", "ALIAS"],
 *                  "filters": ["NAME"],
 *                  "sorters": ["CREATED_ON"],
 *                  "aggregators": [],
 *                  "limit": 20,
 *                  "offset": 0,
 *                  "rows": 0
 *              },
 *              "response": {}
 *          }
 *      ]     
 * }
 * ```
 * 
 * @author Andrej Kabachnik
 *
 */
class AnalyticsFacade extends AbstractHttpFacade
{
    const ROUTE_TRACKER = 'tracker.js';
    const ROUTE_EVENT = 'event';
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getUrlRouteDefault()
     */
    public function getUrlRouteDefault(): string
    {
        return 'api/analytics';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::createResponse()
     */
    protected function createResponse(ServerRequestInterface $request) : ResponseInterface
    {
        $uri = $request->getUri();
        // api/analytics/0x1qwfz1233rqwpr/tracker -> $trackerUid = 0x1qwfz1233rqwpr, $route=tracker
        $path = ltrim(StringDataType::substringAfter($uri->getPath(), $this->getUrlRouteDefault()), "/");
        list($trackerUid, $route) = explode('/', $path, 2);
        
        $headers = $this->buildHeadersCommon();
        $route = mb_strtolower($route);
        $method = $request->getMethod();
        switch (true) {
            case $route === self::ROUTE_TRACKER && $method === 'GET':
                $body = $this->buildJsTracker($trackerUid);
                $headers['Content-Type'] = 'application/javascript';
                return new Response(200, $headers, $body);
            case $route === self::ROUTE_EVENT && $method === 'POST':
                try {
                    $this->saveEvent($trackerUid, $request);
                } catch (\Throwable $e) {
                    $this->getWorkbench()->getLogger()->logException($e);
                }
                return new Response(200, $headers, '');
        }
        
        $e = new HttpBadRequestError($request, 'Cannot match route ' . $route);
        $this->getWorkbench()->getLogger()->logException($e);
        return $this->createResponseFromError($e, $request);
    }

    /**
     * TODO move this method to a tracker prototype class
     * 
     * @param string $trackerUid
     * @return string
     */
    protected function buildJsTracker(string $trackerUid) : string
    {
        $tplPath = $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'UI5Tracker.js';
        $tpl = file_get_contents($tplPath);
        if ($tpl === false) {
            throw new RuntimeException('Cannot read tracker template file from ' . $tplPath);
        }
        $phs = [
            '~tracker_uid' => $trackerUid,
            '~url' => $this->buildUrlToFacade(false) . '/' . $trackerUid . '/event',
        ];
        return StringDataType::replacePlaceholders($tpl, $phs);
    }
    
    protected function saveEvent(string $trackerUid, ServerRequestInterface $request)
    {
        $json = $request->getBody()->__toString();
        $trackerData = JsonDataType::decodeJson($json);
        foreach ($trackerData['events'] as $eventData) {
            $eventType = mb_strtolower($eventData['type']);
            $eventSheet = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Analytics.event');
            $eventSheet->addRow([
                'event_type' => $eventType,
                'tracker' => $trackerUid,
                'timestamp' => $eventData['ts'],
                'date' => DateDataType::cast($eventData['ts']),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'request_id' => $eventData['xrId'] ?? null,
                'source_id' => $trackerData['dId'] ?? null,
                'properties_json' => $json
            ]);
            $eventSheet->dataCreate(false);

            switch ($eventType) {
                case 'action':
                case 'widget':
                    $this->saveEventAction($eventSheet, $eventData);
                    break;
            }
        }
    }
    
    protected function saveEventAction(DataSheetInterface $eventSheet, array $eventProperties)
    {
        $actionSheet = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Analytics.action');
        $actionSheet->addRow([
            'tracker' => $eventSheet->getCellValue('tracker', 0),
            'event' => $eventSheet->getUidColumn()->getValue(0),
            'action_alias' => $eventProperties['action']['alias'],
            'action_object_alias' => $eventProperties['action']['object'],
            'page_alias' => $eventProperties['page'],
            'widget_id' => $eventProperties['widget'],
            'user' => $eventProperties['user'],
            'request_data_object_alias' => $eventProperties['request']['object'],
            'request_data_columns' => $eventProperties['request']['columns'] ? JsonDataType::encodeJson($eventProperties['request']['columns']) : null,
            'request_data_filters' => $eventProperties['request']['filters'] ? JsonDataType::encodeJson($eventProperties['request']['filters']) : null,
            'request_data_sorters' => $eventProperties['request']['sorters'] ? JsonDataType::encodeJson($eventProperties['request']['sorters']) : null,
            'request_data_aggregators' => $eventProperties['request']['aggregators'] ? JsonDataType::encodeJson($eventProperties['request']['aggregators']) : null,
            'request_data_row_count' => $eventProperties['request']['rows'],
            'response_data_columns' => $eventProperties['response']['columns'] ? JsonDataType::encodeJson($eventProperties['response']['columns']) : null,
            'response_data_row_count' => $eventProperties['response']['rows'],
            'duration_ms' => $eventProperties['duration'],
            'duration_server_ms' => $eventProperties['duration_server'],
            'duration_network_ms' => $eventProperties['duration'] - $eventProperties['duration_server'],
        ]);
        $actionSheet->dataCreate(false);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        /*
        $middleware[] = new AuthenticationMiddleware(
            $this,
            [
                [AuthenticationMiddleware::class, 'extractBasicHttpAuthToken']
            ]
        );*/
        
        return $middleware;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::buildHeadersCommon()
     */
    protected function buildHeadersCommon() : array
    {
        // TODO add more headers
        return parent::buildHeadersCommon();
    }
}