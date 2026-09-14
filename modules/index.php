<?php
require_once dirname(__DIR__) . '/auth.php';
require_login();
header('Location: ' . app_url('modules/Dashboard.php'));
exit;
