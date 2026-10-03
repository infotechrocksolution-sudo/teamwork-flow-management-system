-- Teamwork Flow Management System central state table
-- Version supports optimistic concurrency so stale screens cannot silently overwrite newer saves.
CREATE TABLE IF NOT EXISTS tfms_app_state (
  state_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  state_json JSON NOT NULL,
  updated_by VARCHAR(80) NULL,
  version BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tfms_app_state (state_id, state_json, updated_by, version)
VALUES (1, JSON_OBJECT('tasks', JSON_ARRAY(), 'standups', JSON_ARRAY(), 'notes', JSON_ARRAY()), 'system', 0)
ON DUPLICATE KEY UPDATE state_id = state_id;

-- Existing installations are upgraded automatically by api.php when the version column is missing.
