<?php
namespace axenox\Analytics\Interfaces;

use exface\Core\Interfaces\Facades\HttpFacadeInterface;

/**
 * 
 * 
 * @author Andrej Kabachnik
 */
interface AnalyticsFacadeInterface extends HttpFacadeInterface
{
    /**
     * Returns the URL to download the JS tracker code
     *
     * @param JsTrackerInterface $tracker
     * @param bool $relativeToSiteRoot
     * @return mixed
     */
    public function buildUrlToTrackerDownload(JsTrackerInterface $tracker, bool $relativeToSiteRoot = false) : string;
}