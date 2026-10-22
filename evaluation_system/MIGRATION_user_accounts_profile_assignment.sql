-- ASES update: run once in phpMyAdmin after selecting the existing acedamic_staff database.
-- Back up the database first.

-- Store each user's optional profile photo path.
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER instructor_id;

-- Link each evaluator/course/time-window assignment to the instructor being evaluated.
ALTER TABLE evaluation_assignments ADD COLUMN instructor_id INT UNSIGNED NULL AFTER course_id;

-- For existing assignments, attempt to link an instructor in the same department as the assigned course.
-- Review/recreate any assignments still missing an instructor after migration.
UPDATE evaluation_assignments a
JOIN courses c ON c.id = a.course_id
JOIN departments d ON d.id = c.department_id
JOIN instructors i ON i.department = d.name AND i.is_active = 1
SET a.instructor_id = i.id
WHERE a.instructor_id IS NULL;

ALTER TABLE evaluation_assignments ADD KEY idx_assignment_instructor (instructor_id);
ALTER TABLE evaluation_assignments ADD CONSTRAINT fk_assignment_instructor
  FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE RESTRICT;
