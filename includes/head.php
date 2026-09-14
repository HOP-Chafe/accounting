<?php if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; } ?>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?=htmlspecialchars(($currentPageTitle ?? 'Dashboard') . " • HOP Chafe' Accounting", ENT_QUOTES, 'UTF-8')?></title>
  <link rel="stylesheet" href="<?=htmlspecialchars(app_url('plugins/bootstrap/css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8')?>">
  <link rel="stylesheet" href="<?=htmlspecialchars(app_url('assets/app.css'), ENT_QUOTES, 'UTF-8')?>">
</head>
