<?php
require_once __DIR__ . '/auth.php';
require_login(true);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_post_csrf(array $input): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['ok' => false, 'message' => 'Method not allowed'], 405);
    }
    $token = (string)($input['csrf'] ?? '');
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        respond(['ok' => false, 'message' => 'CSRF token ไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บ'], 403);
    }
}

function valid_month(string $month): bool
{
    return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function num(array $data, string $key): float
{
    $value = $data[$key] ?? 0;
    return is_numeric($value) ? max(0, (float)$value) : 0;
}

function int_num(array $data, string $key): int
{
    return (int)round(num($data, $key));
}


function signed_num(array $data, string $key): float
{
    $value = $data[$key] ?? 0;
    return is_numeric($value) ? (float)$value : 0;
}

function ledger_items(array $data, string $key): array
{
    $items = $data[$key] ?? [];
    if (!is_array($items)) {
        respond(['ok' => false, 'message' => 'รูปแบบรายการบัญชีไม่ถูกต้อง'], 422);
    }
    if (count($items) > 40) {
        respond(['ok' => false, 'message' => 'รายการบัญชีต่อฝั่งต้องไม่เกิน 40 รายการ'], 422);
    }

    $clean = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $label = trim((string)($item['label'] ?? ''));
        if ($label === '') continue;
        if (mb_strlen($label, 'UTF-8') > 120) {
            respond(['ok' => false, 'message' => 'ชื่อรายการบัญชียาวเกิน 120 ตัวอักษร'], 422);
        }
        $amount = $item['amount'] ?? 0;
        $amount = is_numeric($amount) ? max(0, (float)$amount) : 0;
        $clean[] = ['label' => $label, 'amount' => round($amount, 2)];
    }
    return $clean;
}

function decode_daily_record(array $row): array
{
    $row['extra_expense_items'] = json_decode((string)($row['extra_expense_items'] ?? '[]'), true) ?: [];
    return $row;
}

function decode_ledger_record(array $row): array
{
    $row['income_items'] = json_decode((string)($row['income_items'] ?? '[]'), true) ?: [];
    $row['expense_items'] = json_decode((string)($row['expense_items'] ?? '[]'), true) ?: [];
    return $row;
}

$action = $_GET['action'] ?? 'status';

