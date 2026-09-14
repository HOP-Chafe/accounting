<?php
require_once dirname(__DIR__) . '/auth.php';
require_login();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function valid_report_date(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function th_month_short(int $month): string
{
    static $months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
    return $months[$month] ?? (string)$month;
}

function th_month_long(int $month): string
{
    static $months = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
    return $months[$month] ?? (string)$month;
}

function thai_date_long(string $date): string
{
    $dt = new DateTime($date);
    return $dt->format('j') . ' ' . th_month_long((int)$dt->format('n')) . ' ' . ((int)$dt->format('Y') + 543);
}

function ledger_decode($json): array
{
    if (is_array($json)) return $json;
    $items = json_decode((string)$json, true);
    return is_array($items) ? $items : [];
}

function money_parts(float $amount): array
{
    $negative = $amount < 0;
    $cents = (int)round(abs($amount) * 100);
    $baht = intdiv($cents, 100);
    $satang = $cents % 100;
    $bahtText = number_format($baht, 0, '.', ',');
    if ($negative) $bahtText = '-' . $bahtText;
    return [$bahtText, str_pad((string)$satang, 2, '0', STR_PAD_LEFT)];
}

function render_money_cells(float $amount, string $class = ''): string
{
    [$baht, $satang] = money_parts($amount);
    return '<td class="money ' . h($class) . '">' . h($baht) . '</td><td class="satang ' . h($class) . '">' . h($satang) . '</td>';
}

function render_ledger_rows(array $rows, int $rowCount): string
{
    $html = '';
    for ($i = 0; $i < $rowCount; $i++) {
        if (isset($rows[$i])) {
            $row = $rows[$i];
            $dt = new DateTime($row['date']);
            [$baht, $satang] = money_parts((float)$row['amount']);
            $carryClass = !empty($row['carry']) ? ' carry-row' : '';
            $html .= '<tr class="data-row' . $carryClass . '">'
                . '<td class="month">' . h(th_month_short((int)$dt->format('n'))) . '</td>'
                . '<td class="day">' . h($dt->format('j')) . '</td>'
                . '<td class="description">' . h((string)$row['label']) . '</td>'
                . '<td class="money">' . h($baht) . '</td>'
                . '<td class="satang">' . h($satang) . '</td>'
                . '</tr>';
        } else {
            $html .= '<tr class="data-row blank"><td></td><td></td><td></td><td></td><td></td></tr>';
        }
    }
    return $html;
}

function day_year_caption(string $date): string
{
    return 'ประจำปี ' . ((int)substr($date, 0, 4) + 543);
}

function day_table_year_caption(string $date): string
{
    return 'พ.ศ. ' . ((int)substr($date, 0, 4) + 543);
}

$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$autoPrint = ($_GET['autoprint'] ?? '') === '1';
$error = '';
$records = [];

if (!valid_report_date($from) || !valid_report_date($to)) {
    $error = 'กรุณาระบุช่วงวันที่สำหรับออกรายงานให้ถูกต้อง';
} elseif ($from > $to) {
    $error = 'วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด';
} else {
    try {
        $stmt = db()->prepare('SELECT * FROM income_expense_records WHERE record_date BETWEEN ? AND ? ORDER BY record_date ASC, id ASC');
        $stmt->execute([$from, $to]);
        $records = $stmt->fetchAll();
        if (!$records) $error = 'ไม่พบข้อมูลสรุปบัญชีในช่วงวันที่ที่เลือก';
    } catch (Throwable $e) {
        $error = 'ไม่สามารถอ่านข้อมูลสรุปบัญชีจากฐานข้อมูลได้';
    }
}

// Group all saved history records by date so one calendar date becomes one printed page.
$dailyReports = [];
if (!$error && $records) {
    foreach ($records as $record) {
        $date = (string)$record['record_date'];
        if (!isset($dailyReports[$date])) {
            $opening = (float)$record['carry_forward'];
            $dailyReports[$date] = [
                'date' => $date,
                'opening_balance' => $opening,
                'income_total' => 0.0,
                'expense_total' => 0.0,
                'grand_total' => 0.0,
                'ending_balance' => (float)$record['balance'],
                'income_rows' => [[
                    'date' => $date,
                    'label' => 'ยอดยกมา',
                    'amount' => $opening,
                    'carry' => true,
                ]],
                'expense_rows' => [],
                'record_count' => 0,
            ];
        }

        $dailyReports[$date]['record_count']++;
        $dailyReports[$date]['income_total'] += (float)$record['income_total'];
        $dailyReports[$date]['expense_total'] += (float)$record['expense_total'];
        $dailyReports[$date]['ending_balance'] = (float)$record['balance'];

        $incomeItems = ledger_decode($record['income_items']);
        $hasIncomeDetail = false;
        foreach ($incomeItems as $item) {
            $amount = round((float)($item['amount'] ?? 0), 2);
            $label = trim((string)($item['label'] ?? ''));
            if (abs($amount) < 0.00001) continue;
            if ($label === '') $label = 'รายรับ';
            $dailyReports[$date]['income_rows'][] = [
                'date' => $date,
                'label' => $label,
                'amount' => $amount,
            ];
            $hasIncomeDetail = true;
        }
        if (!$hasIncomeDetail && abs((float)$record['income_total']) > 0.00001) {
            $dailyReports[$date]['income_rows'][] = [
                'date' => $date,
                'label' => 'รายรับวันนี้',
                'amount' => (float)$record['income_total'],
            ];
        }

        $expenseItems = ledger_decode($record['expense_items']);
        $hasExpenseDetail = false;
        foreach ($expenseItems as $item) {
            $amount = round((float)($item['amount'] ?? 0), 2);
            $label = trim((string)($item['label'] ?? ''));
            if (abs($amount) < 0.00001) continue;
            if ($label === '') $label = 'รายจ่าย';
            $dailyReports[$date]['expense_rows'][] = [
                'date' => $date,
                'label' => $label,
                'amount' => $amount,
            ];
            $hasExpenseDetail = true;
        }
        if (!$hasExpenseDetail && abs((float)$record['expense_total']) > 0.00001) {
            $dailyReports[$date]['expense_rows'][] = [
                'date' => $date,
                'label' => 'รายจ่ายวันนี้',
                'amount' => (float)$record['expense_total'],
            ];
        }
    }

    foreach ($dailyReports as &$day) {
        $day['grand_total'] = $day['opening_balance'] + $day['income_total'];
    }
    unset($day);
}

$dailyReports = array_values($dailyReports);

$reportConfigFile = dirname(__DIR__) . '/report_config.php';
$reportCfg = is_file($reportConfigFile) ? require $reportConfigFile : [];
$ownerName = (string)($reportCfg['owner_name'] ?? 'จิตรลดา วินิจฉัย');
$citizenId = (string)($reportCfg['citizen_id'] ?? '1719900211512');
$businessName = (string)($reportCfg['business_name'] ?? "HOP Chafe' สาขาตลาดชุมพล");
$taxId = (string)($reportCfg['tax_id'] ?? '1719900211512');

$periodLabel = valid_report_date($from) && valid_report_date($to)
    ? thai_date_long($from) . ($from === $to ? '' : ' ถึง ' . thai_date_long($to))
    : '';
$pageCount = max(1, count($dailyReports));

$printNow = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
$printedAt = thai_date_long($printNow->format('Y-m-d')) . ' เวลา ' . $printNow->format('H:i:s') . ' น.';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>สรุปบัญชีรายรับ - รายจ่าย<?= $periodLabel ? ' | ' . h($periodLabel) : '' ?></title>
<style>
@font-face{font-family:Sarabun;src:url('../assets/fonts/Sarabun-Regular.ttf') format('truetype');font-weight:400;font-style:normal;font-display:swap}
@font-face{font-family:Sarabun;src:url('../assets/fonts/Sarabun-SemiBold.ttf') format('truetype');font-weight:600;font-style:normal;font-display:swap}
@font-face{font-family:Sarabun;src:url('../assets/fonts/Sarabun-Bold.ttf') format('truetype');font-weight:700;font-style:normal;font-display:swap}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:#eef1ef;color:#111;font-family:Sarabun,Tahoma,sans-serif;font-size:14px}
.report-toolbar{position:sticky;top:0;z-index:10;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;background:#fff;border-bottom:1px solid #d9dfdc;box-shadow:0 2px 9px rgba(20,50,40,.08)}
.report-toolbar strong{display:block}.report-toolbar small{color:#66756e}.toolbar-actions{display:flex;gap:8px}.btn{border:1px solid #cfd8d4;border-radius:9px;background:#fff;padding:9px 14px;font:600 14px Sarabun;cursor:pointer}.btn.primary{background:#0b755d;border-color:#0b755d;color:#fff}
.error-card{max-width:760px;margin:60px auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center}.error-card h2{margin-top:0;color:#b43d3d}
.report-pages{padding:18px}
.report-page{width:297mm;min-height:210mm;margin:0 auto 18px;background:#fff;padding:9mm 12mm 7mm;box-shadow:0 5px 20px rgba(0,0,0,.08);page-break-after:always;break-after:page;display:flex;flex-direction:column}
.report-page:last-child{page-break-after:auto;break-after:auto}
.report-header{text-align:center;margin-bottom:4mm;line-height:1.32}.report-header h1{font-size:18px;margin:0 0 .8mm;font-weight:700}.report-header h2{font-size:16px;margin:0 0 1.6mm;font-weight:700}.identity{font-size:13px;margin:.6mm 0}.identity b{font-weight:700}.range-line{font-size:11.5px;color:#33473f;margin-top:1.3mm;font-weight:600}
.ledger-pair{display:grid;grid-template-columns:1fr 1fr;border:1px solid #111}.ledger-half:first-child{border-right:2px solid #111}.ledger-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:11.5px}.ledger-table th,.ledger-table td{border-right:1px solid #111;border-bottom:1px solid #111;padding:1.05mm 1.2mm;height:6.7mm;vertical-align:middle}.ledger-table th:last-child,.ledger-table td:last-child{border-right:0}.ledger-table thead th{text-align:center;font-weight:700}.ledger-table .year-head{height:7.5mm;font-size:12px}.ledger-table .month{width:14mm;text-align:center}.ledger-table .day{width:12mm;text-align:center}.ledger-table .description{width:auto;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ledger-table .money{width:24mm;text-align:right}.ledger-table .satang{width:12mm;text-align:center}.ledger-table .data-row td{height:6.6mm}.ledger-table .carry-row td{font-weight:600;background:#fff9df}.ledger-table tfoot th,.ledger-table tfoot td{height:7.5mm;font-weight:700}.ledger-table .summary-label{text-align:right;background:#e6e6e6}.ledger-table .summary-money{background:#e6e6e6}.ledger-table .final-label{text-align:right}.ledger-table .final-value{color:#e00000;font-size:13px}
.report-page.compact .ledger-table{font-size:10.5px}.report-page.compact .ledger-table th,.report-page.compact .ledger-table td{padding:.65mm 1mm}.report-page.compact .ledger-table .data-row td{height:5mm}.report-page.compact .ledger-table thead th,.report-page.compact .ledger-table tfoot th,.report-page.compact .ledger-table tfoot td{height:6.4mm}
.report-page.ultra-compact .ledger-table{font-size:9.5px}.report-page.ultra-compact .ledger-table th,.report-page.ultra-compact .ledger-table td{padding:.35mm .8mm}.report-page.ultra-compact .ledger-table .data-row td{height:4mm}.report-page.ultra-compact .ledger-table thead th,.report-page.ultra-compact .ledger-table tfoot th,.report-page.ultra-compact .ledger-table tfoot td{height:5.4mm}.report-page.ultra-compact .report-header{margin-bottom:2.5mm}.report-page.ultra-compact .report-header h1{font-size:16px}.report-page.ultra-compact .report-header h2{font-size:14px}.report-page.ultra-compact .identity{font-size:11.5px}
.page-footer{margin-top:auto;padding-top:2.4mm;color:#53645c;font-size:9.5px}.page-meta{display:flex;justify-content:space-between;align-items:center;gap:10px;border-top:1px solid #d7ddda;padding-top:1.8mm}.print-stamp{margin-top:1mm;text-align:right;font-weight:600;color:#374940}.history-count{font-weight:600;color:#263c33}
@page{size:A4 landscape;margin:0}
@media print{html,body{background:#fff}.report-toolbar{display:none!important}.report-pages{padding:0}.report-page{margin:0;box-shadow:none;width:297mm;height:210mm;min-height:210mm;overflow:hidden}.report-page:last-child{page-break-after:auto}.page-footer{break-inside:avoid}}
@media(max-width:1100px){.report-pages{overflow:auto}}
</style>
</head>
<body>
<?php if ($error): ?>
<div class="report-toolbar"><div><strong>รายงานสรุปบัญชีรายรับ - รายจ่าย</strong><small>ไม่สามารถสร้างรายงานได้</small></div><div class="toolbar-actions"><button class="btn" onclick="window.close()">ปิด</button></div></div>
<div class="error-card"><h2>ไม่สามารถออกรายงานได้</h2><p><?=h($error)?></p><button class="btn primary" onclick="window.close()">กลับไปเลือกช่วงวันที่ใหม่</button></div>
<?php else: ?>
<div class="report-toolbar">
  <div><strong>ตัวอย่างก่อนบันทึก PDF</strong><small><?=h($periodLabel)?> • <span class="history-count"><?=count($dailyReports)?> วัน / <?=count($records)?> รายการจากประวัติสรุปบัญชี</span> • แยก 1 วันต่อ 1 หน้า</small></div>
  <div class="toolbar-actions"><button class="btn" onclick="window.close()">กลับ</button><button class="btn primary" onclick="window.print()">บันทึก / พิมพ์ PDF</button></div>
</div>
<div class="report-pages">
<?php foreach ($dailyReports as $pageIndex => $day):
    $rowCount = max(15, count($day['income_rows']), count($day['expense_rows']));
    $compactClass = $rowCount > 23 ? ' ultra-compact' : ($rowCount > 15 ? ' compact' : '');
    $date = $day['date'];
    $dayLabel = thai_date_long($date);
    $yearCaption = day_year_caption($date);
    $tableYearCaption = day_table_year_caption($date);
?>
<section class="report-page<?=$compactClass?>">
  <header class="report-header">
    <h1>สรุปบัญชีรายรับ - รายจ่าย</h1>
    <h2><?=h($yearCaption)?></h2>
    <div class="identity"><b>ชื่อผู้ประกอบกิจการ</b> <?=h($ownerName)?> &nbsp; <b>เลขประจำตัวประชาชน</b> <?=h($citizenId)?></div>
    <div class="identity"><b>ชื่อสถานประกอบการ</b> <?=h($businessName)?> &nbsp; <b>เลขประจำตัวผู้เสียภาษีอากร</b> <?=h($taxId)?></div>
    <div class="range-line">ประจำวันที่ <?=h($dayLabel)?></div>
  </header>

  <div class="ledger-pair">
    <div class="ledger-half">
      <table class="ledger-table">
        <colgroup><col class="month"><col class="day"><col class="description"><col class="money"><col class="satang"></colgroup>
        <thead>
          <tr><th colspan="2" class="year-head"><?=h($tableYearCaption)?></th><th rowspan="2">รายการรับ</th><th colspan="2">รวมเงิน</th></tr>
          <tr><th>เดือน</th><th>วันที่</th><th>บาท</th><th>ส.ต.</th></tr>
        </thead>
        <tbody><?=render_ledger_rows($day['income_rows'],$rowCount)?></tbody>
        <tfoot>
          <tr><th colspan="3" class="summary-label">รวมรายรับ</th><?=render_money_cells((float)$day['income_total'],'summary-money')?></tr>
          <tr><th colspan="3" class="final-label">รวมทั้งสิ้น</th><?=render_money_cells((float)$day['grand_total'],'final-value')?></tr>
        </tfoot>
      </table>
    </div>
    <div class="ledger-half">
      <table class="ledger-table">
        <colgroup><col class="month"><col class="day"><col class="description"><col class="money"><col class="satang"></colgroup>
        <thead>
          <tr><th colspan="2" class="year-head"><?=h($tableYearCaption)?></th><th rowspan="2">รายการจ่าย</th><th colspan="2">รวมเงิน</th></tr>
          <tr><th>เดือน</th><th>วันที่</th><th>บาท</th><th>ส.ต.</th></tr>
        </thead>
        <tbody><?=render_ledger_rows($day['expense_rows'],$rowCount)?></tbody>
        <tfoot>
          <tr><th colspan="3" class="summary-label">รวมรายจ่าย</th><?=render_money_cells((float)$day['expense_total'],'summary-money')?></tr>
          <tr><th colspan="3" class="final-label">ยอดคงเหลือ</th><?=render_money_cells((float)$day['ending_balance'],'final-value')?></tr>
        </tfoot>
      </table>
    </div>
  </div>

  <footer class="page-footer">
    <div class="page-meta">
      <span>ประจำวันที่ <?=h($dayLabel)?> • จากประวัติสรุปบัญชี <?= (int)$day['record_count'] ?> รายการ</span>
      <span>หน้า <?=($pageIndex + 1)?> / <?=$pageCount?></span>
    </div>
    <div class="print-stamp">พิมพ์เอกสารเมื่อ <?=h($printedAt)?></div>
  </footer>
</section>
<?php endforeach; ?>
</div>
<script>
const AUTO_PRINT = <?= $autoPrint ? 'true' : 'false' ?>;
window.addEventListener('load',()=>{
  if(!AUTO_PRINT)return;
  const go=()=>setTimeout(()=>window.print(),250);
  if(document.fonts&&document.fonts.ready){document.fonts.ready.then(go).catch(go);}else{go();}
});
</script>
<?php endif; ?>
</body>
</html>
