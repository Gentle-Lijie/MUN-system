-- MUN System Database Creation Script
-- Based on 数据库结构 - 更新.md
-- MySQL 8.0+ compatible
Drop database if exists mun;

CREATE Database IF NOT EXISTS mun;

use mun;

-- Drop tables in reverse dependency order to avoid foreign key constraints
DROP TABLE IF EXISTS Logs;

DROP TABLE IF EXISTS Messages;

DROP TABLE IF EXISTS Timelines;

DROP TABLE IF EXISTS Crises;

DROP TABLE IF EXISTS Votes;

DROP TABLE IF EXISTS Motions;

DROP TABLE IF EXISTS SpeakerListEntries;

DROP TABLE IF EXISTS Sessions;

DROP TABLE IF EXISTS CommitteeSessions;

DROP TABLE IF EXISTS SpeakerLists;

DROP TABLE IF EXISTS Files;

DROP TABLE IF EXISTS Delegates;

DROP TABLE IF EXISTS Committees;

DROP TABLE IF EXISTS Users;

-- Core Identity Tables
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

BEGIN;
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (1, '测试账户', 'test@example.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'admin', 'UNNC', NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (2, '主席团测试1', 'dais1@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'dais', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (3, '主席团测试2', 'dais2@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'dais', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (4, '代表测试1', 'del1@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'delegate', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (5, '代表测试2', 'del2@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'delegate', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (6, '观察员测试1', 'observer1@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'observer', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
INSERT INTO `Users` (`id`, `name`, `email`, `password_hash`, `role`, `organization`, `phone`, `last_login`, `created_at`, `updated_at`, `session_token`, `permissions`) VALUES (7, '观察员测试2', 'observer2@test.com', 'scrypt:32768:8:1$fUqR5RRJVKklPwwS$a643c51c1cece0c98b437e5414e9bb736e5db7837caeabe30d738cfc7465f1a2391081ea07d5a24812ace095e2a4c518e0ac63518737c20d7bf8d5f403369924', 'observer', NULL, NULL, NULL, '2025-11-22 00:45:39', '2025-11-22 00:45:39', NULL, NULL);
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

-- 会场、代表与会期管理
CREATE TABLE
    Committees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(10) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        venue VARCHAR(255),
        description TEXT,
        status ENUM ('preparation', 'in_session', 'paused', 'closed') DEFAULT 'preparation',
        capacity INT DEFAULT 40,
        agenda_order JSON, -- Store agenda order or version info
        dais_json JSON, -- Structured info for dais members
        time_config JSON,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES Users (id)
    );

CREATE TABLE
    Delegates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        committee_id INT NOT NULL,
        country VARCHAR(255) NOT NULL,
        veto_allowed BOOLEAN DEFAULT FALSE,
        role_in_committee ENUM ('delegate', 'head_delegate', 'advisor') DEFAULT 'delegate',
        status ENUM ('active', 'absent') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users (id),
        FOREIGN KEY (committee_id) REFERENCES Committees (id),
        UNIQUE KEY unique_user_committee (user_id, committee_id)
    );

-- 发言名单、动议与投票
CREATE TABLE
    SpeakerLists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        unit_time INT, -- Unit time in seconds
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES Committees (id)
    );

CREATE TABLE
    Sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        type ENUM (
            'main_list',
            'moderated',
            'unmoderated',
            'special',
            'other'
        ) NOT NULL,
        unit_time_seconds INT,
        total_time_seconds INT,
        proposer_id INT,
        is_approved BOOLEAN DEFAULT FALSE,
        vote_result JSON, -- Structured vote tally data
        speaker_list_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES Committees (id),
        FOREIGN KEY (proposer_id) REFERENCES Delegates (id),
        FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists (id)
    );

CREATE TABLE
    CommitteeSessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        topic VARCHAR(255) NOT NULL,
        chair VARCHAR(255),
        start_time DATETIME,
        duration_minutes INT DEFAULT 20,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES Committees (id)
    );

CREATE TABLE
    SpeakerListEntries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        speaker_list_id INT NOT NULL,
        delegate_id INT NOT NULL,
        position INT NOT NULL,
        status ENUM ('waiting', 'speaking', 'removed') DEFAULT 'waiting',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists (id),
        FOREIGN KEY (delegate_id) REFERENCES Delegates (id),
        UNIQUE KEY unique_list_position (speaker_list_id, position)
    );

