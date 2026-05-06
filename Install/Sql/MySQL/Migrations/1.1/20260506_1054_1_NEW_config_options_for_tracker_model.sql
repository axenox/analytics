-- UP

ALTER TABLE `tracker`
ADD `prototype_path` varchar(200) COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `project_oid`;

ALTER TABLE `tracker`
ADD `origin` varchar(200) COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `description`;

-- DOWN

-- DO NOT remove columns to avoid accidental data loss