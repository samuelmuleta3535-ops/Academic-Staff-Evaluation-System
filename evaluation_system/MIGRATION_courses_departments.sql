-- Run once in phpMyAdmin after selecting the existing acedamic_staff database.
CREATE TABLE IF NOT EXISTS departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO departments(name)
SELECT DISTINCT TRIM(department) FROM users WHERE department IS NOT NULL AND TRIM(department) <> '';
INSERT IGNORE INTO departments(name)
SELECT DISTINCT TRIM(department) FROM instructors WHERE department IS NOT NULL AND TRIM(department) <> '';

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id INT UNSIGNED NOT NULL,
  course_code VARCHAR(60) NOT NULL,
  course_title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_course_department_code (department_id, course_code),
  KEY idx_courses_department_active (department_id, is_active),
  CONSTRAINT fk_courses_department FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evaluations ADD COLUMN course_id INT UNSIGNED NULL AFTER instructor_id;
ALTER TABLE evaluations ADD KEY idx_evaluations_course_id (course_id);
ALTER TABLE evaluations ADD CONSTRAINT fk_evaluations_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL;