-- 文件、否决与危机相关
CREATE TABLE
    Files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT,
        type ENUM (
            'position_paper',
            'working_paper',
            'draft_resolution',
            'press_release',
            'other'
        ) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        content_path VARCHAR(500), -- Path to stored content
        submitted_by INT NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM (
            'draft',
            'submitted',
            'approved',
            'published',
            'rejected'
        ) DEFAULT 'draft',
        visibility ENUM ('committee_only', 'all_committees', 'public') DEFAULT 'committee_only',
        dias_fb TEXT, -- Dais feedback
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES Committees (id),
        FOREIGN KEY (submitted_by) REFERENCES Users (id)
    );

CREATE TABLE
    Motions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT,
        motion_type VARCHAR(100) NOT NULL, -- e.g., 'open_speaker_list', 'suspension'
        proposer_id INT,
        file_id INT,
        unit_time_seconds INT,
        total_time_seconds INT,
        requires_list BOOLEAN DEFAULT FALSE,
        vote_required BOOLEAN DEFAULT FALSE,
        veto_applicable BOOLEAN DEFAULT FALSE,
        state ENUM ('passed', 'rejected', 'pending') DEFAULT 'pending',
        vote_result JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (session_id) REFERENCES Sessions (id),
        FOREIGN KEY (proposer_id) REFERENCES Delegates (id),
        FOREIGN KEY (file_id) REFERENCES Files (id)
    );

CREATE TABLE
    Votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        motion_id INT NOT NULL,
        voter_delegate_id INT NOT NULL,
        vote ENUM ('yes', 'no', 'abstain') NOT NULL,
        is_veto BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (motion_id) REFERENCES Motions (id),
        FOREIGN KEY (voter_delegate_id) REFERENCES Delegates (id),
        UNIQUE KEY unique_motion_voter (motion_id, voter_delegate_id)
    );

CREATE TABLE
    Crises (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        file_path VARCHAR(500),
        published_by INT NOT NULL,
        published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        target_committees JSON, -- Array of committee IDs
        responses_allowed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (published_by) REFERENCES Users (id)
    );

-- 时间轴、消息与附件
CREATE TABLE
    Timelines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        real_time TIMESTAMP NOT NULL,
        simulation_time TIMESTAMP,
        flow_speed DECIMAL(3, 1) DEFAULT 1.0, -- e.g., 1.0 for 1x speed
        note TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES Committees (id),
        FOREIGN KEY (created_by) REFERENCES Users (id)
    );

CREATE TABLE
    Messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        from_user_id INT NOT NULL,
        to_user_id INT, -- NULL for channel messages
        channel ENUM ('private', 'committee', 'global', 'dais') NOT NULL,
        committee_id INT, -- For committee channel
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES Users (id),
        FOREIGN KEY (to_user_id) REFERENCES Users (id),
        FOREIGN KEY (committee_id) REFERENCES Committees (id)
    );

-- 管理、审计、报表与配置
CREATE TABLE
    Logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        actor_user_id INT,
        action VARCHAR(255) NOT NULL,
        target_table VARCHAR(100),
        target_id INT,
        meta_json JSON,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (actor_user_id) REFERENCES Users (id)
    );

-- Optional: Create indexes for better performance
CREATE INDEX idx_users_email ON Users (email);

CREATE INDEX idx_users_role ON Users (role);

CREATE INDEX idx_committees_code ON Committees (code);

CREATE INDEX idx_delegates_committee ON Delegates (committee_id);

CREATE INDEX idx_sessions_committee ON Sessions (committee_id);

CREATE INDEX idx_committee_sessions_committee ON CommitteeSessions (committee_id);

CREATE INDEX idx_speaker_lists_committee ON SpeakerLists (committee_id);

CREATE INDEX idx_motions_session ON Motions (session_id);

CREATE INDEX idx_votes_motion ON Votes (motion_id);

CREATE INDEX idx_files_committee ON Files (committee_id);

CREATE INDEX idx_files_status ON Files (status);

CREATE INDEX idx_timelines_committee ON Timelines (committee_id);

CREATE INDEX idx_messages_channel ON Messages (channel);

CREATE INDEX idx_logs_action ON Logs (action);