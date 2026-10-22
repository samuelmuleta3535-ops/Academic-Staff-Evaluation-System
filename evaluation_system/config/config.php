<?php
declare(strict_types=1);
define('APP_NAME','Academic Staff / Instructor Evaluation System (ASES)');
define('BASE_URL','/evaluation_system');
if(session_status()===PHP_SESSION_NONE){session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);session_start();}
