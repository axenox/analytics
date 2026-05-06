<?php
namespace axenox\Analytics\Analytics\Tracker;

use axenox\Analytics\Common\AbstractTracker;
use axenox\Analytics\Interfaces\TrackerInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\JsonDataType;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\DataSheetMapperFactory;
use exface\Core\Formulas\Today;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tracks AI agent conversations, messages, tool calls and ratings
 * 
 * Aggregates AI statistics on a daily basis, collecting metrics about agent interactions, 
 * LLM model usage, message counts, tool invocations, ratings and costs.
 * 
 * @author Andrej Kabachnik
 */
class AITracker extends AbstractTracker
{
    const EVENT_TYPE_CONVERSATIONS_PER_DAY = 'conversations_per_day';
    
    /**
     * {@inheritDoc}
     * @see AbstractTracker::saveEvent()
     */
    protected function saveEvent(array $eventData, string $eventType, string $eventUid, ?ServerRequestInterface $request) : TrackerInterface
    {
        switch ($eventType) {
            case self::EVENT_TYPE_CONVERSATIONS_PER_DAY:
                $this->saveEventForAiStats($eventData, $eventUid);
                break;
        }
        return $this;
    }

    /**
     * Saves AI statistics event data to the ai_stats_per_day table
     * 
     * @param array $eventData The raw event data from the tracker
     * @param string $eventUid The UID of the created event record
     * @return void
     */
    protected function saveEventForAiStats(array $eventData, string $eventUid)
    {
        $aiStatsSheet = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Analytics.ai_conversations_per_day');
        $aiStatsSheet->addRow([
            'tracker' => $this->getUid(),
            'event' => $eventUid,
            'date' => $eventData['date'] ?? DateDataType::now(),
            'agent_name' => $eventData['agent_name'] ?? '',
            'llm_model_name' => $eventData['llm_model_name'] ?? '',
            'count_conversations' => $eventData['count_conversations'] ?? 0,
            'count_messages' => $eventData['count_messages'] ?? 0,
            'count_tool_calls' => $eventData['count_tool_calls'] ?? 0,
            'count_ratings' => $eventData['count_ratings'] ?? 0,
            'avg_rating' => !empty($eventData['avg_rating']) ? $eventData['avg_rating'] : null,
            'sum_cost' => $eventData['sum_cost'] ?? 0,
        ]);

        $aiStatsSheet->dataCreate(false);
    }
}