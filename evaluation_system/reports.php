<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['admin', 'department_head', 'academic_admin', 'self', 'committee', 'college_admin', 'dean', 'academic_affairs', 'archive', 'concerned_office']);
$u = current_user();
$role = (string)($u['role'] ?? '');

/*
 * Department Head feedback and instructor reply are stored separately from
 * evaluation submissions, so individual evaluator identities remain private.
 * Run MIGRATION_instructor_feedback.sql before using this page.
 */

// Instructor reply: instructors can reply only to feedback addressed to their own instructor profile.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_instructor_reply'])) {
  if ($role !== 'self') {
    http_response_code(403);
    exit('Forbidden');
  }
  verify_csrf();
  $feedbackId = (int)($_POST['feedback_id'] ?? 0);
  $reply = trim((string)($_POST['instructor_reply'] ?? ''));
  if ($reply === '') {
    flash('error', 'Please enter a reply before submitting.');
    redirect('reports.php');
  }
  $check = $pdo->prepare('SELECT f.id FROM instructor_feedback f JOIN users u ON u.instructor_id=f.instructor_id WHERE f.id=? AND u.id=? AND u.role=\'self\' LIMIT 1');
  $check->execute([$feedbackId, (int)$u['id']]);
  if (!$check->fetchColumn()) {
    http_response_code(403);
    exit('Forbidden: this feedback is not assigned to your instructor profile.');
  }
  $save = $pdo->prepare('UPDATE instructor_feedback SET instructor_reply=?, replied_at=NOW(), updated_at=NOW() WHERE id=?');
  $save->execute([$reply, $feedbackId]);
  flash('success', 'Your reply has been sent to the Department Head.');
  redirect('reports.php');
}

// Department Head feedback: comment is attached to an instructor's consolidated result, not to an individual evaluator.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_head_feedback'])) {
  if (!in_array($role, ['admin', 'department_head', 'academic_admin'], true)) {
    http_response_code(403);
    exit('Forbidden');
  }
  verify_csrf();
  $instructorId = (int)($_POST['instructor_id'] ?? 0);
  $academicYear = trim((string)($_POST['academic_year'] ?? ''));
  $comment = trim((string)($_POST['head_comment'] ?? ''));
  if ($instructorId < 1 || $comment === '') {
    flash('error', 'Choose an instructor and enter a comment.');
    redirect('reports.php');
  }
  $exists = $pdo->prepare('SELECT id FROM instructors WHERE id=? LIMIT 1');
  $exists->execute([$instructorId]);
  if (!$exists->fetchColumn()) {
    flash('error', 'Instructor not found.');
    redirect('reports.php');
  }
  $find = $pdo->prepare('SELECT id FROM instructor_feedback WHERE instructor_id=? AND academic_year=? LIMIT 1');
  $find->execute([$instructorId, $academicYear]);
  $feedbackId = $find->fetchColumn();
  if ($feedbackId) {
    $save = $pdo->prepare('UPDATE instructor_feedback SET head_user_id=?, head_comment=?, updated_at=NOW() WHERE id=?');
    $save->execute([(int)$u['id'], $comment, (int)$feedbackId]);
  } else {
    $save = $pdo->prepare('INSERT INTO instructor_feedback (instructor_id, academic_year, head_user_id, head_comment, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())');
    $save->execute([$instructorId, $academicYear, (int)$u['id'], $comment]);
    $feedbackId = (int)$pdo->lastInsertId();
  }
  // Notify the self-evaluation account linked to this instructor, if one exists.
  $recipient = $pdo->prepare("SELECT id FROM users WHERE role='self' AND instructor_id=? AND is_active=1 LIMIT 1");
  $recipient->execute([$instructorId]);
  $recipientId = $recipient->fetchColumn();
  if ($recipientId) {
    try {
      $instructorName = $pdo->prepare('SELECT full_name FROM instructors WHERE id=?');
      $instructorName->execute([$instructorId]);
      $name = (string)$instructorName->fetchColumn();
      $msg = $pdo->prepare('INSERT INTO messages(sender_user_id,recipient_user_id,instructor_id,subject,body,message_type,created_at) VALUES(?,?,?,?,?,?,NOW())');
      $msg->execute([(int)$u['id'], (int)$recipientId, $instructorId, 'Department Head feedback received', 'A Department Head has added or updated feedback on your consolidated evaluation result for ' . ($academicYear !== '' ? $academicYear : 'the selected evaluation period') . '. Please open Reports to read the comment and reply.', 'Instructor Feedback']);
    } catch (Throwable $ex) {
      // Feedback remains saved even if this installation has a different messages schema.
      error_log('ASES feedback notification could not be created: ' . $ex->getMessage());
    }
  }
  flash('success', 'Department Head comment saved for the selected instructor.');
  redirect('reports.php');
}

