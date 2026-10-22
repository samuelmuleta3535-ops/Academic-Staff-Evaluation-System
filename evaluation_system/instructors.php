<?php
// Instructor management is now part of the single User Accounts workspace.
require_once __DIR__.'/includes/bootstrap.php';
require_role(['admin']);
redirect('users.php');
