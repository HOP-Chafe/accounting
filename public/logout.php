<?php
require_once dirname(__DIR__) . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บ');
}
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: ' . app_url('public/login.php'));
exit;