// The instructor view is deliberately isolated from institutional reports and evaluator-level data.
if ($role === 'self') {
  $instructorId = (int)($u['instructor_id'] ?? 0);
  if ($instructorId < 1) {
    page_header('My feedback');
    show_flash();
    echo '<section class="panel"><p>Your account is not linked to an instructor profile. Please contact the administrator.</p></section>';
    page_footer();
    exit;
  }
  $stmt = $pdo->prepare('SELECT f.*, i.full_name AS instructor_name FROM instructor_feedback f JOIN instructors i ON i.id=f.instructor_id WHERE f.instructor_id=? ORDER BY f.updated_at DESC, f.created_at DESC');
  $stmt->execute([$instructorId]);
  $feedbackRows = $stmt->fetchAll();
  page_header('My feedback');
  show_flash();
?>
  <div class="page-intro">
    <div>
      <div class="eyebrow">INSTRUCTOR FEEDBACK</div>
      <h1>My feedback</h1>
      <p>Read comments about your consolidated evaluation result and reply to the Department Head. Individual evaluator identities and submissions are not shown here.</p>
    </div>
  </div>
  <?php if (!$feedbackRows): ?><section class="panel">
      <p class="muted">No Department Head comments are available yet.</p>
    </section><?php else: foreach ($feedbackRows as $f): ?>
      <section class="panel" style="margin-bottom:16px">
        <div class="section-head">
          <div>
            <div class="eyebrow">EVALUATION PERIOD</div>
            <h2><?= e($f['academic_year'] ?: 'Not specified') ?></h2>
          </div><span class="status">Department Head feedback</span>
        </div>
        <h3>Comment on your result</h3>
        <p><?= nl2br(e($f['head_comment'])) ?></p>
        <p class="muted tiny">Updated: <?= e($f['updated_at'] ?? $f['created_at']) ?></p>
        <div class="divider"></div>
        <h3>Your reply</h3>
        <?php if (!empty($f['instructor_reply'])): ?><p><?= nl2br(e($f['instructor_reply'])) ?></p>
          <p class="muted tiny">Replied: <?= e($f['replied_at'] ?? '') ?></p><?php endif; ?>
        <form method="post" class="stack-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="feedback_id" value="<?= (int)$f['id'] ?>">
          <label for="reply-<?= (int)$f['id'] ?>">Reply to Department Head</label>
          <textarea id="reply-<?= (int)$f['id'] ?>" name="instructor_reply" rows="4" required maxlength="5000" placeholder="Write your response here..."><?= e($f['instructor_reply'] ?? '') ?></textarea>
          <button class="btn small primary" type="submit" name="submit_instructor_reply" value="1">Send reply</button>
        </form>
      </section>
  <?php endforeach;
            endif; ?>
<?php page_footer();
  exit;
}

