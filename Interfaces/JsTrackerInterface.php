<?php
namespace axenox\Analytics\Interfaces;

/**
 * A JavaScript tracker produces JS code to collect all required data
 * 
 * For JavaScript based facades, trackers do not even need to be explicitly supported - it is enough to include the
 * URL to donwload the tracker code in the HTML head tags and the resulting UI will start sending information. 
 * Additionally, auto-downloading the JS tracker code also means, that code is always up-to-date - even if the Analytics
 * app is updated.
 * 
 * @author Andrej Kabachnik
 */
interface JsTrackerInterface extends TrackerInterface
{
    public function buildJsTracker(AnalyticsFacadeInterface $facade) : string;
}