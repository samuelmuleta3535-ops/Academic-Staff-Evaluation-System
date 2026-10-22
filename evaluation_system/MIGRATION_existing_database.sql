-- Run this once in phpMyAdmin against the existing acedamic_staff database.
-- If instructor_id already exists, skip the first ALTER TABLE statement.
ALTER TABLE users ADD COLUMN instructor_id INT UNSIGNED NULL AFTER department;
ALTER TABLE users ADD INDEX idx_users_instructor_id (instructor_id);
ALTER TABLE users MODIFY role ENUM('admin','student','department_head','peer','self','academic_admin','committee','college_admin','dean','academic_affairs','archive','concerned_office') NOT NULL DEFAULT 'student';
UPDATE users SET role='academic_admin' WHERE role IN ('committee','college_admin','dean','academic_affairs','archive','concerned_office');
-- After the migration, use Actors & accounts to link each self-evaluator to their instructor profile.
