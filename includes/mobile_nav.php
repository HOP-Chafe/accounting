<?php if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; } ?>
<nav class="mobile-nav" id="mobileNav" aria-label="เมนูมือถือ">
  <?php foreach ($accountingNavigation as $item): ?>
    <a
      href="<?=htmlspecialchars(app_url($item['route']), ENT_QUOTES, 'UTF-8')?>"
      class="<?=($currentPage ?? '') === $item['page'] ? 'active' : ''?>"
      data-page="<?=htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8')?>"
    >
      <b><?=htmlspecialchars($item['mobile_icon'] ?? $item['icon'], ENT_QUOTES, 'UTF-8')?></b><?=htmlspecialchars($item['mobile_label'], ENT_QUOTES, 'UTF-8')?>
    </a>
  <?php endforeach; ?>
</nav>
