-- Teamwork Flow Management System central state table
-- Select your Hostinger-created database before running this SQL.
CREATE TABLE IF NOT EXISTS tfms_app_state (
  state_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  state_json JSON NOT NULL,
  updated_by VARCHAR(80) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tfms_app_state (state_id, state_json, updated_by)
VALUES (1, JSON_OBJECT('tasks', JSON_ARRAY(), 'standups', JSON_ARRAY(), 'notes', JSON_ARRAY()), 'system')
ON DUPLICATE KEY UPDATE state_id = state_id;
