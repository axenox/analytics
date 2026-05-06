-- UP

ALTER TABLE `event`
    ADD INDEX `timestamp` (`timestamp`),
	ADD INDEX `date` (`date`);

-- DOWN

ALTER TABLE `event`
    DROP INDEX `timestamp`,
    DROP INDEX `date`;