try {
    $pdo = db();

    if ($action === 'status') {
        $pdo->query('SELECT 1');
        respond([
            'ok' => true,
            'database' => db_config()['db_name'],
            'server_time' => date('c'),
            'csrf' => $_SESSION['csrf_token'],
        ]);
    }

    if ($action === 'months') {
        $rows = $pdo->query("SELECT DATE_FORMAT(record_date, '%Y-%m') AS month FROM daily_records GROUP BY month ORDER BY month DESC")->fetchAll();
        respond(['ok' => true, 'months' => array_column($rows, 'month')]);
    }

    if ($action === 'records') {
        $month = trim((string)($_GET['month'] ?? ''));
        if ($month !== '' && !valid_month($month)) {
            respond(['ok' => false, 'message' => 'รูปแบบเดือนไม่ถูกต้อง'], 422);
        }

        if ($month !== '') {
            $stmt = $pdo->prepare("SELECT * FROM daily_records WHERE DATE_FORMAT(record_date, '%Y-%m') = ? ORDER BY record_date");
            $stmt->execute([$month]);
        } else {
            $stmt = $pdo->query('SELECT * FROM daily_records ORDER BY record_date DESC LIMIT 366');
        }
        $rows = array_map('decode_daily_record', $stmt->fetchAll());
        respond(['ok' => true, 'records' => $rows]);
    }

    if ($action === 'inventory') {
        $rows = $pdo->query('SELECT id, name, used_qty, remaining_qty, unit, updated_at FROM inventory_items ORDER BY sort_order, id')->fetchAll();
        respond(['ok' => true, 'items' => $rows]);
    }

    if ($action === 'closings') {
        $stmt = $pdo->query('SELECT id, business_date, expected_cash, counted_cash, variance, closed_by, denominations, created_at FROM cash_closings ORDER BY business_date DESC, id DESC LIMIT 20');
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['denominations'] = json_decode($row['denominations'] ?: '{}', true) ?: [];
        }
        respond(['ok' => true, 'closings' => $rows]);
    }

    if ($action === 'income_expense_months') {
        $rows = $pdo->query("SELECT DATE_FORMAT(record_date, '%Y-%m') AS month FROM income_expense_records GROUP BY month ORDER BY month DESC")->fetchAll();
        respond(['ok' => true, 'months' => array_column($rows, 'month')]);
    }

    if ($action === 'income_expense_records') {
        $month = trim((string)($_GET['month'] ?? ''));
        if ($month !== '' && !valid_month($month)) {
            respond(['ok' => false, 'message' => 'รูปแบบเดือนไม่ถูกต้อง'], 422);
        }
        if ($month !== '') {
            $stmt = $pdo->prepare("SELECT * FROM income_expense_records WHERE DATE_FORMAT(record_date, '%Y-%m') = ? ORDER BY record_date ASC, id ASC");
            $stmt->execute([$month]);
        } else {
            $stmt = $pdo->query('SELECT * FROM income_expense_records ORDER BY record_date DESC, id DESC LIMIT 366');
        }
        $rows = array_map('decode_ledger_record', $stmt->fetchAll());
        respond(['ok' => true, 'records' => $rows]);
    }

    if ($action === 'income_expense_date') {
        $date = trim((string)($_GET['date'] ?? ''));
        if (!valid_date($date)) {
            respond(['ok' => false, 'message' => 'กรุณาระบุวันที่ให้ถูกต้อง'], 422);
        }

        // ยอดยกมาจะอ้างอิงเฉพาะรายการล่าสุดภายในเดือนเดียวกับวันที่ที่เลือกเท่านั้น
        // ไม่ยกยอดคงเหลือข้ามเดือน และยังรองรับหลายรายการในวันเดียวกัน
        $monthStart = substr($date, 0, 7) . '-01';
        $stmt = $pdo->prepare('SELECT id, record_date, balance FROM income_expense_records WHERE record_date >= ? AND record_date <= ? ORDER BY record_date DESC, id DESC LIMIT 1');
        $stmt->execute([$monthStart, $date]);
        $previous = $stmt->fetch() ?: null;

        $stmt = $pdo->prepare('SELECT * FROM daily_records WHERE record_date = ? LIMIT 1');
        $stmt->execute([$date]);
        $dailyRow = $stmt->fetch() ?: null;
        $daily = $dailyRow ? decode_daily_record($dailyRow) : null;

        respond([
            'ok' => true,
            'previous' => $previous,
            'daily' => $daily,
        ]);
    }

    if ($action === 'income_expense_record') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(['ok' => false, 'message' => 'ไม่พบรหัสรายการที่ต้องการแก้ไข'], 422);
        }

        $stmt = $pdo->prepare('SELECT * FROM income_expense_records WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if (!$record) {
            respond(['ok' => false, 'message' => 'ไม่พบรายการสรุปบัญชี'], 404);
        }
        $record = decode_ledger_record($record);

        $stmt = $pdo->prepare('SELECT * FROM daily_records WHERE record_date = ? LIMIT 1');
        $stmt->execute([$record['record_date']]);
        $dailyRow = $stmt->fetch() ?: null;
        $daily = $dailyRow ? decode_daily_record($dailyRow) : null;

        respond([
            'ok' => true,
            'record' => $record,
            'daily' => $daily,
        ]);
    }

    $input = json_input();
    require_post_csrf($input);

    if ($action === 'save_daily') {
        $date = trim((string)($input['date'] ?? ''));
        if (!valid_date($date)) {
            respond(['ok' => false, 'message' => 'กรุณาระบุวันที่ให้ถูกต้อง'], 422);
        }
        $extraExpenseItems = ledger_items($input, 'extraExpenseItems');

        $sql = "INSERT INTO daily_records (
                    record_date, front_cups, lineman_cups, grab_cups, shopee_cups,
                    cash_revenue, transfer_revenue, other_front_revenue,
                    lineman_revenue, grab_revenue, shopee_revenue,
                    ice_expense, wage_expense, cup_expense, other_expense, extra_expense_items, note, source
                ) VALUES (
                    :record_date, :front_cups, :lineman_cups, :grab_cups, :shopee_cups,
                    :cash_revenue, :transfer_revenue, :other_front_revenue,
                    :lineman_revenue, :grab_revenue, :shopee_revenue,
                    :ice_expense, :wage_expense, :cup_expense, :other_expense, :extra_expense_items, :note, 'web'
                ) ON DUPLICATE KEY UPDATE
                    front_cups = VALUES(front_cups), lineman_cups = VALUES(lineman_cups),
                    grab_cups = VALUES(grab_cups), shopee_cups = VALUES(shopee_cups),
                    cash_revenue = VALUES(cash_revenue), transfer_revenue = VALUES(transfer_revenue),
                    other_front_revenue = VALUES(other_front_revenue), lineman_revenue = VALUES(lineman_revenue),
                    grab_revenue = VALUES(grab_revenue), shopee_revenue = VALUES(shopee_revenue),
                    ice_expense = VALUES(ice_expense), wage_expense = VALUES(wage_expense),
                    cup_expense = VALUES(cup_expense), other_expense = VALUES(other_expense),
                    extra_expense_items = VALUES(extra_expense_items),
                    note = VALUES(note), source = 'web'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':record_date' => $date,
            ':front_cups' => int_num($input, 'front'),
            ':lineman_cups' => int_num($input, 'line'),
            ':grab_cups' => int_num($input, 'grab'),
            ':shopee_cups' => int_num($input, 'shopee'),
            ':cash_revenue' => num($input, 'cash'),
            ':transfer_revenue' => num($input, 'transfer'),
            ':other_front_revenue' => num($input, 'thai'),
            ':lineman_revenue' => num($input, 'lineRev'),
            ':grab_revenue' => num($input, 'grabRev'),
            ':shopee_revenue' => num($input, 'shopeeRev'),
            ':ice_expense' => num($input, 'ice'),
            ':wage_expense' => num($input, 'wage'),
            ':cup_expense' => num($input, 'cupCost'),
            ':other_expense' => num($input, 'otherExp'),
            ':extra_expense_items' => json_encode($extraExpenseItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':note' => trim((string)($input['note'] ?? '')),
        ]);

        respond(['ok' => true, 'message' => 'บันทึกข้อมูลประจำวันเรียบร้อย']);
    }

    if ($action === 'delete_daily') {
        $date = trim((string)($input['date'] ?? ''));
        if (!valid_date($date)) {
            respond(['ok' => false, 'message' => 'วันที่ไม่ถูกต้อง'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM daily_records WHERE record_date = ?');
        $stmt->execute([$date]);
        respond(['ok' => true, 'message' => 'ลบข้อมูลแล้ว']);
    }

    if ($action === 'add_inventory') {
        $name = trim((string)($input['name'] ?? ''));
        $unit = trim((string)($input['unit'] ?? ''));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || mb_strlen($unit, 'UTF-8') > 100) {
            respond(['ok' => false, 'message' => 'กรุณาระบุชื่อวัตถุดิบไม่เกิน 150 ตัวอักษร และหน่วย/หมายเหตุไม่เกิน 100 ตัวอักษร'], 422);
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO inventory_items (name, unit, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order), 0) + 1 FROM inventory_items');
            $stmt->execute([$name, $unit]);
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                respond(['ok' => false, 'message' => 'มีชื่อวัตถุดิบนี้อยู่แล้ว กรุณาใช้ชื่ออื่น'], 422);
            }
            throw $e;
        }
        respond(['ok' => true, 'item' => ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'unit' => $unit, 'used_qty' => null, 'remaining_qty' => null]]);
    }

    if ($action === 'save_inventory') {
        $items = $input['items'] ?? [];
        if (!is_array($items)) {
            respond(['ok' => false, 'message' => 'ข้อมูลวัตถุดิบไม่ถูกต้อง'], 422);
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE inventory_items SET used_qty = :used_qty, remaining_qty = :remaining_qty, unit = :unit WHERE id = :id');
        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0) continue;
            $used = ($item['used_qty'] ?? '') === '' ? null : max(0, (float)$item['used_qty']);
            $remaining = ($item['remaining_qty'] ?? '') === '' ? null : max(0, (float)$item['remaining_qty']);
            $stmt->execute([
                ':used_qty' => $used,
                ':remaining_qty' => $remaining,
                ':unit' => trim((string)($item['unit'] ?? '')),
                ':id' => $id,
            ]);
        }
        $pdo->commit();
        respond(['ok' => true, 'message' => 'บันทึกวัตถุดิบเรียบร้อย']);
    }

    if ($action === 'save_close') {
        $date = trim((string)($input['business_date'] ?? ''));
        if (!valid_date($date)) {
            respond(['ok' => false, 'message' => 'กรุณาระบุวันที่ปิดรอบ'], 422);
        }

        $expected = num($input, 'expected');
        $counted = num($input, 'counted');
        $variance = $counted - $expected;
        $denoms = $input['denoms'] ?? [];
        if (!is_array($denoms)) $denoms = [];

        // ผู้ปิดรอบต้องอ้างอิงผู้ใช้ที่ Login อยู่จริง ไม่รับชื่อจาก Browser
        $closedBy = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
        if ($closedBy === '') {
            $closedBy = trim((string)($_SESSION['user']['username'] ?? ''));
        }

        $stmt = $pdo->prepare('INSERT INTO cash_closings (business_date, expected_cash, counted_cash, variance, closed_by, denominations) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $date,
            $expected,
            $counted,
            $variance,
            $closedBy,
            json_encode($denoms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        respond(['ok' => true, 'message' => 'บันทึกการปิดรอบเรียบร้อย']);
    }

    if ($action === 'save_income_expense') {
        $id = (int)($input['id'] ?? 0);
        $date = trim((string)($input['date'] ?? ''));
        if (!valid_date($date)) {
            respond(['ok' => false, 'message' => 'กรุณาระบุวันที่ให้ถูกต้อง'], 422);
        }

        $carry = round(signed_num($input, 'carry_forward'), 2);
        $incomeItems = ledger_items($input, 'income_items');
        $expenseItems = ledger_items($input, 'expense_items');
        $incomeTotal = round(array_sum(array_column($incomeItems, 'amount')), 2);
        $expenseTotal = round(array_sum(array_column($expenseItems, 'amount')), 2);
        $grandTotal = round($carry + $incomeTotal, 2);
        $balance = round($grandTotal - $expenseTotal, 2);
        $note = trim((string)($input['note'] ?? ''));
        if (mb_strlen($note, 'UTF-8') > 500) {
            respond(['ok' => false, 'message' => 'หมายเหตุต้องไม่เกิน 500 ตัวอักษร'], 422);
        }

        $params = [
            ':record_date' => $date,
            ':carry_forward' => $carry,
            ':income_items' => json_encode($incomeItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':expense_items' => json_encode($expenseItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':income_total' => $incomeTotal,
            ':grand_total' => $grandTotal,
            ':expense_total' => $expenseTotal,
            ':balance' => $balance,
            ':note' => $note,
        ];

        if ($id > 0) {
            $sql = "UPDATE income_expense_records SET
                        record_date = :record_date,
                        carry_forward = :carry_forward,
                        income_items = :income_items,
                        expense_items = :expense_items,
                        income_total = :income_total,
                        grand_total = :grand_total,
                        expense_total = :expense_total,
                        balance = :balance,
                        note = :note
                    WHERE id = :id";
            $params[':id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare('SELECT id FROM income_expense_records WHERE id = ? LIMIT 1');
                $check->execute([$id]);
                if (!$check->fetch()) respond(['ok' => false, 'message' => 'ไม่พบรายการที่ต้องการอัปเดต'], 404);
            }
            $savedId = $id;
            $message = 'อัปเดตสรุปบัญชีรายรับ - รายจ่ายเรียบร้อย';
        } else {
            $sql = "INSERT INTO income_expense_records
                        (record_date, carry_forward, income_items, expense_items, income_total, grand_total, expense_total, balance, note)
                    VALUES
                        (:record_date, :carry_forward, :income_items, :expense_items, :income_total, :grand_total, :expense_total, :balance, :note)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $savedId = (int)$pdo->lastInsertId();
            $message = 'บันทึกสรุปบัญชีรายรับ - รายจ่ายเรียบร้อย';
        }

        respond([
            'ok' => true,
            'message' => $message,
            'id' => $savedId,
            'totals' => [
                'income_total' => $incomeTotal,
                'grand_total' => $grandTotal,
                'expense_total' => $expenseTotal,
                'balance' => $balance,
            ],
        ]);
    }

    if ($action === 'delete_income_expense') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            respond(['ok' => false, 'message' => 'ไม่พบรหัสรายการที่ต้องการลบ'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM income_expense_records WHERE id = ?');
        $stmt->execute([$id]);
        respond(['ok' => true, 'message' => 'ลบสรุปบัญชีแล้ว']);
    }

    respond(['ok' => false, 'message' => 'ไม่พบคำสั่ง API'], 404);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond([
        'ok' => false,
        'message' => 'เชื่อมต่อฐานข้อมูลไม่ได้ หรือยังไม่ได้ติดตั้งฐานข้อมูล',
        'detail' => $e->getMessage(),
    ], 500);
} catch (Throwable $e) {
    respond(['ok' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
}
