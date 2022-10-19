CREATE TABLE `ext_wol_group` (
 `id` int NOT NULL AUTO_INCREMENT,
 `parent_wol_group_id` int DEFAULT NULL,
 `name` text NOT NULL,
 PRIMARY KEY (`id`),
 KEY `fk_wol_group_1` (`parent_wol_group_id`),
 CONSTRAINT `fk_wol_group_1` FOREIGN KEY (`parent_wol_group_id`) REFERENCES `ext_wol_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ext_wol_schedule` (
 `id` int NOT NULL AUTO_INCREMENT,
 `wol_group_id` int NOT NULL,
 `name` text NOT NULL,
 `monday` tinytext,
 `tuesday` tinytext,
 `wednesday` tinytext,
 `thursday` tinytext,
 `friday` tinytext,
 `saturday` tinytext,
 `sunday` tinytext,
 PRIMARY KEY (`id`),
 KEY `fk_wol_schedule_1` (`wol_group_id`),
 CONSTRAINT `fk_wol_schedule_1` FOREIGN KEY (`wol_group_id`) REFERENCES `ext_wol_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ext_wol_plan` (
 `id` int NOT NULL AUTO_INCREMENT,
 `wol_group_id` int NOT NULL,
 `computer_group_id` int NOT NULL,
 `wol_schedule_id` int NOT NULL,
 `shutdown_credential` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
 `start_time` datetime DEFAULT NULL,
 `end_time` datetime DEFAULT NULL,
 `description` text NOT NULL,
 PRIMARY KEY (`id`),
 KEY `fk_wol_plan_1` (`computer_group_id`),
 KEY `fk_wol_plan_2` (`wol_schedule_id`),
 KEY `fk_wol_plan_3` (`wol_group_id`),
 CONSTRAINT `fk_wol_plan_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fk_wol_plan_2` FOREIGN KEY (`wol_schedule_id`) REFERENCES `ext_wol_schedule` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_wol_plan_3` FOREIGN KEY (`wol_group_id`) REFERENCES `ext_wol_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ext_wol_shutdown_flag` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `computer_id` int(11) NOT NULL,
 `valid_until` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `fk_wol_shutdown_flag_1` (`computer_id`),
 CONSTRAINT `fk_wol_shutdown_flag_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
