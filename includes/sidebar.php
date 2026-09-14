<?php if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; } ?>
<aside class="sidebar">
  <div class="logo">
    <div class="logo-badge">H</div>
    <div>
      <strong>HOP Chafe'</strong>
      <span><?=htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8')?></span>
    </div>
  </div>

  <nav class="nav" id="desktopNav" aria-label="เมนูหลัก">
    <?php foreach ($accountingNavigation as $item): ?>
      <a
        href="<?=htmlspecialchars(app_url($item['route']), ENT_QUOTES, 'UTF-8')?>"
        class="<?=($currentPage ?? '') === $item['page'] ? 'active' : ''?>"
        data-page="<?=htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8')?>"
      ><span class="ico"><?=htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8')?></span> <?=htmlspecialchars($item['desktop_label'], ENT_QUOTES, 'UTF-8')?></a>
    <?php endforeach; ?>
  </nav>

  <div class="side-note">
    <b>Database version</b><br>
    ข้อมูลทั้งหมดบันทึกลง MySQL/MariaDB ผ่าน PHP ไม่ใช้ Local Storage แล้ว
  </div>
</aside>
