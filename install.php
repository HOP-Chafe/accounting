<?php
require_once __DIR__ . '/auth.php';
require_login();
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');

$message = '';
$error = '';
$installed = false;

$ingredients = [
    'ไข่มุก','นมข้นหวาน','นมจืด','น้ำตาล','ครีมเทียม','กาแฟ','อเมริกาโน่','โอวัลติน',
    'โอวัลตินไวท์มอล์ล','โกโก้','เผือก','แคนตาลูป','พีช','มะพร้าว','เนสที','สตรอเบอรี่',
    'บราวน์ชูก้า','ฟรุตสลัด','ฟรุตสลัด 3 สี','แพนด้า','เฉาก๊วย','บุกบราวน์','ภูเขาไฟ','นมหมี'
];

$sample = [
['2026-06-01',132,36,15,19],['2026-06-02',124,28,6,17],['2026-06-03',146,24,10,10],['2026-06-04',144,19,8,2],['2026-06-05',139,30,9,0],['2026-06-06',181,31,16,9],['2026-06-07',163,14,20,16],['2026-06-08',0,0,0,0],['2026-06-09',106,20,9,0],['2026-06-10',129,36,13,11],['2026-06-11',115,25,15,9],['2026-06-12',128,17,10,3],['2026-06-13',136,40,18,6],['2026-06-14',199,22,24,7],['2026-06-15',104,58,6,21],['2026-06-16',125,46,15,2],['2026-06-17',119,60,26,5],['2026-06-18',104,57,8,22],['2026-06-19',83,63,9,4],['2026-06-20',156,70,15,21],['2026-06-21',163,95,31,16],['2026-06-22',0,0,0,0],['2026-06-23',141,33,10,3],['2026-06-24',106,49,14,0],['2026-06-25',125,23,17,4],['2026-06-26',123,42,13,6],['2026-06-27',145,37,43,18],['2026-06-28',147,56,22,10],['2026-06-29',109,27,28,3],['2026-06-30',108,34,8,3],
['2026-07-01',124,35,13,0],['2026-07-02',105,43,29,0],['2026-07-03',96,25,19,0],['2026-07-04',107,49,17,6],['2026-07-05',170,56,16,12]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บ');
    }
    try {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $config['db_name'])) {
            throw new RuntimeException('ชื่อฐานข้อมูลใน config.php ไม่ถูกต้อง');
        }
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['db_host'], (int)$config['db_port']);
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $dbName = $config['db_name'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");
        $pdo->exec(file_get_contents(__DIR__ . '/database/users.sql'));

        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_records (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            record_date DATE NOT NULL UNIQUE,
            front_cups INT UNSIGNED NOT NULL DEFAULT 0,
            lineman_cups INT UNSIGNED NOT NULL DEFAULT 0,
            grab_cups INT UNSIGNED NOT NULL DEFAULT 0,
            shopee_cups INT UNSIGNED NOT NULL DEFAULT 0,
            cash_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            transfer_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            other_front_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            lineman_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            grab_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            shopee_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
            ice_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
            wage_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
            cup_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
            other_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
            extra_expense_items LONGTEXT NULL,
            note VARCHAR(500) NOT NULL DEFAULT '',
            source VARCHAR(30) NOT NULL DEFAULT 'web',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_daily_date (record_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // รองรับรายการรายจ่ายเพิ่มเติมในหน้าบันทึกยอดประจำวัน โดยคงข้อมูลเดิมไว้
        $dailyExpenseColumn = $pdo->query("SHOW COLUMNS FROM daily_records LIKE 'extra_expense_items'")->fetch();
        if (!$dailyExpenseColumn) {
            $pdo->exec("ALTER TABLE daily_records ADD COLUMN extra_expense_items LONGTEXT NULL AFTER other_expense");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            used_qty DECIMAL(12,2) NULL,
            remaining_qty DECIMAL(12,2) NULL,
            unit VARCHAR(100) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cash_closings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            business_date DATE NOT NULL,
            expected_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
            counted_cash DECIMAL(12,2) NOT NULL DEFAULT 0,
            variance DECIMAL(12,2) NOT NULL DEFAULT 0,
            closed_by VARCHAR(150) NOT NULL DEFAULT '',
            denominations JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_closing_date (business_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec(file_get_contents(__DIR__ . '/database/income_expense.sql'));

        // อัปเกรดตารางสรุปรายรับ-รายจ่ายจากรุ่นเดิมที่บังคับ 1 รายการต่อวัน
        // ให้รองรับหลายรายการในวันเดียวกัน โดยคงข้อมูลเดิมไว้ทั้งหมด
        $uniqueDateIndexes = $pdo->query("SHOW INDEX FROM income_expense_records WHERE Non_unique = 0 AND Column_name = 'record_date'")->fetchAll();
        foreach ($uniqueDateIndexes as $indexRow) {
            $keyName = (string)($indexRow['Key_name'] ?? '');
            if ($keyName !== '' && strtoupper($keyName) !== 'PRIMARY') {
                $safeKeyName = str_replace('`', '``', $keyName);
                $pdo->exec("ALTER TABLE income_expense_records DROP INDEX `$safeKeyName`");
            }
        }
        $dateIndexes = $pdo->query("SHOW INDEX FROM income_expense_records WHERE Key_name = 'idx_income_expense_date'")->fetchAll();
        if (!$dateIndexes) {
            $pdo->exec('CREATE INDEX idx_income_expense_date ON income_expense_records (record_date)');
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO inventory_items (name, sort_order) VALUES (?, ?)');
        foreach ($ingredients as $i => $name) {
            $stmt->execute([$name, $i + 1]);
        }

        if (!empty($_POST['seed_sample'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO daily_records (record_date, front_cups, lineman_cups, grab_cups, shopee_cups, note, source) VALUES (?, ?, ?, ?, ?, 'นำเข้าจาก Excel ตัวอย่าง', 'excel')");
            foreach ($sample as $row) {
                $stmt->execute($row);
            }
        }

        $installed = true;
        $message = 'สร้าง/อัปเดตฐานข้อมูลสำเร็จแล้ว';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ติดตั้ง HOP Chafe' Accounting</title>
<link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
<style>
@font-face{font-family:Sarabun;src:url('assets/fonts/Sarabun-Regular.ttf') format('truetype');font-weight:400;font-style:normal;font-display:swap}
@font-face{font-family:Sarabun;src:url('assets/fonts/Sarabun-SemiBold.ttf') format('truetype');font-weight:600;font-style:normal;font-display:swap}
@font-face{font-family:Sarabun;src:url('assets/fonts/Sarabun-Bold.ttf') format('truetype');font-weight:700;font-style:normal;font-display:swap}
*{box-sizing:border-box}body{margin:0;background:#f5f8f6;color:#173129;font-family:Sarabun,Tahoma,sans-serif}.wrap{max-width:760px;margin:60px auto;padding:20px}.card{background:white;border:1px solid #dfe8e3;border-radius:20px;padding:28px;box-shadow:0 15px 40px rgba(24,55,44,.08)}h1{margin:0 0 8px;font-size:28px}.muted{color:#6d7c75}.info{background:#eef8f4;border:1px solid #d0ebdf;border-radius:14px;padding:15px;margin:18px 0;line-height:1.7}.err{background:#fff1f1;border-color:#f0caca;color:#9b3535}.ok{background:#eef9f4;color:#176a50}button,a.btn{display:inline-block;border:0;border-radius:11px;padding:11px 16px;background:#08775c;color:#fff;font:inherit;font-weight:700;text-decoration:none;cursor:pointer}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.kv{display:grid;grid-template-columns:140px 1fr;gap:7px 12px;margin-top:15px}.kv b{font-weight:600}code{background:#edf2ef;padding:2px 6px;border-radius:6px}
</style>
</head>
<body><div class="wrap"><div class="card">
<h1>ติดตั้ง / อัปเดตระบบบัญชี HOP Chafe'</h1>
<p class="muted">สำหรับ XAMPP + MySQL/MariaDB</p>
<div class="info"><b>ค่าที่ระบบจะใช้</b><div class="kv"><span>Host</span><b><?=htmlspecialchars($config['db_host'])?>:<?=htmlspecialchars((string)$config['db_port'])?></b><span>Database</span><b><?=htmlspecialchars($config['db_name'])?></b><span>User</span><b><?=htmlspecialchars($config['db_user'])?></b></div></div>
<?php if ($message): ?><div class="info ok"><b><?=htmlspecialchars($message)?></b><br>ตารางพร้อมใช้งานแล้ว คุณสามารถเปิดหน้าโปรแกรมได้ทันที</div><?php endif; ?>
<?php if ($error): ?><div class="info err"><b>ติดตั้งไม่สำเร็จ</b><br><?=htmlspecialchars($error)?><br><br>ตรวจว่า MySQL ใน XAMPP เปิดอยู่ และถ้ามีรหัสผ่าน root ให้แก้ใน <code>config.php</code></div><?php endif; ?>
<?php if (!$installed): ?>
<form method="post">
<input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')?>">
<label style="display:flex;gap:10px;align-items:flex-start;margin:18px 0"><input type="checkbox" name="seed_sample" value="1" checked style="margin-top:5px"><span><b>นำเข้าข้อมูลตัวอย่างจาก Excel</b><br><span class="muted">จำนวนแก้วเดือนมิถุนายน 2569 และต้นเดือนกรกฎาคม 2569</span></span></label>
<button type="submit">สร้าง / อัปเดตฐานข้อมูลและตาราง</button>
</form>
<?php else: ?><div class="row"><a class="btn" href="modules/Dashboard.php">เปิดระบบบัญชี</a></div><?php endif; ?>
</div></div><script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script></body></html>
