<?php
require_once __DIR__.'/includes/bootstrap.php';
require_login();
$u=current_user();$msg='';
$canSend=in_array($u['role'],['department_head','academic_admin','admin'],true);
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send_message'])){
    verify_csrf();
    if(!$canSend){http_response_code(403);exit('You are not allowed to send official advice from this account.');}
    $recipientId=(int)($_POST['recipient_user_id']??0);$instructorId=(int)($_POST['instructor_id']??0);
    $subject=trim($_POST['subject']??'');$body=trim($_POST['body']??'');
    $validIns=$pdo->prepare('SELECT id,full_name,department FROM instructors WHERE id=? AND is_active=1');$validIns->execute([$instructorId]);$instructor=$validIns->fetch();
    $validRec=$pdo->prepare('SELECT id,full_name,role,department,instructor_id FROM users WHERE id=? AND is_active=1');$validRec->execute([$recipientId]);$recipient=$validRec->fetch();
    $allowedRecipient=false;
    if($recipient && $instructor){
        if($u['role']==='admin') $allowedRecipient=true;
        elseif($u['role']==='department_head') $allowedRecipient=(!$u['department'] || $u['department']===$instructor['department']) && (($recipient['role']==='self' && (int)$recipient['instructor_id']===$instructorId) || $recipient['role']==='academic_admin');
        elseif($u['role']==='academic_admin') $allowedRecipient=(($recipient['role']==='self' && (int)$recipient['instructor_id']===$instructorId) || ($recipient['role']==='department_head' && (!$recipient['department'] || $recipient['department']===$instructor['department'])));
    }
    if(!$instructor || !$recipient || !$allowedRecipient) $msg='That recipient is not permitted for this message. Choose an allowed recipient and instructor.';
    elseif($subject==='' || $body==='') $msg='Subject and comment/advice are required.';
    elseif(mb_strlen($subject)>180 || mb_strlen($body)>10000) $msg='Subject must be under 180 characters and comment under 10,000 characters.';
    else{
        $q=$pdo->prepare('INSERT INTO messages(sender_user_id,recipient_user_id,instructor_id,subject,body,message_type,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $kind=$u['role']==='department_head'?'Department Head Advice':($u['role']==='academic_admin'?'Academic Administration / Committee Advice':'Official Message');
        $q->execute([(int)$u['id'],$recipientId,$instructorId,$subject,$body,$kind]);
        flash('success','Message sent. The recipient will see it in Messages & notifications.');redirect('messages.php');
    }
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_read'])){
    verify_csrf();$id=(int)($_POST['message_id']??0);$q=$pdo->prepare('UPDATE messages SET read_at=NOW() WHERE id=? AND recipient_user_id=?');$q->execute([$id,(int)$u['id']]);flash('success','Message marked as read.');redirect('messages.php');
}
$instructors=$pdo->query('SELECT id,full_name,department FROM instructors WHERE is_active=1 ORDER BY full_name')->fetchAll();
$recipients=[];
if($canSend){
    if($u['role']==='admin') $recipients=$pdo->query('SELECT id,full_name,role,department,instructor_id FROM users WHERE is_active=1 AND id<>'.(int)$u['id'].' ORDER BY full_name')->fetchAll();
    elseif($u['role']==='department_head') $recipients=$pdo->query("SELECT id,full_name,role,department,instructor_id FROM users WHERE is_active=1 AND (role='self' OR role='academic_admin') ORDER BY full_name")->fetchAll();
    else $recipients=$pdo->query("SELECT id,full_name,role,department,instructor_id FROM users WHERE is_active=1 AND (role='self' OR role='department_head') ORDER BY full_name")->fetchAll();
}
$q=$pdo->prepare('SELECT m.*,s.full_name AS sender_name,r.full_name AS recipient_name,i.full_name AS instructor_name FROM messages m JOIN users s ON s.id=m.sender_user_id JOIN users r ON r.id=m.recipient_user_id LEFT JOIN instructors i ON i.id=m.instructor_id WHERE m.recipient_user_id=? ORDER BY m.created_at DESC LIMIT 100');$q->execute([(int)$u['id']]);$inbox=$q->fetchAll();
page_header('Messages & notifications');show_flash();
?>
<div class="page-intro"><div><div class="eyebrow">INBOX · NOTIFICATIONS</div><h1>Messages &amp; notifications</h1><p>Comments and advice sent to your account appear here. Unread messages are marked clearly.</p></div><span class="weight-pill"><?=unread_notifications_count()?> unread</span></div>
<?php if($msg):?><div class="alert error"><?=e($msg)?></div><?php endif;?>
<?php if($canSend):?><section class="panel"><div class="section-head"><div><div class="eyebrow">OFFICIAL COMMUNICATION</div><h2>Send advice or a comment</h2></div></div><p class="muted tiny">Department Heads can advise an instructor or forward advice to Academic Administration / Committee. Academic Administration / Committee can advise an instructor or the relevant Department Head. Recipients see the sender and message in their inbox.</p><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="send_message" value="1"><label>Instructor<select name="instructor_id" id="message-instructor" required><option value="">Choose instructor</option><?php foreach($instructors as $i):?><option value="<?=$i['id']?>"><?=e($i['full_name'].' — '.$i['department'])?></option><?php endforeach;?></select></label><label>Send to<select name="recipient_user_id" id="message-recipient" required><option value="">Choose recipient</option><?php foreach($recipients as $r):?><option value="<?=$r['id']?>" data-role="<?=e($r['role'])?>" data-instructor="<?=e($r['instructor_id']??'')?>" data-department="<?=e($r['department']??'')?>"><?=e($r['full_name'].' — '.(roles()[$r['role']]??$r['role']))?></option><?php endforeach;?></select></label><label class="wide">Subject<input name="subject" maxlength="180" required placeholder="e.g. Feedback on teaching evaluation"></label><label class="wide">Comment / advice<textarea name="body" rows="5" maxlength="10000" required placeholder="Write constructive, professional advice…"></textarea></label><div><button class="btn primary" type="submit">Send message ↗</button></div></form></section><?php endif;?>
<section class="panel" style="margin-top:18px"><div class="section-head"><div><div class="eyebrow">YOUR PRIVATE INBOX</div><h2>Received messages</h2></div></div>
<?php if(!$inbox):?><div class="empty-state"><span>✉</span><b>No messages yet</b><p>When an authorized actor sends you a comment or advice, it will appear here.</p></div><?php else:foreach($inbox as $m):?><article class="message-card <?=empty($m['read_at'])?'unread':''?>"><div class="message-top"><div><b><?=e($m['subject'])?></b><small>From <?=e($m['sender_name'])?> · <?=e($m['message_type'])?></small></div><div class="message-meta"><?=e(date('M j, Y g:i A',strtotime($m['created_at'])))?><?php if(empty($m['read_at'])):?><span class="unread-pill">Unread</span><?php else:?><span class="read-pill">Read</span><?php endif;?></div></div><p class="muted tiny">Instructor: <?=e($m['instructor_name']??'General message')?></p><div class="message-body"><?=nl2br(e($m['body']))?></div><?php if(empty($m['read_at'])):?><form method="post" class="message-action"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="message_id" value="<?=$m['id']?>"><button class="btn small secondary" name="mark_read" value="1">Mark as read</button></form><?php endif;?></article><?php endforeach;endif;?></section>
<script>
(function(){const instructor=document.getElementById('message-instructor'),recipient=document.getElementById('message-recipient');if(!instructor||!recipient)return;const role=<?=json_encode($u['role'])?>;function filterRecipients(){const iid=instructor.value;const opt=instructor.options[instructor.selectedIndex];const dept=opt?opt.text.split(' — ').slice(1).join(' — '):'';let first=true;Array.from(recipient.options).forEach(o=>{if(!o.value){o.hidden=false;return;}let show=true;const r=o.dataset.role, linked=o.dataset.instructor, rdept=o.dataset.department;if(role==='department_head')show=(r==='academic_admin'||(r==='self'&&linked===iid));else if(role==='academic_admin')show=(r==='self'&&linked===iid)||(r==='department_head'&&(!rdept||rdept===dept));o.hidden=!show;if(!show&&o.selected)recipient.value='';});}instructor.addEventListener('change',filterRecipients);filterRecipients();})();
</script>
<?php page_footer();?>
