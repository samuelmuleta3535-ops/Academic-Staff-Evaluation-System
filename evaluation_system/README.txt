ACADEMIC STAFF / INSTRUCTOR EVALUATION SYSTEM (ASES)

INSTALL / UPDATE
1. Back up C:\xampp\htdocs\evaluation_system and export your acedamic_staff database before replacing files.
2. Keep config/database.php settings matching your local MySQL host, database, username, password, and port. This project is configured for 127.0.0.1:3307.
3. For an existing database, import MIGRATION_courses_departments.sql and any earlier migrations you have not yet run in phpMyAdmin after selecting acedamic_staff. Run each migration only once. Do not re-import database.sql over an existing database.
4. Open http://localhost/evaluation_system/ and sign in.

ASES features include role-based access, typed/searchable instructor names (must match a registered instructor), profile linking, evaluation forms, aggregate results, institutional workflow, private messages/notifications, and administrator-only workflow history clearing.

MESSAGES & NOTIFICATIONS
Department Heads can send advice to linked instructors and forward messages to Academic Administration / Evaluation Committee. Academic Administration / Committee can advise instructors and relevant Department Heads. Each account sees only messages addressed to that account; unread messages appear in the navigation badge.

SECURITY NOTES
- The message endpoint validates recipient roles on the server and uses CSRF tokens.
- Instructors see aggregate results only; evaluator identities and individual submissions are not shown in the self-result page.
- Back up the database before migrations or clearing workflow history.

The project files were checked with PHP syntax lint. Live database and browser testing must be completed in your own XAMPP environment.

LANGUAGE + DEADLINE UPDATE
1. Import MIGRATION_language_deadline.sql into the existing acedamic_staff database using phpMyAdmin.
2. Log in as System Administrator and open Deadline settings in the sidebar. Set a date/time or leave blank for no deadline.
3. Use the language selector in the top bar to choose English, Amharic, or Afaan Oromoo. The language choice is kept for the current session.

COURSES AND DEPARTMENTS
1. Sign in as System Administrator and open Course & department management.
2. Create each department, then create courses with a unique course code within that department.
3. Open Actors & accounts and assign each Student, Department Head, or Instructor / Self-Evaluator account to the matching department.
4. Register the corresponding user profile in User directory and link the Self-Evaluator account to that profile when needed.
5. The evaluation form shows active courses for the logged-in user's department. Peer evaluators can choose from all active courses; target profile and course department must match.
6. Existing evaluation history is preserved. Course titles and codes are copied into evaluation records when submitted.


LATEST UPDATE — INDIVIDUAL COURSE ASSIGNMENTS
1. Back up your acedamic_staff database and evaluation_system folder.
2. In phpMyAdmin select acedamic_staff.
3. Import MIGRATION_evaluation_assignments.sql exactly once. This creates evaluator-to-course assignments and adds the assignment reference to evaluations.
4. Sign in as Admin, open Course & department management, assign each Student / Peer / Department Head / Instructor account a course, start date/time, and deadline.
5. Evaluators can submit once for each assigned course/assignment while the time window is open. A different assigned course may still be evaluated.
6. The User Directory is inside Actors & Accounts. Permanently deleting a profile also deletes its associated evaluation history; confirm carefully before deleting.


UPDATE NOTES (User Accounts, profile photos, and instructor-targeted assignments)
1. Back up the acedamic_staff database in phpMyAdmin.
2. Select the acedamic_staff database and import MIGRATION_user_accounts_profile_assignment.sql once.
3. Copy the updated evaluation_system files over C:\xampp\htdocs\evaluation_system. Do not overwrite config/database.php if your local database credentials differ.
4. Make sure Apache can write to evaluation_system/uploads/profiles; the application creates this folder on first upload.
5. Open User Accounts. Create login accounts and add instructor profiles in the Instructor profiles section on the same page.
6. In Course & department management, assign the evaluator, course, instructor being evaluated, start time, and deadline. The instructor and course must be in the same department.
7. Profile photos can be uploaded from My profile. JPG, PNG, and WebP are accepted, maximum 2 MB.
8. Existing assignment rows that could not be matched to an instructor in the same department must be reassigned with a valid instructor.

UI UPDATE (October 2026)
- The top-right profile photo/name opens My profile; a separate exit-arrow icon logs out.
- Use the moon/sun button in the top bar to switch between light and dark themes. The choice is remembered on the same browser/device.
- The “Secure local workspace” status text has been removed, including its space.
- User Accounts is the single admin workspace. Instructor registration and activation are now inside an expandable section on that page; the old Instructor Profiles route redirects to User Accounts.
- My profile supports JPG, PNG, and WebP uploads up to 2 MB and previews the chosen photo before saving.
- The English, Afaan Oromoo, and Amharic selector translates common navigation, form, and workflow labels. Proper names, user-entered content, and some page-specific explanatory text remain in their authored language and may need additional dictionary entries for full page-by-page localization.
- No new database migration is required for these interface changes. Profile-image storage uses the existing profile_image column and uploads/profiles directory from the earlier update.


INSTITUTIONAL ACTOR ROLES UPDATE
For an existing database, import MIGRATION_institutional_actor_roles.sql in phpMyAdmin before assigning new roles. New roles: committee, college_admin, dean, academic_affairs, archive, concerned_office. Existing evaluator roles and weights are preserved.

EXISTING DATABASE: import MIGRATION_institutional_actor_roles.sql to enable new institutional roles. If the instructor feedback/reply feature is not already installed, also import MIGRATION_instructor_feedback.sql. Back up the database first; do not re-import database.sql over a live database.
