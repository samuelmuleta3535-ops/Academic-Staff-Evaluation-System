-- Run once in phpMyAdmin after selecting the existing acedamic_staff database.
-- This adds private messages/notifications. It does not alter or delete evaluation data.
CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_user_id INT UNSIGNED NOT NULL,
  recipient_user_id INT UNSIGNED NOT NULL,
  instructor_id INT UNSIGNED DEFAULT NULL,
  evaluation_id BIGINT UNSIGNED DEFAULT NULL,
  subject VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  message_type VARCHAR(80) NOT NULL DEFAULT 'Official Message',
  read_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_recipient_read (recipient_user_id, read_at, created_at),
  KEY idx_messages_instructor (instructor_id),
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id),
  CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id),
  CONSTRAINT fk_messages_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL,
  CONSTRAINT fk_messages_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Required by the administrator-only Clear Workflow Events feature.
CREATE TABLE IF NOT EXISTS admin_audit_log (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_user_id INT UNSIGNED NOT NULL,
 action VARCHAR(80) NOT NULL,
 details TEXT DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_admin_audit_actor (actor_user_id),
 KEY idx_admin_audit_created (created_at),
 CONSTRAINT fk_admin_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
