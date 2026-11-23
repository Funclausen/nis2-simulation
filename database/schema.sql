-- NIS2 simulator - MySQL schema for hannestad.dk/private/nis2
-- No credentials belong in this file.

CREATE TABLE nis2_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_code VARCHAR(12) NOT NULL UNIQUE,
  title VARCHAR(160) NOT NULL DEFAULT 'NIS2 Ledelsessimulation',
  status ENUM('waiting','active','finished') NOT NULL DEFAULT 'waiting',
  active_round TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expected_participants SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  reset_version BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nis2_teams (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT UNSIGNED NOT NULL,
  browser_id VARCHAR(64) NOT NULL,
  team_name VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_session_browser (session_id, browser_id),
  CONSTRAINT fk_team_session FOREIGN KEY (session_id) REFERENCES nis2_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nis2_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT UNSIGNED NOT NULL,
  team_id BIGINT UNSIGNED NOT NULL,
  round_no TINYINT UNSIGNED NOT NULL,
  quality ENUM('good','neutral','bad') NOT NULL,
  short_text VARCHAR(500) NOT NULL,
  answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_team_round (session_id, team_id, round_no),
  INDEX idx_session_round (session_id, round_no),
  CONSTRAINT fk_answer_session FOREIGN KEY (session_id) REFERENCES nis2_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_answer_team FOREIGN KEY (team_id) REFERENCES nis2_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