$canFlow = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_workflow_events'])) {
  if ($role !== 'admin') {
    http_response_code(403);
    exit('Forbidden: administrator access is required.');
  }
  verify_csrf();
  if (($_POST['confirm_clear'] ?? '') !== 'CLEAR') {
    flash('error', 'Clear action cancelled: type CLEAR exactly to confirm.');
    redirect('reports.php');
  }
  try {
    $pdo->beginTransaction();
    $count = (int)$pdo->query('SELECT COUNT(*) FROM workflow_log')->fetchColumn();
    $pdo->exec('DELETE FROM workflow_log');
    $audit = $pdo->prepare('INSERT INTO admin_audit_log (actor_user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
    $audit->execute([(int)$u['id'], 'clear_workflow_events', 'Deleted ' . $count . ' workflow_log row(s).']);
    $pdo->commit();
    flash('success', 'Workflow event history cleared. ' . $count . ' event(s) removed. Evaluation records and results were not deleted.');
  } catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Clear workflow events failed: ' . $ex->getMessage());
    flash('error', 'Could not clear workflow events. Check the database migration and server error log.');
  }
  redirect('reports.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['workflow_action'])) {
  verify_csrf();
  $id = (int)($_POST['evaluation_id'] ?? 0);
  $action = $_POST['workflow_action'];
  $note = trim($_POST['workflow_note'] ?? '');
  $targets = [
    'committee_review' => ['admin', 'academic_admin', 'committee'],
    'college_review' => ['admin', 'academic_admin', 'committee', 'college_admin'],
    'academic_affairs' => ['admin', 'academic_admin', 'college_admin', 'dean'],
    'archived' => ['admin', 'academic_admin', 'academic_affairs', 'archive'],
    'returned' => ['admin', 'academic_admin', 'committee', 'college_admin', 'dean']
  ];
  if (!isset($targets[$action]) || !in_array($role, $targets[$action], true)) {
    flash('error', 'Your role cannot perform that workflow action.');
    redirect('reports.php');
  }
  $s = $pdo->prepare('SELECT id FROM evaluations WHERE id=?');
  $s->execute([$id]);
  if (!$s->fetch()) {
    flash('error', 'Evaluation record not found.');
    redirect('reports.php');
  }
  $s = $pdo->prepare('UPDATE evaluations SET status=?,workflow_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?');
  $s->execute([$action, $note, $u['id'], $id]);
  $s = $pdo->prepare('INSERT INTO workflow_log(evaluation_id,actor_user_id,action,note) VALUES(?,?,?,?)');
  $s->execute([$id, $u['id'], $action, $note]);
  $ctx = $pdo->prepare('SELECT e.instructor_id,i.full_name FROM evaluations e JOIN instructors i ON i.id=e.instructor_id WHERE e.id=?');
  $ctx->execute([$id]);
  $ctxRow = $ctx->fetch();
  if ($ctxRow && $action === 'committee_review') {
    $recipients = $pdo->query("SELECT id FROM users WHERE role IN ('academic_admin','committee') AND is_active=1")->fetchAll(PDO::FETCH_COLUMN);
    $notify = $pdo->prepare('INSERT INTO messages(sender_user_id,recipient_user_id,instructor_id,evaluation_id,subject,body,message_type,created_at) VALUES(?,?,?,?,?,?,?,NOW())');
    foreach ($recipients as $recipientId) {
      $notify->execute([(int)$u['id'], (int)$recipientId, (int)$ctxRow['instructor_id'], $id, 'Evaluation report forwarded for committee review', 'Evaluation #' . $id . ' for ' . $ctxRow['full_name'] . ' was forwarded by ' . $u['full_name'] . ' for Academic Administration / Evaluation Committee review.' . ($note ? ' Review note: ' . $note : ''), 'Workflow Notification']);
    }
  }
  if ($ctxRow && $action === 'returned') {
    $target = $pdo->prepare("SELECT id FROM users WHERE role='self' AND instructor_id=? AND is_active=1");
    $target->execute([(int)$ctxRow['instructor_id']]);
    $recipientId = $target->fetchColumn();
    if ($recipientId) {
      $notify = $pdo->prepare('INSERT INTO messages(sender_user_id,recipient_user_id,instructor_id,evaluation_id,subject,body,message_type,created_at) VALUES(?,?,?,?,?,?,?,NOW())');
      $notify->execute([(int)$u['id'], (int)$recipientId, (int)$ctxRow['instructor_id'], $id, 'Evaluation returned for revision', 'Your evaluation record for ' . $ctxRow['full_name'] . ' was returned for revision by ' . $u['full_name'] . '.' . ($note ? ' Comment: ' . $note : ''), 'Workflow Notification']);
    }
  }
  if ($action === 'archived') {
    $s = $pdo->prepare('INSERT INTO institutional_records(evaluation_id,archive_level,archive_reference,archived_by) VALUES(?,?,?,?)');
    $level = in_array($role, ['academic_admin','academic_affairs'], true) ? 'college' : (($role === 'archive') ? 'university' : 'department');
    $ref = trim($_POST['archive_reference'] ?? '');
    if ($ref === '') $ref = 'EVAL-' . $id;
    $s->execute([$id, $level, $ref, $u['id']]);
  }
  flash('success', 'Workflow status updated.');
  redirect('reports.php');
}

