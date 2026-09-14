<?php if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; } ?>
<script>
const CSRF = <?=json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
const APP_URLS = {
  api: <?=json_encode(app_url('api.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
  login: <?=json_encode(app_url('public/login.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
  install: <?=json_encode(app_url('install.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
  incomeExpenseReport: <?=json_encode(app_url('public/income_expense_report.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>
};
window.ACCOUNTING_CURRENT_PAGE = <?=json_encode($currentPage ?? 'dashboard', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
window.ACCOUNTING_PAGE_TITLES = <?=json_encode($accountingPageTitles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
window.ACCOUNTING_PAGE_URLS = <?=json_encode($accountingPageUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
</script>
<script src="<?=htmlspecialchars(app_url('plugins/bootstrap/js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8')?>"></script>
<script src="<?=htmlspecialchars(app_url('assets/app.js'), ENT_QUOTES, 'UTF-8')?>"></script>
