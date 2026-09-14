<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
/**
 * Legacy compatibility file.
 * The application now uses real module routes (modules/*.php) and each route
 * loads only its own view through includes/page.php.
 * This file intentionally renders nothing.
 */