$sql = "SELECT e.*, i.full_name AS instructor, u.full_name AS evaluator, AVG(CASE WHEN r.is_na=0 THEN r.rating ELSE NULL END) AS avg_rating, COUNT(r.id) AS item_count FROM evaluations e JOIN instructors i ON i.id=e.instructor_id JOIN users u ON u.id=e.evaluator_user_id LEFT JOIN evaluation_responses r ON r.evaluation_id=e.id GROUP BY e.id ORDER BY e.created_at DESC";
$rows = $pdo->query($sql)->fetchAll();
$all = (int)$pdo->query('SELECT COUNT(*) FROM evaluations')->fetchColumn();
$arch = (int)$pdo->query("SELECT COUNT(*) FROM evaluations WHERE status='archived'")->fetchColumn();
$inReview = (int)$pdo->query("SELECT COUNT(*) FROM evaluations WHERE status IN('committee_review','college_review','academic_affairs')")->fetchColumn();
$weightMap = weights();
$availableActions = match ($role) {
  'admin', 'academic_admin' => ['committee_review' => 'Send to committee', 'college_review' => 'Forward to college administration', 'academic_affairs' => 'Forward to Academic Affairs', 'archived' => 'Archive record', 'returned' => 'Return for revision'],
  'committee' => ['college_review' => 'Forward summary to college administration', 'returned' => 'Return for revision'],
  'college_admin', 'dean' => ['academic_affairs' => 'Forward to Academic Affairs', 'returned' => 'Return for revision'],
  'academic_affairs', 'archive' => ['archived' => 'Archive record'],
  default => []
};
page_header('Reports & workflow');
show_flash();
?>
<div class="page-intro">
  <div>
    <div class="eyebrow">INSTITUTIONAL OVERSIGHT</div>
    <h1>Reports &amp; workflow</h1>
    <p>Review submissions, monitor weighted results, and move records through the approved institutional process.</p>
  </div>
</div>
<div class="metrics compact">
  <article class="metric">
    <div><small>Total submissions</small><strong><?= $all ?></strong></div>
  </article>
  <article class="metric">
    <div><small>In review</small><strong><?= $inReview ?></strong></div>
  </article>
  <article class="metric">
    <div><small>Archived</small><strong><?= $arch ?></strong></div>
  </article>
</div>
<section class="panel">
  <div class="section-head">
    <div>
      <div class="eyebrow">CONSOLIDATED EVALUATION RESULTS</div>
      <h2>Instructor evaluation results</h2>
    </div>
  </div>
  <p class="muted tiny">This table displays each registered instructor's weighted results. Individual evaluator names are not displayed.</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Instructor name</th>
          <th>Student / 40</th>
          <th>Head / 35</th>
          <th>Peer / 15</th>
          <th>Self / 10</th>
          <th>Available total / 100</th>
        </tr>
      </thead>
      <tbody><?php
        // Show actual instructor profiles only; exclude records the administrator
        // has identified as non-instructors from this results table.
        // This only hides them from this table; it does not delete database records.
        $sumSql = "SELECT i.id, i.full_name, e.evaluator_type,
                          AVG(CASE WHEN r.is_na=0 THEN r.rating ELSE NULL END) AS avg_rating
                   FROM instructors i
                   LEFT JOIN evaluations e
                     ON e.instructor_id=i.id
                    AND e.status IN ('submitted','committee_review','college_review','academic_affairs','archived')
                   LEFT JOIN evaluation_responses r ON r.evaluation_id=e.id
                   WHERE LOWER(TRIM(i.full_name)) NOT IN ('chaltu tola', 'dabala', 'dame', 'dame dereje')
                   GROUP BY i.id, i.full_name, e.evaluator_type
                   ORDER BY i.full_name ASC";
        $sumRows = $pdo->query($sumSql)->fetchAll();
        $byInstructor = [];
        foreach ($sumRows as $sr) {
          $iid = (int)$sr['id'];
          if (!isset($byInstructor[$iid])) {
            $byInstructor[$iid] = ['id' => $iid, 'name' => $sr['full_name'], 'parts' => []];
          }
          if ($sr['evaluator_type'] !== null && $sr['avg_rating'] !== null) {
            $wm = weights();
            $type = $sr['evaluator_type'];
            $byInstructor[$iid]['parts'][$type] = ((float)$sr['avg_rating'] / 5) * ($wm[$type] ?? 0);
          }
        }
        if (!$byInstructor): ?>
          <tr><td colspan="6">No instructors found.</td></tr>
        <?php else: foreach ($byInstructor as $bi):
          $total = 0;
          $has = false;
          foreach ($bi['parts'] as $v) {
            $total += $v;
            $has = true;
          } ?>
          <tr>
            <td><b><?= e($bi['name']) ?></b></td>
            <?php foreach (['student', 'department_head', 'peer', 'self'] as $cat): ?>
              <td><?= isset($bi['parts'][$cat]) ? number_format($bi['parts'][$cat], 2) . '%' : '—' ?></td>
            <?php endforeach; ?>
            <td><b><?= $has ? number_format($total, 2) . '%' : '—' ?></b></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel" id="head-feedback">
  <div class="section-head">
    <div>
      <div class="eyebrow">DEPARTMENT HEAD REVIEW</div>
      <h2>Comment on instructor result</h2>
    </div>
  </div>
  <p class="muted">Select the instructor and evaluation period, then write feedback based on the consolidated result. The instructor can read this comment and reply. Individual student, peer, and evaluator identities are not disclosed.</p>
  <form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="feedback-instructor">Instructor</label><select id="feedback-instructor" name="instructor_id" required>
      <option value="">Choose instructor</option><?php foreach ($byInstructor as $bi): ?><option value="<?= (int)$bi['id'] ?>"><?= e($bi['name']) ?></option><?php endforeach; ?>
    </select><label for="feedback-year">Evaluation period / academic year</label><input id="feedback-year" name="academic_year" required placeholder="e.g. 2025/2026"><label for="head-comment">Department Head comment</label><textarea id="head-comment" name="head_comment" rows="4" required maxlength="5000" placeholder="Comment on the instructor's consolidated result..."></textarea><button class="btn small primary" type="submit" name="save_head_feedback" value="1">Save comment and notify instructor</button></form>
