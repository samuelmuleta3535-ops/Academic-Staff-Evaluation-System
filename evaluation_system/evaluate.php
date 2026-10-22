<?php
require_once __DIR__.'/includes/bootstrap.php';
require_login();
$types=['student'=>'Student Evaluation','department_head'=>'Department Head Evaluation','peer'=>'Peer Instructor Evaluation','self'=>'Self-Evaluation'];
$user=current_user();$role=$user['role'];
$allowed=match($role){'student'=>['student'],'department_head'=>['department_head'],'peer'=>['peer'],'self'=>['self'],default=>[]};
if(!$allowed){http_response_code(403);page_header('Evaluation unavailable');echo '<section class="panel"><h1>No evaluation form assigned</h1><p>System administrators and academic administration do not submit evaluator forms. Use your authorized management functions.</p><a class="btn primary" href="'.BASE_URL.'/dashboard.php">Return to dashboard</a></section>';page_footer();exit;}
$type=$allowed[0];
$qs=$pdo->prepare('SELECT * FROM evaluation_questions WHERE evaluator_type=? AND is_active=1 ORDER BY sort_order,id');$qs->execute([$type]);$questions=$qs->fetchAll();
// Only courses explicitly assigned to this evaluator, and only while the assignment window is open.
$courseStmt=$pdo->prepare("SELECT a.id AS assignment_id,a.starts_at,a.deadline_at,a.instructor_id,i.full_name AS target_instructor_name,c.id,c.course_code,c.course_title,d.name AS department_name FROM evaluation_assignments a JOIN courses c ON c.id=a.course_id JOIN departments d ON d.id=c.department_id JOIN instructors i ON i.id=a.instructor_id WHERE a.evaluator_user_id=? AND a.evaluator_type=? AND a.is_active=1 AND c.is_active=1 AND d.is_active=1 AND i.is_active=1 AND NOW() BETWEEN a.starts_at AND a.deadline_at AND NOT EXISTS (SELECT 1 FROM evaluations e WHERE e.assignment_id=a.id) AND NOT EXISTS (SELECT 1 FROM evaluations e2 WHERE e2.evaluator_user_id=a.evaluator_user_id AND e2.evaluator_type=a.evaluator_type AND e2.course_id=a.course_id AND e2.created_at BETWEEN a.starts_at AND a.deadline_at) ORDER BY a.deadline_at,c.course_code");
$courseStmt->execute([(int)$user['id'],$type]);$courses=$courseStmt->fetchAll();
$deadline=$courses ? $courses[0]['deadline_at'] : null;$closed=!$courses;
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['submit_eval'])){
 verify_csrf();
 $assignmentId=(int)($_POST['assignment_id']??0);$course=null;$assignment=null;
 if(!$err){$s=$pdo->prepare("SELECT a.id AS assignment_id,a.course_id,a.instructor_id,a.starts_at,a.deadline_at,c.course_code,c.course_title,d.name AS department_name,i.full_name AS target_instructor_name FROM evaluation_assignments a JOIN courses c ON c.id=a.course_id JOIN departments d ON d.id=c.department_id JOIN instructors i ON i.id=a.instructor_id WHERE a.id=? AND a.evaluator_user_id=? AND a.evaluator_type=? AND a.is_active=1 AND c.is_active=1 AND d.is_active=1 AND i.is_active=1 AND NOW() BETWEEN a.starts_at AND a.deadline_at");$s->execute([$assignmentId,(int)$user['id'],$type]);$assignment=$s->fetch();if(!$assignment)$err='This course is not assigned to your account, or its evaluation period has not started / has ended.';else{$course=['id'=>(int)$assignment['course_id'],'course_code'=>$assignment['course_code'],'course_title'=>$assignment['course_title'],'department_name'=>$assignment['department_name']];$courseId=(int)$assignment['course_id'];$iid=(int)$assignment['instructor_id'];if($type==='self'&&!empty($user['instructor_id'])&&$iid!==(int)$user['instructor_id'])$err='This self-evaluation assignment does not match your linked instructor profile.';$duplicate=$pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE evaluator_user_id=? AND evaluator_type=? AND course_id=? AND created_at BETWEEN ? AND ?");$duplicate->execute([(int)$user['id'],$type,(int)$course['id'],$assignment['starts_at'],$assignment['deadline_at']]);if((int)$duplicate->fetchColumn()>0)$err='You have already submitted an evaluation for this course during this evaluation period. You may evaluate another assigned course.';}}
 // The instructor being evaluated is fixed by the administrator's assignment; evaluators cannot change it.
 if(!$err){
   $s=$pdo->prepare('SELECT id FROM instructors WHERE id=? AND is_active=1');
   $s->execute([$iid]);
   if(!$s->fetch()){$err='Select a valid registered user.';}
   elseif(!$questions){$err='No criteria are configured for this form.';}
   else{
     try{
       $pdo->beginTransaction();
       $again=$pdo->prepare("SELECT COUNT(*) FROM evaluations WHERE assignment_id=? OR (evaluator_user_id=? AND evaluator_type=? AND course_id=? AND created_at BETWEEN ? AND ?)");
       $again->execute([$assignmentId,(int)$user['id'],$type,$courseId,$assignment['starts_at'],$assignment['deadline_at']]);
       if((int)$again->fetchColumn()>0) throw new RuntimeException('You have already submitted this course evaluation for the current assignment window.');
       $sql="INSERT INTO evaluations(instructor_id,course_id,assignment_id,evaluator_user_id,evaluator_type,academic_year,semester,course_title,course_code,batch_no,strengths,improvements,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?, 'submitted')";
       $s=$pdo->prepare($sql);
       $s->execute([
         $iid, $courseId, $assignmentId, $user['id'], $type,
         trim($_POST['academic_year'] ?? ''),
         trim($_POST['semester'] ?? ''),
         $course['course_title'], $course['course_code'],
         trim($_POST['batch_no'] ?? 'I'),
         trim($_POST['strengths'] ?? ''),
         trim($_POST['improvements'] ?? '')
       ]);
       $eid=(int)$pdo->lastInsertId();
       $r=$pdo->prepare('INSERT INTO evaluation_responses(evaluation_id,question_id,rating,is_na) VALUES(?,?,?,?)');
       foreach($questions as $q){
         $v=(string)($_POST['rating'][$q['id']] ?? '');
         if(!in_array($v,['5','4','3','2','1','0','NA'],true)) throw new RuntimeException('Rate every criterion, using NA if not applicable.');
         $r->execute([$eid,$q['id'],$v==='NA' ? null : (int)$v,$v==='NA' ? 1 : 0]);
       }
       $log=$pdo->prepare('INSERT INTO workflow_log(evaluation_id,actor_user_id,action,note) VALUES(?,?,?,?)');
       $log->execute([$eid,$user['id'],'submitted','Evaluation submitted for course '.$course['course_code']]);
       $pdo->commit(); flash('success','Evaluation submitted successfully.'); redirect('my_evaluations.php');
     } catch(Throwable $ex) {
       if($pdo->inTransaction()) $pdo->rollBack();
       $err=$ex instanceof RuntimeException ? $ex->getMessage() : 'Unable to save evaluation. Check required fields and database setup.';
     }
   }
 }
}
page_header('New evaluation');show_flash();?><div class="page-intro"><div><div class="eyebrow">OFFICIAL EVALUATION SCALE</div><h1><?=e($types[$type])?></h1><p>Choose a course created by the administrator and evaluate only within your authorized role.</p><?php if($deadline):?><p class="deadline-note"><strong><?=e(t('Evaluation deadline'))?>:</strong> <?=e(date('M j, Y g:i A',strtotime($deadline)))?> (earliest assigned deadline)</p><?php else:?><p class="deadline-note">Your assigned course windows determine when you can submit.</p><?php endif;?></div><span class="weight-pill"><?=weights()[$type]?>% contribution</span></div><?php if($closed):?><div class="alert error"><?=e(t('Evaluation deadline has passed. Submissions are closed.'))?></div><?php elseif($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<?php if(!$courses):?><section class="panel"><h2>No course available</h2><p>You currently have no assigned course with an open evaluation period. Ask the administrator to assign the correct course and set its start time and deadline.</p></section><?php elseif(!$closed):?><form method="post" class="panel form-panel"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-grid"><label class="wide">Assigned course / department<select name="assignment_id" required><option value="">Choose an assigned course</option><?php foreach($courses as $c):?><option value="<?=$c['assignment_id']?>" <?=((string)($_POST['assignment_id']??'')===(string)$c['assignment_id'])?'selected':''?>><?=e($c['course_code'].' — '.$c['course_title'].' ('.$c['department_name'].') · Instructor: '.$c['target_instructor_name'].' · deadline '.date('M j, Y g:i A',strtotime($c['deadline_at'])))?></option><?php endforeach;?></select></label><div class="wide notice">The instructor being evaluated is selected by the administrator and shown in each assigned course above. You cannot change the instructor here.</div>
<label>Academic year<input name="academic_year" placeholder="e.g. 2018 E.C." value="<?=e($_POST['academic_year']??'')?>" required></label><label>Semester<select name="semester"><option <?=($_POST['semester']??'I')==='I'?'selected':''?>>I</option><option <?=($_POST['semester']??'')==='II'?'selected':''?>>II</option></select></label><label>Batch<select name="batch_no"><?php foreach(['I','II','III','IV','V'] as $b):?><option <?=($_POST['batch_no']??'I')===$b?'selected':''?>><?=$b?></option><?php endforeach;?></select></label></div><div class="section-head"><div><h2>Evaluation criteria</h2><p class="muted">NA is excluded from the numeric average.</p></div><span class="count-pill"><?=count($questions)?> criteria</span></div><?php $section='';foreach($questions as $q):if($section!==$q['section']){$section=$q['section'];echo '<div class="criteria-section">'.e($section).'</div>';}?><div class="criterion"><span class="criterion-number"><?=e($q['sort_order'])?></span><span class="criterion-text"><?=e($q['question_text'])?></span><div class="rating-options"><?php foreach(['5','4','3','2','1','0','NA'] as $v):?><label class="rating-choice"><input type="radio" name="rating[<?=$q['id']?>]" value="<?=$v?>" required <?=((string)($_POST['rating'][$q['id']]??'')===$v)?'checked':''?>><span><?=$v?></span></label><?php endforeach;?></div></div><?php endforeach;?><div class="form-grid"><label class="wide">User strengths<textarea name="strengths" rows="3"><?=e($_POST['strengths']??'')?></textarea></label><label class="wide">Suggested points for improvement<textarea name="improvements" rows="3"><?=e($_POST['improvements']??'')?></textarea></label></div><div class="form-actions"><span class="muted tiny">Only authorized users can submit evaluations. Individual responses remain confidential.</span><button class="btn primary" name="submit_eval" value="1" <?=!$questions?'disabled':''?>>Submit evaluation →</button></div></form><?php elseif($closed):?><section class="panel"><h2><?=e(t('Evaluation deadline'))?></h2><p><?=e(t('Evaluation deadline has passed. Submissions are closed.'))?></p></section><?php endif;?><?php page_footer();?>
