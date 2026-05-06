-- UP

CREATE TABLE IF NOT EXISTS `ai_conversations_per_day` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) DEFAULT NULL,
    `modified_by_user_oid` binary(16) DEFAULT NULL,
    `tracker_oid` binary(16) NOT NULL,
    `event_oid` binary(16) DEFAULT NULL,
    `date` date NOT NULL,
    `agent_name` varchar(150) NOT NULL,
    `llm_model_name` varchar(100) NOT NULL,
    `count_conversations` int NOT NULL,
    `count_messages` int NOT NULL,
    `count_tool_calls` int NOT NULL,
    `count_ratings` int NOT NULL,
    `avg_rating` float DEFAULT NULL,
    `sum_cost` decimal(20,6) NOT NULL,
    PRIMARY KEY (`oid`) USING BTREE,
    KEY `ai_conversations_per_day_tracker` (`tracker_oid`) USING BTREE,
    KEY `ai_conversations_per_day_event` (`event_oid`),
    CONSTRAINT `ai_conversations_per_day_event` FOREIGN KEY (`event_oid`) REFERENCES `event` (`oid`) ON DELETE SET NULL ON UPDATE SET NULL,
    CONSTRAINT `ai_conversations_per_day_tracker` FOREIGN KEY (`tracker_oid`) REFERENCES `tracker` (`oid`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

-- DOWN

-- DO NOT drop tables to avoid accidental data loss