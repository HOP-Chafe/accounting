<?php
require_once dirname(__DIR__) . '/auth.php';
if (!empty($_SESSION['user']['id'])) {
    header('Location: ' . app_url('modules/Dashboard.php'));
    exit;
}
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'คำขอหมดอายุ กรุณาลองเข้าสู่ระบบอีกครั้ง';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, username, password_hash, first_name, last_name, failed_attempts, locked_until FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            $valid = password_verify((string)($_POST['password'] ?? ''), $user['password_hash'] ?? '$2y$10$aHj3HOUVVia5HHY88zcr0OQot1DiTuOpuR5OWdvAljbdTbDEsjnye');
            if ($user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                $error = 'บัญชีถูกพักชั่วคราว กรุณาลองใหม่ใน 15 นาที';
            } elseif ($user && $valid) {
                db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);
                session_regenerate_id(true);
                $_SESSION['user'] = array_intersect_key($user, array_flip(['id', 'username', 'first_name', 'last_name']));
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ' . app_url('modules/Dashboard.php'));
                exit;
            } else {
                if ($user) {
                    db()->prepare('UPDATE users SET failed_attempts = IF(locked_until IS NOT NULL AND locked_until <= NOW(), 1, failed_attempts + 1), locked_until = IF(failed_attempts >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL) WHERE id = ?')->execute([$user['id']]);
                }
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'ไม่สามารถเข้าสู่ระบบได้ กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูล';
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>เข้าสู่ระบบ | HOP Chafe' Accounting</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/app.css">
  <style>body{min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#0d2e26,#08775c)}.login-card{width:100%;max-width:420px;padding:32px}.login-card h1{font-size:26px;margin:18px 0 6px}.login-card p{color:var(--muted);margin:0 0 24px}.login-card .field{margin-bottom:18px}.login-card .btn{width:100%}.login-error{background:#fff1f1;color:#9b3535;padding:12px;border-radius:10px;margin-bottom:18px}</style>
</head>
<body>
  <main class="card login-card">
    <div class="logo-badge" style="background:var(--brand-soft)">H</div>
    <h1>เข้าสู่ระบบ</h1><p>HOP Chafe' • ระบบบัญชีร้าน</p>
    <?php if ($error): ?><div class="login-error" role="alert"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')?>">
      <div class="field"><label for="username">ชื่อผู้ใช้</label><input id="username" name="username" autocomplete="username" maxlength="100" required autofocus value="<?=htmlspecialchars($username, ENT_QUOTES, 'UTF-8')?>"></div>
      <div class="field"><label for="password">รหัสผ่าน</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <button class="btn primary" type="submit">เข้าสู่ระบบ</button>
    </form>
  </main>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
