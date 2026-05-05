<?php
namespace axenox\Analytics\Interfaces;

use exface\Core\Interfaces\iCanBeConvertedToUxon;
use exface\Core\Interfaces\WorkbenchDependantInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Common interface for all analytics tracker prototypes
 * 
 * A tracker prototype takes care of processing received tracking requests and transforming them from a raw collection
 * of event data into the dedicated reporting structures. While the HTTP facade only resolves and instantiates the
 * tracker responsible for a URL, the tracker class decides, how the received data is to be processed.
 */
interface TrackerInterface extends iCanBeConvertedToUxon, WorkbenchDependantInterface
{
    public function getUid() : string;
    
    public function getExpectedOrigins() : array;

    /**
     * Process the received request and save all applicable event data
     * 
     * @param ServerRequestInterface $request
     * @return TrackerInterface
     */
    public function saveRequest(ServerRequestInterface $request) : TrackerInterface;
}