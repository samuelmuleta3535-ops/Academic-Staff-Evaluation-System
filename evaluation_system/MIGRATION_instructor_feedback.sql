CREATE TABLE IF NOT EXISTS instructor_feedback (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  instructor_id INT UNSIGNED NOT NULL,
  academic_year VARCHAR(80) NOT NULL DEFAULT '',
  head_user_id INT UNSIGNED NOT NULL,
  head_comment TEXT NOT NULL,
  instructor_reply TEXT NULL,
  replied_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_feedback_instructor_period (instructor_id, academic_year),
  KEY idx_feedback_head_user (head_user_id),
  CONSTRAINT fk_feedback_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_head_user FOREIGN KEY (head_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
