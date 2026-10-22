<?php
require_once __DIR__.'/includes/bootstrap.php';
require_role(['admin']);
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $raw=trim($_POST['evaluation_deadline']??'');
    if($raw!=='' && !DateTime::createFromFormat('Y-m-d\TH:i',$raw)) $error='Enter a valid deadline date and time.';
    else {
        $value=$raw===''?null:str_replace('T',' ',$raw).':00';
        $q=$pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES('evaluation_deadline',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $q->execute([$value]);
        $a=$pdo->prepare("INSERT INTO admin_audit_log(actor_user_id,action,details) VALUES(?,?,?)");
        $a->execute([(int)current_user()['id'],'deadline_updated',$value??'Deadline removed']);
        flash('success',t('Deadline saved successfully.')); redirect('settings.php');
    }
}
$current=evaluation_deadline();
page_header(t('Deadline settings')); show_flash();
?>
<section class="page-intro"><div><div class="eyebrow">ASES ADMINISTRATION</div><h1><?=e(t('Deadline settings'))?></h1><p>Set the closing date and time for all evaluation submissions. Leave it blank to keep submissions open.</p></div></section>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post" class="panel form-panel"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-grid"><label class="wide"><?=e(t('Deadline date and time'))?><input type="datetime-local" name="evaluation_deadline" value="<?=e($current?date('Y-m-d\TH:i',strtotime($current)):'')?>"></label></div><p class="muted">When the deadline passes, the system blocks new submissions. Previously submitted evaluations and results are not changed.</p><div class="form-actions"><button class="btn primary" type="submit"><?=e(t('Save deadline'))?></button><a class="btn secondary" href="<?=BASE_URL?>/dashboard.php">Cancel</a></div></form>
<?php page_footer();?>
