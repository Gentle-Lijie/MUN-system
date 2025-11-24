-- Adminer 5.3.1-dev MySQL 8.0.42-0ubuntu0.20.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `CommitteeSessions`;
CREATE TABLE `CommitteeSessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `committee_id` int NOT NULL,
  `topic` varchar(255) NOT NULL,
  `chair` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `duration_minutes` int DEFAULT '20',
  `current_speaker_list_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_committee_sessions_committee` (`committee_id`),
  KEY `fk_current_speaker_list` (`current_speaker_list_id`),
  CONSTRAINT `CommitteeSessions_ibfk_1` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`),
  CONSTRAINT `fk_current_speaker_list` FOREIGN KEY (`current_speaker_list_id`) REFERENCES `SpeakerLists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `CommitteeSessions` (`id`, `committee_id`, `topic`, `chair`, `start_time`, `duration_minutes`, `current_speaker_list_id`, `created_at`, `updated_at`) VALUES
(1,	1,	'第一议程',	NULL,	'2025-11-22 02:31:11',	60,	NULL,	'2025-11-21 10:31:23',	'2025-11-21 10:31:23'),
(2,	1,	'第二阶段',	NULL,	'2025-11-22 02:44:00',	200,	NULL,	'2025-11-21 10:44:32',	'2025-11-21 10:44:32'),
(3,	1,	'1',	'1',	'2025-11-22 10:05:00',	20,	9,	'2025-11-21 18:05:33',	'2025-11-23 01:54:53');

DROP TABLE IF EXISTS `Committees`;
CREATE TABLE `Committees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `description` text,
  `status` enum('preparation','in_session','paused','closed') DEFAULT 'preparation',
  `capacity` int DEFAULT '40',
  `agenda_order` json DEFAULT NULL,
  `dais_json` json DEFAULT NULL,
  `time_config` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  KEY `idx_committees_code` (`code`),
  CONSTRAINT `Committees_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Committees` (`id`, `code`, `name`, `venue`, `description`, `status`, `capacity`, `agenda_order`, `dais_json`, `time_config`, `created_by`, `created_at`, `updated_at`) VALUES
(1,	'GA-1',	'联合国大会',	'NICC-3',	NULL,	'in_session',	40,	NULL,	'[]',	'{\"flowSpeed\": 60, \"updateTime\": \"2025-11-23T09:33:20+00:00\", \"realTimeAnchor\": \"2012-01-08T03:06\"}',	1,	'2025-11-21 18:23:26',	'2025-11-23 01:35:07');

