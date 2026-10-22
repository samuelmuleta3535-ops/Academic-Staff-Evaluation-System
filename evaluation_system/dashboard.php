<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$u = current_user();
$role = (string)($u['role'] ?? '');
$name = trim((string)($u['full_name'] ?? $u['username'] ?? 'User'));
if ($name === '') $name = 'User';
$isAdmin = $role === 'admin';
$isReviewer = in_array($role, ['admin', 'department_head', 'academic_admin'], true);
$canSeeMetrics = in_array($role, ['admin', 'academic_admin'], true);
$isEvaluator = in_array($role, ['student', 'department_head', 'peer', 'self'], true);

// Global operational metrics are only queried/displayed for administration roles.
$ins = $sub = $pending = $mine = 0;
$recent = [];
if ($canSeeMetrics) {
    $ins = (int)$pdo->query('SELECT COUNT(*) FROM instructors WHERE is_active=1')->fetchColumn();
    $sub = (int)$pdo->query("SELECT COUNT(*) FROM evaluations WHERE status='submitted'")->fetchColumn();
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM evaluations WHERE status IN('committee_review','college_review','academic_affairs')")->fetchColumn();
    $s = $pdo->prepare('SELECT COUNT(*) FROM evaluations WHERE evaluator_user_id=?');
    $s->execute([$u['id']]);
    $mine = (int)$s->fetchColumn();
}
if ($isEvaluator) {
    $s = $pdo->prepare('SELECT e.id,e.evaluator_type,e.status,e.created_at,i.full_name instructor FROM evaluations e JOIN instructors i ON i.id=e.instructor_id WHERE e.evaluator_user_id=? ORDER BY e.created_at DESC LIMIT 5');
    $s->execute([$u['id']]);
    $recent = $s->fetchAll();
}
page_header('Dashboard');
show_flash();
?>
<section class="welcome">
  <div>
    <div class="eyebrow">ACADEMIC PERFORMANCE WORKSPACE</div>
    <h1>Welcome <?= e($name) ?> to ASES</h1>
    <p>Keep evaluations organized, fair, and focused on professional growth.</p>
    <div class="actions">
      <?php if ($isEvaluator): ?><a class="btn white" href="<?= BASE_URL ?>/evaluate.php">＋ Start evaluation</a><?php endif; ?>
      <a class="btn ghost-white" href="<?= BASE_URL ?>/course_catalog.php">▦ Browse courses</a>
      <?php if ($isReviewer): ?><a class="btn ghost-white" href="<?= BASE_URL ?>/reports.php">View results ↗</a><?php endif; ?>
    </div>
  </div>
  <div class="welcome-art"><div class="orbit o1"></div><div class="orbit o2"></div><div class="art-card"><small>Your workspace</small><strong><?= e(roles()[$role] ?? $role) ?></strong><span>Role-based access · Academic courses</span></div></div>
</section>

<?php if ($canSeeMetrics): ?>
<div class="metrics">
  <article class="metric"><span class="metric-icon blue">♙</span><div><small>Active instructors</small><strong><?= $ins ?></strong><em>Staff directory</em></div></article>
  <article class="metric"><span class="metric-icon purple">▤</span><div><small>My submissions</small><strong><?= $mine ?></strong><em>Your account</em></div></article>
  <article class="metric"><span class="metric-icon green">✓</span><div><small>Submitted records</small><strong><?= $sub ?></strong><em>All evaluators</em></div></article>
  <article class="metric"><span class="metric-icon amber">◷</span><div><small>In review workflow</small><strong><?= $pending ?></strong><em>Awaiting review</em></div></article>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<section class="panel admin-shortcuts">
  <div class="section-head"><div><div class="eyebrow">ADMINISTRATION</div><h2>Manage the evaluation system</h2></div></div>
  <div class="admin-shortcut-grid">
    <a class="admin-shortcut" href="<?= BASE_URL ?>/users.php"><span>♙</span><b>Actors &amp; Accounts</b><small>Create accounts, assign roles and departments, and manage access.</small></a>
    <a class="admin-shortcut" href="<?= BASE_URL ?>/course_management.php"><span>▦</span><b>Course &amp; Department Management</b><small>Create departments and courses and maintain their status.</small></a>
    <a class="admin-shortcut" href="<?= BASE_URL ?>/course_catalog.php"><span>☷</span><b>Courses by Department</b><small>Browse every course grouped under its department.</small></a>
    <a class="admin-shortcut" href="<?= BASE_URL ?>/reports.php"><span>▥</span><b>Results &amp; Workflow</b><small>Review consolidated results and institutional workflow.</small></a>
  </div>
</section>
<?php elseif ($isEvaluator): ?>
<div class="two-col">
  <section class="panel">
    <div class="section-head"><div><div class="eyebrow">YOUR AUTHORIZED WORKSPACE</div><h2><?= $role === 'student' || $role === 'peer' ? 'Evaluate an assigned course' : 'Evaluation workspace' ?></h2></div></div>
    <p class="muted">Use courses available to your assigned department. Only submit evaluations that match your account role.</p>
    <div class="actions"><a class="btn primary" href="<?= BASE_URL ?>/evaluate.php">＋ Start evaluation</a><a class="btn secondary" href="<?= BASE_URL ?>/course_catalog.php">View courses</a></div>
    <?php if ($role === 'self'): ?><p class="tiny muted" style="margin-top:16px">Your personal results show consolidated scores only, not individual evaluator submissions or identities.</p><a class="text-link" href="<?= BASE_URL ?>/my_result.php">View my consolidated result →</a><?php endif; ?>
  </section>
  <section class="panel">
    <div class="section-head"><div><div class="eyebrow">RECENT ACTIVITY</div><h2>Recent evaluations you submitted</h2></div><a class="text-link" href="<?= BASE_URL ?>/my_evaluations.php">View all →</a></div>
    <?php if (!$recent): ?><div class="empty-state"><span>▤</span><b>No submissions yet</b><p>Your own submitted evaluations will appear here.</p></div>
    <?php else: foreach ($recent as $r): ?><div class="activity"><span class="activity-icon">▤</span><div><b><?= e($r['instructor']) ?></b><small><?= e(ucwords(str_replace('_',' ',$r['evaluator_type']))) ?> · <?= e(date('M j, Y', strtotime($r['created_at']))) ?></small></div><span class="status"><?= e(ucwords(str_replace('_',' ',$r['status']))) ?></span></div><?php endforeach; endif; ?>
  </section>
</div>
<?php endif; ?>

<?php if (in_array($role, ['department_head', 'academic_admin'], true)): ?>
<section class="workflow-banner"><div><div class="eyebrow">INSTITUTIONAL WORKFLOW</div><h2>From evaluation to improvement</h2><p>Committee review → college administration → Academic Affairs → records archive.</p></div><a class="btn secondary" href="<?= BASE_URL ?>/reports.php">Open workflow board →</a></section>
<?php endif; ?>
<?php page_footer(); ?>
