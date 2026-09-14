<?php if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; } ?>
<header class="topbar">
  <div>
    <h1 id="topTitle"><?=htmlspecialchars($currentPageTitle ?? "HOP Chafe'", ENT_QUOTES, 'UTF-8')?></h1>
    <small>HOP Chafe' • Accounting Database</small>
  </div>

  <div class="top-actions">
    <form method="post" action="<?=htmlspecialchars(app_url('public/logout.php'), ENT_QUOTES, 'UTF-8')?>">
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8')?>">
      <button class="btn ghost" type="submit" style="display:inline-block">ออกจากระบบ</button>
    </form>
    <span class="badge offline" id="dbBadge">● กำลังเชื่อมต่อ</span>
    <?php if (in_array(($currentPage ?? ''), ['dashboard', 'reports', 'income-expense'], true)): ?>
      <button class="btn ghost" type="button" onclick="exportCSV()">Export CSV</button>
    <?php endif; ?>
    <a class="btn primary" href="<?=htmlspecialchars(app_url('modules/Daily.php'), ENT_QUOTES, 'UTF-8')?>">+ บันทึกวันนี้</a>
  </div>
</header>
