<?php
declare(strict_types=1);
$db_host='127.0.0.1'; $db_name='acedamic_staff'; $db_user='root'; $db_pass=''; $db_port=3307;
try{$pdo=new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",$db_user,$db_pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
catch(PDOException $e){http_response_code(500);exit('<h2>Database connection failed</h2><p>Check MySQL status, database name, port, username and password in config/database.php.</p><code>'.htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').'</code>');}
