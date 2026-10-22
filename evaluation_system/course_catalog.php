<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$u = current_user();
$isAdmin = (($u['role'] ?? '') === 'admin');
$restrictedRole = in_array(($u['role'] ?? ''), ['student','peer','department_head','self'], true);
$userDepartment = trim((string)($u['department'] ?? ''));
$selectedDepartment = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: 0;
$search = trim((string)($_GET['q'] ?? ''));

// Department selector includes active departments for everyone; administrators can also inspect inactive departments.
$departmentSql = $isAdmin
    ? 'SELECT id, name, is_active FROM departments ORDER BY name'
    : ($restrictedRole ? 'SELECT id, name, is_active FROM departments WHERE is_active = 1 AND name = ' . $pdo->quote($userDepartment) . ' ORDER BY name' : 'SELECT id, name, is_active FROM departments WHERE is_active = 1 ORDER BY name');
$departments = $pdo->query($departmentSql)->fetchAll(PDO::FETCH_ASSOC);

$sql = 'SELECT c.id, c.course_code, c.course_title, c.description, c.is_active, d.id AS department_id, d.name AS department_name
        FROM courses c INNER JOIN departments d ON d.id = c.department_id WHERE 1=1';
$params = [];
if (!$isAdmin) {
    $sql .= ' AND c.is_active = 1 AND d.is_active = 1';
}
if ($restrictedRole) {
    $sql .= ' AND d.name = ?';
    $params[] = $userDepartment;
}
if ($selectedDepartment > 0) {
    $sql .= ' AND d.id = ?';
    $params[] = $selectedDepartment;
}
if ($search !== '') {
    $sql .= ' AND (c.course_code LIKE ? OR c.course_title LIKE ? OR c.description LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
$sql .= ' ORDER BY d.name, c.course_code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($courses as $course) {
    $grouped[$course['department_name']][] = $course;
}

page_header($restrictedRole ? 'Courses' : 'Courses by department');
show_flash();
?>
<div class="page-intro">
  <div>
    <div class="eyebrow">ACADEMIC COURSE DIRECTORY</div>
    <h1><?= $restrictedRole ? 'Courses assigned to your department' : 'Courses by department' ?></h1>
    <p><?= $restrictedRole ? 'Browse active courses in your assigned department. Only these courses are available for your evaluation role.' : 'Choose a department to see every course registered under it.' ?></p>
  </div>
</div>
<section class="panel">
  <form method="get" class="form-grid">
    <label>Department
      <select name="department_id">
        <?php if (!$restrictedRole): ?><option value="0">All departments</option><?php endif; ?>
        <?php foreach ($departments as $department): ?>
          <option value="<?= (int)$department['id'] ?>" <?= $selectedDepartment === (int)$department['id'] ? 'selected' : '' ?>>
            <?= e($department['name']) ?><?= $isAdmin && !(int)$department['is_active'] ? ' (Inactive)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Search course
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Course code or title">
    </label>
    <div class="actions">
      <button class="btn primary" type="submit">Show courses</button>
      <a class="btn secondary" href="<?= e(BASE_URL) ?>/course_catalog.php">Clear filters</a>
    </div>
  </form>
</section>
<div class="metrics">
  <article class="metric"><span class="metric-icon blue">▦</span><div><small>Courses shown</small><strong><?= count($courses) ?></strong><em>Based on your filters</em></div></article>
  <article class="metric"><span class="metric-icon purple">▤</span><div><small>Departments shown</small><strong><?= count($grouped) ?></strong><em>With matching courses</em></div></article>
</div>
<?php if (!$grouped): ?>
  <section class="panel empty-state"><b>No courses found</b><p>There are no courses matching this department or search. Ask the administrator to create a course or check that it is active.</p></section>
<?php else: ?>
  <?php foreach ($grouped as $departmentName => $departmentCourses): ?>
    <section class="panel table-wrap">
      <div class="section-head">
        <div><div class="eyebrow">DEPARTMENT</div><h2><?= e($departmentName) ?></h2><p class="muted">All registered courses under this department.</p></div>
        <span class="count-pill"><?= count($departmentCourses) ?> course<?= count($departmentCourses) === 1 ? '' : 's' ?></span>
      </div>
      <table>
        <thead><tr><th>Course code</th><th>Course title</th><th>Description</th><?php if ($isAdmin): ?><th>Status</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($departmentCourses as $course): ?>
          <tr>
            <td><b><?= e($course['course_code']) ?></b></td>
            <td><?= e($course['course_title']) ?></td>
            <td><?= e($course['description'] ?: '—') ?></td>
            <?php if ($isAdmin): ?><td><?= (int)$course['is_active'] ? 'Active' : 'Inactive' ?></td><?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php page_footer(); ?>
