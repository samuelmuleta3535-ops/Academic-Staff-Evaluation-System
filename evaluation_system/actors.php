<?php
require_once __DIR__.'/includes/bootstrap.php';
require_role(['admin','department_head','academic_admin','committee','college_admin','dean','academic_affairs','archive','concerned_office']);
$actors = [
 ['01','Students','student','Evaluate instructional skills, clarity of course delivery, classroom management, professional ethics, assessment methods, feedback, and responsiveness to student needs.','40% · Evaluator'],
 ['02','Department Head','department_head','Evaluate the instructor’s core and professional competencies, research participation, time management, ethical conduct, and departmental contributions. Coordinate evaluation activities and submit results through the prescribed channels.','35% · Evaluator'],
 ['03','Peer Instructors','peer','Evaluate colleagues’ subject-matter competency, teaching methods, research and community service, professional conduct, collaboration, and time management.','15% · Evaluator'],
 ['04','Instructor (Self-Evaluator)','self','Conduct a self-assessment of teaching practices, professional development, personal behavior, assessment methods, interpersonal relationships, and institutional responsibilities. Instructors can view consolidated results, not individual confidential submissions.','10% · Evaluator'],
 ['05','Ad-hoc Evaluation Committee / Officially Delegated Body','committee','Assist the department head in administering evaluations; select students and staff for evaluation; explain the process; coordinate fair and transparent participation; and contribute to the final evaluation summary.','Committee coordination'],
 ['06','College / School / Institute Administration','college_admin','Receive summarized evaluation results from departments, review and process reports, and forward them to the Academic Affairs Vice President’s Office.','Institutional review'],
 ['07','College / School Dean / Vice Dean','dean','Evaluate the department head and review departmental performance in accordance with the institution’s approved evaluation guideline.','Leadership evaluation'],
 ['08','Academic Affairs Vice President / Academic Affairs Vice Director','academic_affairs','Evaluate the dean and oversee the academic evaluation process at the appropriate institutional level.','Academic oversight'],
];
$oversight = [
 ['Academic Affairs Vice President’s Office / Campus Academic Vice Director','Receives evaluation summaries from colleges, schools, and institutes and officially forwards them for archiving, copying the instructor and department.','academic_affairs'],
 ['University / Department Archives','Preserves evaluation records at department, college, school, and university levels, following institutional records-retention and confidentiality rules.','archive'],
 ['Concerned Offices and Directorates','Consult with academic units to address performance gaps and support capacity-building activities.','concerned_office'],
 ['System Administrator','Creates and manages accounts, assigns roles, maintains instructor profiles, manages system settings, and supports system operation.','admin'],
];
page_header('Actors & responsibilities'); show_flash();
?>
<div class="page-intro"><div><div class="eyebrow">SYSTEM GOVERNANCE</div><h1>Actors &amp; responsibilities</h1><p>Institutional actors, account roles, responsibilities, and the evaluation workflow supported by ASES.</p></div></div>
<div class="actor-grid">
<?php foreach($actors as $a): ?><article class="actor-card"><div class="actor-top"><span class="actor-num"><?=e($a[0])?></span><span class="actor-weight"><?=e($a[4])?></span></div><h2><?=e($a[1])?></h2><p><?=e($a[3])?></p><div class="muted tiny">Admin account role: <code><?=e($a[2])?></code></div></article><?php endforeach; ?>
</div>
<section class="panel"><div class="section-head"><div><div class="eyebrow">INSTITUTIONAL OVERSIGHT & RECORDS</div><h2>Oversight, communication, and records</h2></div></div><div class="oversight-list">
<?php foreach($oversight as $o): ?><article><span class="oversight-icon">✓</span><div><h3><?=e($o[0])?></h3><p><?=e($o[1])?></p><p class="muted tiny">Admin account role: <code><?=e($o[2])?></code></p></div></article><?php endforeach; ?>
</div></section>
<section class="panel"><h2>Confidentiality and role boundaries</h2><ul><li>Students, department heads, peer instructors, and self-evaluators keep their existing evaluator roles and evaluation weights.</li><li>Instructor accounts must not see individual student, peer, or department-head submissions or evaluator identities; they may see only their consolidated results.</li><li>Institutional oversight roles are separate from the four weighted evaluator categories. The new roles do not change the existing 40/35/15/10 weighting.</li><li>Admin creates each account from <b>User Accounts</b> and assigns the role that matches the person’s official responsibility.</li></ul><p class="muted tiny">Important: the current weighted evaluation form remains limited to student, department-head, peer, and self-evaluation. Separate criteria/forms for dean-to-department-head and Academic Affairs-to-dean evaluation should be configured from the institution’s approved criteria before those evaluations are submitted in ASES.</p></section>
<?php page_footer(); ?>
