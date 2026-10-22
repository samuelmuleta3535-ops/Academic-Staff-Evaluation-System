-- Run ONCE in phpMyAdmin after selecting the existing acedamic_staff database.
-- Gives each evaluator an explicit course assignment and its own start/deadline window.
CREATE TABLE IF NOT EXISTS evaluation_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evaluator_user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  evaluator_type ENUM('student','department_head','peer','self') NOT NULL,
  starts_at DATETIME NOT NULL,
  deadline_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_assignment_user_window (evaluator_user_id, is_active, starts_at, deadline_at),
  KEY idx_assignment_course (course_id),
  CONSTRAINT fk_assignment_user FOREIGN KEY (evaluator_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_assignment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_assignment_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evaluations ADD COLUMN assignment_id BIGINT UNSIGNED NULL AFTER course_id;
ALTER TABLE evaluations ADD KEY idx_evaluations_assignment_id (assignment_id);
ALTER TABLE evaluations ADD CONSTRAINT fk_evaluations_assignment FOREIGN KEY (assignment_id) REFERENCES evaluation_assignments(id) ON DELETE SET NULL;
ALTER TABLE evaluations ADD UNIQUE KEY uq_evaluation_one_submission_per_assignment (assignment_id);
