<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }

/**
 * Main navigation is defined in one place.
 * Each menu points to a real PHP route under /modules.
 */
$accountingNavigation = [
    [
        'page' => 'dashboard',
        'title' => 'Dashboard',
        'desktop_label' => 'Dashboard',
        'mobile_label' => 'ภาพรวม',
        'icon' => '⌂',
        'module' => 'Dashboard.php',
        'route' => 'modules/Dashboard.php',
    ],
    [
        'page' => 'daily',
        'title' => 'บันทึกยอดประจำวัน',
        'desktop_label' => 'บันทึกยอดประจำวัน',
        'mobile_label' => 'บันทึก',
        'icon' => '✎',
        'mobile_icon' => '＋',
        'module' => 'Daily.php',
        'route' => 'modules/Daily.php',
    ],
    [
        'page' => 'close',
        'title' => 'ปิดรอบ',
        'desktop_label' => 'ปิดรอบ',
        'mobile_label' => 'ปิดรอบ',
        'icon' => '✓',
        'module' => 'Close.php',
        'route' => 'modules/Close.php',
    ],
    [
        'page' => 'inventory',
        'title' => 'วัตถุดิบ',
        'desktop_label' => 'วัตถุดิบ',
        'mobile_label' => 'สต็อก',
        'icon' => '▦',
        'module' => 'Inventory.php',
        'route' => 'modules/Inventory.php',
    ],
    [
        'page' => 'reports',
        'title' => 'รายงานย้อนหลัง',
        'desktop_label' => 'รายงานย้อนหลัง',
        'mobile_label' => 'รายงาน',
        'icon' => '▤',
        'module' => 'Reports.php',
        'route' => 'modules/Reports.php',
    ],
    [
        'page' => 'income-expense',
        'title' => 'สรุปบัญชีรายรับ - รายจ่าย',
        'desktop_label' => 'สรุปบัญชีรายรับ - รายจ่าย',
        'mobile_label' => 'บัญชี',
        'icon' => '฿',
        'module' => 'IncomeExpense.php',
        'route' => 'modules/IncomeExpense.php',
    ],
];

$accountingPageTitles = [];
$accountingPageUrls = [];
foreach ($accountingNavigation as $item) {
    $accountingPageTitles[$item['page']] = $item['title'];
    $accountingPageUrls[$item['page']] = app_url($item['route']);
}