DROP TABLE IF EXISTS `Crises`;
CREATE TABLE `Crises` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `published_by` int NOT NULL,
  `published_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `target_committees` json DEFAULT NULL,
  `status` enum('draft','active','resolved','archived') NOT NULL DEFAULT 'active',
  `responses_allowed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `published_by` (`published_by`),
  CONSTRAINT `Crises_ibfk_1` FOREIGN KEY (`published_by`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Crises` (`id`, `title`, `content`, `file_path`, `published_by`, `published_at`, `target_committees`, `status`, `responses_allowed`, `created_at`, `updated_at`) VALUES
(1,	'自动化测试危机',	'这是用于验证数据库迁移后的自动化测试内容',	NULL,	1,	'2025-11-23 09:20:50',	NULL,	'active',	1,	'2025-11-23 09:20:50',	'2025-11-23 09:20:50'),
(2,	'测试危机1',	'测试危机1',	'/attachments/1/2025-11-23 17-21-07.pdf',	1,	'2025-11-23 09:21:07',	'[1]',	'active',	1,	'2025-11-23 09:21:07',	'2025-11-23 09:21:07');

DROP TABLE IF EXISTS `CrisisResponses`;
CREATE TABLE `CrisisResponses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crisis_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` json NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `CrisisResponses` (`id`, `crisis_id`, `user_id`, `content`, `file_path`, `created_at`, `updated_at`) VALUES
(1,	2,	1,	'{\"actions\": null, \"summary\": \"1\", \"resources\": null}',	NULL,	'2025-11-23 09:46:23',	'2025-11-23 09:46:23');

DROP TABLE IF EXISTS `Delegates`;
CREATE TABLE `Delegates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `committee_id` int NOT NULL,
  `country` varchar(255) NOT NULL,
  `veto_allowed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('present','absent') DEFAULT 'absent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_committee` (`user_id`,`committee_id`),
  KEY `idx_delegates_committee` (`committee_id`),
  CONSTRAINT `Delegates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`),
  CONSTRAINT `Delegates_ibfk_2` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Delegates` (`id`, `user_id`, `committee_id`, `country`, `veto_allowed`, `created_at`, `updated_at`, `status`) VALUES
(1,	4,	1,	'CHINA',	1,	'2025-11-21 18:57:22',	'2025-11-23 07:00:54',	'present'),
(2,	5,	1,	'USA',	0,	'2025-11-23 00:28:49',	'2025-11-23 09:35:43',	'present');

DROP TABLE IF EXISTS `Files`;
CREATE TABLE `Files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `committee_id` int DEFAULT NULL,
  `type` enum('position_paper','working_paper','draft_resolution','press_release','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `content_path` varchar(500) DEFAULT NULL,
  `submitted_by` int NOT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','submitted','approved','published','rejected') DEFAULT 'draft',
  `visibility` enum('committee_only','all_committees','public') DEFAULT 'committee_only',
  `dias_fb` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `idx_files_committee` (`committee_id`),
  KEY `idx_files_status` (`status`),
  CONSTRAINT `Files_ibfk_1` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`),
  CONSTRAINT `Files_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Files` (`id`, `committee_id`, `type`, `title`, `description`, `content_path`, `submitted_by`, `submitted_at`, `status`, `visibility`, `dias_fb`, `created_at`, `updated_at`) VALUES
(1,	1,	'working_paper',	'test1',	'123',	'/attachments/1-1763901456.pdf',	1,	'2025-11-23 12:37:37',	'approved',	'committee_only',	'',	'2025-11-23 04:37:36',	'2025-11-23 06:30:23'),
(2,	1,	'working_paper',	'uploaded via api test',	NULL,	'/attachments/1/2025-11-23-14-51-14.pdf',	1,	'2025-11-23 14:53:31',	'draft',	'public',	NULL,	'2025-11-23 06:53:30',	'2025-11-23 08:09:40'),
(3,	NULL,	'working_paper',	'测试集',	'123',	'/attachments/3/2025-11-24 16-44-24.pdf',	3,	'2025-11-24 16:44:24',	'submitted',	'committee_only',	NULL,	'2025-11-24 08:44:24',	'2025-11-24 08:44:24');

DROP TABLE IF EXISTS `Logs`;
CREATE TABLE `Logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actor_user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `meta_json` json DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `actor_user_id` (`actor_user_id`),
  KEY `idx_logs_action` (`action`),
  CONSTRAINT `Logs_ibfk_1` FOREIGN KEY (`actor_user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `Messages`;
CREATE TABLE `Messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `from_user_id` int NOT NULL,
  `target_id` int DEFAULT NULL,
  `channel` enum('private','committee','global','dais') NOT NULL,
  `target` enum('everyone','delegate','committee','dias') NOT NULL DEFAULT 'everyone',
  `committee_id` int DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `committee_id` (`committee_id`),
  KEY `idx_messages_channel` (`channel`),
  KEY `idx_messages_target` (`target`),
  KEY `idx_messages_target_id` (`target_id`),
  CONSTRAINT `Messages_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `Users` (`id`),
  CONSTRAINT `Messages_ibfk_3` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Messages` (`id`, `time`, `from_user_id`, `target_id`, `channel`, `target`, `committee_id`, `content`, `created_at`) VALUES
(1,	'2025-11-24 08:41:03',	1,	NULL,	'global',	'everyone',	NULL,	'test message',	'2025-11-24 16:41:03'),
(2,	'2025-11-24 08:41:07',	1,	NULL,	'global',	'everyone',	NULL,	'大家好，原神是一款由米哈游开发的开放世界探险游戏。',	'2025-11-24 16:41:08'),
(3,	'2025-11-24 08:47:17',	3,	NULL,	'global',	'everyone',	NULL,	'123',	'2025-11-24 16:47:17'),
(4,	'2025-11-24 08:56:03',	1,	NULL,	'global',	'everyone',	NULL,	'111',	'2025-11-24 16:56:03'),
(5,	'2025-11-24 08:56:18',	3,	NULL,	'global',	'everyone',	NULL,	'66',	'2025-11-24 16:56:18'),
(6,	'2025-11-24 09:04:53',	1,	NULL,	'global',	'everyone',	NULL,	'通知测试',	'2025-11-24 17:04:53'),
(7,	'2025-11-24 09:08:06',	1,	NULL,	'global',	'everyone',	NULL,	'通知测试',	'2025-11-24 17:08:06'),
(8,	'2025-11-24 09:08:22',	1,	NULL,	'global',	'everyone',	NULL,	'撒发斯蒂芬',	'2025-11-24 17:08:22'),
(9,	'2025-11-24 09:11:16',	1,	NULL,	'global',	'everyone',	NULL,	'sfas',	'2025-11-24 17:11:16'),
(10,	'2025-11-24 09:11:35',	1,	NULL,	'global',	'everyone',	NULL,	'韩天诚太可爱了',	'2025-11-24 17:11:35');

DROP TABLE IF EXISTS `Motions`;
CREATE TABLE `Motions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `committee_session_id` int DEFAULT NULL,
  `motion_type` enum('open_main_list','moderate_caucus','unmoderated_caucus','unmoderated_debate','right_of_query','begin_special_state','end_special_state','adjourn_meeting','document_reading','personal_speech','vote','right_of_reply') COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposer_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `unit_time_seconds` int DEFAULT NULL,
  `total_time_seconds` int DEFAULT NULL,
  `speaker_list_id` int DEFAULT NULL,
  `vote_required` tinyint(1) DEFAULT '0',
  `veto_applicable` tinyint(1) DEFAULT '0',
  `state` enum('passed','rejected','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `vote_result` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `committee_session_id` (`committee_session_id`),
  KEY `proposer_id` (`proposer_id`),
  KEY `speaker_list_id` (`speaker_list_id`),
  CONSTRAINT `Motions_ibfk_1` FOREIGN KEY (`committee_session_id`) REFERENCES `CommitteeSessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Motions_ibfk_2` FOREIGN KEY (`proposer_id`) REFERENCES `Delegates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `Motions_ibfk_3` FOREIGN KEY (`speaker_list_id`) REFERENCES `SpeakerLists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `Motions` (`id`, `committee_session_id`, `motion_type`, `proposer_id`, `file_id`, `unit_time_seconds`, `total_time_seconds`, `speaker_list_id`, `vote_required`, `veto_applicable`, `state`, `vote_result`, `created_at`, `updated_at`) VALUES
(1,	3,	'open_main_list',	1,	NULL,	120,	1200,	9,	0,	0,	'passed',	NULL,	'2025-11-23 00:17:36',	'2025-11-23 09:45:42'),
(4,	3,	'moderate_caucus',	NULL,	NULL,	120,	1200,	10,	0,	0,	'passed',	NULL,	'2025-11-23 01:20:48',	'2025-11-23 01:20:48'),
(5,	3,	'unmoderated_caucus',	NULL,	NULL,	120,	1200,	NULL,	0,	0,	'passed',	NULL,	'2025-11-23 01:35:41',	'2025-11-23 01:35:41'),
(6,	3,	'open_main_list',	NULL,	NULL,	300,	1800,	NULL,	0,	0,	'passed',	NULL,	'2025-11-23 01:37:11',	'2025-11-23 01:37:11'),
(7,	3,	'vote',	NULL,	NULL,	120,	1200,	NULL,	0,	0,	'passed',	NULL,	'2025-11-23 01:39:20',	'2025-11-23 01:39:20'),
(8,	3,	'open_main_list',	NULL,	NULL,	120,	1200,	NULL,	0,	0,	'passed',	NULL,	'2025-11-23 01:45:14',	'2025-11-23 01:45:14');

DROP TABLE IF EXISTS `Sessions`;
CREATE TABLE `Sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `committee_id` bigint unsigned NOT NULL,
  `type` enum('main_list','moderated','unmoderated','special','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_time_seconds` int unsigned DEFAULT NULL,
  `total_time_seconds` int unsigned DEFAULT NULL,
  `proposer_id` bigint unsigned DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `vote_result` json DEFAULT NULL,
  `speaker_list_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `SpeakerListEntries`;
CREATE TABLE `SpeakerListEntries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `speaker_list_id` int NOT NULL,
  `delegate_id` int NOT NULL,
  `position` int NOT NULL,
  `status` enum('waiting','speaking','removed') DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_list_position` (`speaker_list_id`,`position`),
  KEY `delegate_id` (`delegate_id`),
  CONSTRAINT `SpeakerListEntries_ibfk_1` FOREIGN KEY (`speaker_list_id`) REFERENCES `SpeakerLists` (`id`),
  CONSTRAINT `SpeakerListEntries_ibfk_2` FOREIGN KEY (`delegate_id`) REFERENCES `Delegates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `SpeakerListEntries` (`id`, `speaker_list_id`, `delegate_id`, `position`, `status`, `created_at`, `updated_at`) VALUES
(3,	9,	2,	1,	'waiting',	'2025-11-23 00:37:11',	'2025-11-23 09:28:26'),
(4,	10,	2,	1,	'waiting',	'2025-11-23 01:21:09',	'2025-11-23 01:21:09'),
(5,	9,	1,	2,	'waiting',	'2025-11-23 01:28:41',	'2025-11-23 01:28:41');

DROP TABLE IF EXISTS `SpeakerLists`;
CREATE TABLE `SpeakerLists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `committee_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_speaker_lists_committee` (`committee_id`),
  CONSTRAINT `SpeakerLists_ibfk_1` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `SpeakerLists` (`id`, `committee_id`, `created_at`, `updated_at`) VALUES
(1,	1,	'2025-11-22 23:21:14',	'2025-11-22 23:21:14'),
(2,	1,	'2025-11-22 23:22:27',	'2025-11-22 23:22:27'),
(3,	1,	'2025-11-22 23:22:50',	'2025-11-22 23:22:50'),
(4,	1,	'2025-11-22 23:33:27',	'2025-11-22 23:33:27'),
(5,	1,	'2025-11-22 23:33:46',	'2025-11-22 23:33:46'),
(6,	1,	'2025-11-22 23:36:59',	'2025-11-22 23:36:59'),
(7,	1,	'2025-11-22 23:37:15',	'2025-11-22 23:37:15'),
(8,	1,	'2025-11-22 23:37:20',	'2025-11-22 23:37:20'),
(9,	1,	'2025-11-23 00:17:36',	'2025-11-23 00:17:36'),
(10,	1,	'2025-11-23 01:20:48',	'2025-11-23 01:20:48');

DROP TABLE IF EXISTS `Timelines`;
CREATE TABLE `Timelines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `committee_id` int NOT NULL,
  `real_time` timestamp NOT NULL,
  `simulation_time` timestamp NULL DEFAULT NULL,
  `flow_speed` decimal(3,1) DEFAULT '1.0',
  `note` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_timelines_committee` (`committee_id`),
  CONSTRAINT `Timelines_ibfk_1` FOREIGN KEY (`committee_id`) REFERENCES `Committees` (`id`),
  CONSTRAINT `Timelines_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','dais','delegate','observer') NOT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session_token` varchar(255) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES
(1,	'测试账户',	'test@example.com',	'$2y$12$1bPNqeDtKLfz/tPFIHQWweUy1gfkOTcuau4WWrpqazsD2cgLbmjpW',	'admin',	'UNNC',	NULL,	'2025-11-24 03:49:29',	'2025-11-21 16:45:39',	'2025-11-24 03:49:29',	'b4f79c14f9e97aa33c4044fc8c5c7e5b1100984b0bfec4a257c26afab4bf27f4',	'[\"users:manage\", \"presidium:manage\", \"delegates:manage\", \"logs:read\", \"timeline:update\", \"crisis:dispatch\", \"messages:broadcast\", \"observer:read\", \"reports:view\", \"delegate:self\", \"documents:submit\", \"messages:send\"]'),
(2,	'主席团测试1',	'dais1@test.com',	'$2y$12$4neuwKP6k7psUWxkZqMiM.KAvW7bbf7UI74DRA0boIVKLTuYZMN5C',	'dais',	NULL,	NULL,	NULL,	'2025-11-21 16:45:39',	'2025-11-22 22:38:45',	NULL,	'[\"timeline:update\", \"crisis:dispatch\", \"messages:broadcast\", \"presidium:manage\"]'),
(3,	'主席团测试2',	'dais2@test.com',	'$2y$12$ZS42/4gYXtz4etDjrGm8zuUI6I9Oue.lIajl3EM4xR9DFoRh9KI1O',	'dais',	NULL,	NULL,	'2025-11-24 08:41:30',	'2025-11-21 16:45:39',	'2025-11-24 08:41:30',	'12a2ea21d6720aa92090c50ae86f953a1af0c15b6ad716a8a015a4d0d8d5e263',	NULL),
(4,	'代表测试1',	'del1@test.com',	'$2y$12$4sUyKISTDgVCvz/WRVIpgOHGgg257uILQoocn/lquEKyCd60S9g8.',	'delegate',	NULL,	NULL,	NULL,	'2025-11-21 16:45:39',	'2025-11-23 06:34:58',	NULL,	NULL),
(5,	'代表测试2',	'del2@test.com',	'$2y$12$IdVHTgET7ElHqiRHxQ45KO8t1jazj91EZUu0iV08ECDVhCQROddbW',	'delegate',	NULL,	NULL,	NULL,	'2025-11-21 16:45:39',	'2025-11-23 06:34:59',	NULL,	NULL),
(6,	'观察员测试1',	'observer1@test.com',	'$2y$12$GnOJYlAWf/2tkpBKCZXEpe/jB83a89hc/XNgWZQw4uhQIPkpp91sW',	'observer',	NULL,	NULL,	NULL,	'2025-11-21 16:45:39',	'2025-11-23 06:34:59',	NULL,	NULL),
(7,	'观察员测试2',	'observer2@test.com',	'$2y$12$dfgJ9q/LzKHMGprm2gTAsOBbwi783zwIMV5h1vxtj/V0Mk5kT0mF2',	'observer',	NULL,	NULL,	NULL,	'2025-11-21 16:45:39',	'2025-11-23 06:34:59',	NULL,	NULL);

DROP TABLE IF EXISTS `Votes`;
CREATE TABLE `Votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `motion_id` int NOT NULL,
  `voter_delegate_id` int NOT NULL,
  `vote` enum('yes','no','abstain') NOT NULL,
  `is_veto` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_motion_voter` (`motion_id`,`voter_delegate_id`),
  KEY `voter_delegate_id` (`voter_delegate_id`),
  KEY `idx_votes_motion` (`motion_id`),
  CONSTRAINT `Votes_ibfk_1` FOREIGN KEY (`motion_id`) REFERENCES `Motions` (`id`),
  CONSTRAINT `Votes_ibfk_2` FOREIGN KEY (`voter_delegate_id`) REFERENCES `Delegates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-11-24 17:53:24 UTC