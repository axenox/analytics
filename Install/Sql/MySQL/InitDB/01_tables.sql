
CREATE TABLE IF NOT EXISTS `project` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) NOT NULL,
    `modified_by_user_oid` binary(16) NOT NULL,
    `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`oid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `tracker` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) NOT NULL,
    `modified_by_user_oid` binary(16) NOT NULL,
    `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `project_oid` binary(16) NOT NULL,
    `config_uxon` longtext COLLATE utf8mb4_general_ci,
    PRIMARY KEY (`oid`) USING BTREE,
    KEY `project_oid` (`project_oid`),
    CONSTRAINT `FK_tracker_project` FOREIGN KEY (`project_oid`) REFERENCES `project` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `dimension` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) NOT NULL,
    `modified_by_user_oid` binary(16) NOT NULL,
    `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `config_uxon` longtext COLLATE utf8mb4_general_ci,
    PRIMARY KEY (`oid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `event` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) NOT NULL,
    `modified_by_user_oid` binary(16) NOT NULL,
    `tracker_oid` binary(16) NOT NULL,
    `timestamp` datetime NOT NULL,
    `date` date NOT NULL,
    `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `properties_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `source_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `request_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`oid`) USING BTREE,
    KEY `tracker_oid` (`tracker_oid`),
    CONSTRAINT `FK_event_tracker` FOREIGN KEY (`tracker_oid`) REFERENCES `tracker` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `action` (
    `oid` binary(16) NOT NULL,
    `created_on` datetime NOT NULL,
    `modified_on` datetime NOT NULL,
    `created_by_user_oid` binary(16) NOT NULL,
    `modified_by_user_oid` binary(16) NOT NULL,
    `tracker_oid` binary(16) NOT NULL,
    `event_oid` binary(16) DEFAULT NULL,
    `action_alias` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `action_object_alias` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `page_alias` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `widget_id` varchar(2000) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `request_data_object_alias` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `request_data_columns` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `request_data_filters` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `request_data_sorters` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `request_data_aggregators` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `request_data_row_count` int DEFAULT NULL,
    `response_data_columns` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `response_data_row_count` int DEFAULT NULL,
    `duration_ms` int DEFAULT NULL,
    `duration_server_ms` int DEFAULT NULL,
    `duration_network_ms` int DEFAULT NULL,
    PRIMARY KEY (`oid`) USING BTREE,
    KEY `tracker_oid` (`tracker_oid`),
    KEY `event_oid` (`event_oid`),
    CONSTRAINT `FK_action_tracker` FOREIGN KEY (`tracker_oid`) REFERENCES `tracker` (`oid`),
    CONSTRAINT `FK_action_event` FOREIGN KEY (`event_oid`) REFERENCES `event` (`oid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;