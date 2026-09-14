<?php
require_once __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
    session_start();
}
header('Cache-Control: no-store');
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function app_url(string $path = ''): string
{
    $cfg = db_config();
    $base = rtrim((string)($cfg['base_url'] ?? '/accounting'), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}
function require_login(bool $json = false): void
{
    if (!empty($_SESSION['user']['id'])) return;
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน'], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . app_url('public/login.php'));
    }
    exit;
}
