<?php
/**
 * Shared page layout for real module routes.
 * Each file in /modules sets $currentPage and $moduleView, then includes this file.
 */
if (!isset($currentPage, $moduleView)) {
    http_response_code(500);
    exit('Page configuration is missing.');
}

require_once __DIR__ . '/app.php';

$currentNavItem = null;
foreach ($accountingNavigation as $item) {
    if (($item['page'] ?? '') === $currentPage) {
        $currentNavItem = $item;
        break;
    }
}
if ($currentNavItem === null) {
    http_response_code(404);
    exit('Page not found.');
}

$modulePath = ACCOUNTING_ROOT . '/modules/views/' . basename((string)$moduleView);
if (!is_file($modulePath)) {
    http_response_code(500);
    exit('Module view not found.');
}
$currentPageTitle = (string)$currentNavItem['title'];
?>
<!doctype html>
<html lang="th">
<?php require ACCOUNTING_ROOT . '/includes/head.php'; ?>
<body class="multi-page" data-current-page="<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8')?>">
<div class="app">
  <?php require ACCOUNTING_ROOT . '/includes/sidebar.php'; ?>

  <main class="main">
    <?php require ACCOUNTING_ROOT . '/includes/topbar.php'; ?>

    <div class="content">
      <?php require $modulePath; ?>
    </div>
  </main>
</div>

<?php require ACCOUNTING_ROOT . '/includes/mobile_nav.php'; ?>
<?php require ACCOUNTING_ROOT . '/includes/footer.php'; ?>
<?php require ACCOUNTING_ROOT . '/includes/scripts.php'; ?>
</body>
</html>