</section>
<section class="panel">
  <div class="section-head">
    <div>
      <div class="eyebrow">FEEDBACK THREADS</div>
      <h2>Comments and instructor replies</h2>
    </div>
  </div>
  <?php $feedbackRows = $pdo->query('SELECT f.*,i.full_name AS instructor_name,h.full_name AS head_name FROM instructor_feedback f JOIN instructors i ON i.id=f.instructor_id LEFT JOIN users h ON h.id=f.head_user_id ORDER BY f.updated_at DESC, f.created_at DESC')->fetchAll();
  if (!$feedbackRows): ?><p class="muted">No instructor feedback has been recorded yet.</p><?php else: foreach ($feedbackRows as $f): ?><article class="activity"><span class="activity-icon">↔</span>
        <div><b><?= e($f['instructor_name']) ?> · <?= e($f['academic_year'] ?: 'Period not specified') ?></b><small>Head comment: <?= e($f['head_comment']) ?></small><?php if (!empty($f['instructor_reply'])): ?><small>Instructor reply: <?= e($f['instructor_reply']) ?></small><?php else: ?><small class="muted">Awaiting instructor reply</small><?php endif; ?></div>
      </article><?php endforeach;
                                                                                                                                                                                                                                                                                                                                                                        endif; ?>
</section>
<section class="panel">
  <div class="section-head">
    <div>
      <div class="eyebrow">AUDIT TRAIL</div>
      <h2>Recent workflow events</h2>
    </div>
  </div>
  <?php $logs = $pdo->query('SELECT w.*,u.full_name AS actor FROM workflow_log w JOIN users u ON u.id=w.actor_user_id ORDER BY w.created_at DESC LIMIT 12')->fetchAll();
  if (!$logs): ?><p class="muted">No workflow events recorded yet.</p><?php else: foreach ($logs as $l): ?><div class="activity"><span class="activity-icon">↗</span>
        <div><b>Evaluation #<?= (int)$l['evaluation_id'] ?> · <?= e(ucwords(str_replace('_', ' ', $l['action']))) ?></b><small><?= e($l['actor']) ?> · <?= e($l['created_at']) ?><?= $l['note'] ? ' · ' . e($l['note']) : '' ?></small></div>
      </div><?php endforeach;
                                                                                                                                                                                                                                        endif; ?>
</section>
<?php if ($role === 'admin'): ?>
  <section class="panel" style="margin-top:20px;border:1px solid #e3b3b3">
    <div class="section-head">
      <div>
        <div class="eyebrow">ADMINISTRATOR ONLY</div>
        <h2>Clear workflow event history</h2>
      </div>
    </div>
    <p class="muted">This permanently removes entries from the Recent workflow events list. It does not delete evaluations, evaluation responses, instructor records, or results. A separate audit record will be kept.</p>
    <form method="post" onsubmit="return confirm('Clear ALL workflow event history? This cannot be undone. Evaluation records and results will remain.');" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="clear_workflow_events" value="1"><label style="display:block">Type <b>CLEAR</b> to confirm<br><input type="text" name="confirm_clear" required pattern="CLEAR" autocomplete="off" placeholder="Type CLEAR" style="margin-top:6px"></label><button type="submit" class="btn small primary">Clear all workflow events</button></form>
  </section>
<?php endif; ?>
<?php page_footer(); ?>