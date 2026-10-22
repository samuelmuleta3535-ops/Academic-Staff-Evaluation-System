-- ASES institutional actor roles migration
-- Back up the database before running. Existing evaluator roles and records are preserved.
ALTER TABLE users MODIFY COLUMN role ENUM(
  'admin','student','department_head','peer','self','academic_admin',
  'committee','college_admin','dean','academic_affairs','archive','concerned_office'
) NOT NULL DEFAULT 'student';
