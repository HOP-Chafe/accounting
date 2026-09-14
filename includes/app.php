<?php
/**
 * Application bootstrap for pages inside /modules.
 * Keep authentication, shared view variables and navigation configuration here
 * Shared bootstrap for all real module routes under /modules.
 */
require_once dirname(__DIR__) . '/auth.php';
require_login();

if (!defined('ACCOUNTING_APP')) {
    define('ACCOUNTING_APP', true);
}
if (!defined('ACCOUNTING_ROOT')) {
    define('ACCOUNTING_ROOT', dirname(__DIR__));
}

$csrf = (string)($_SESSION['csrf_token'] ?? '');
$currentUserName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
if ($currentUserName === '') {
    $currentUserName = trim((string)($_SESSION['user']['username'] ?? ''));
}

require ACCOUNTING_ROOT . '/includes/navigation.php';
