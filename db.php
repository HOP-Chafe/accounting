<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');

function db_config(): array
{
    global $config;
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $cfg['db_host'],
        (int)$cfg['db_port'],
        $cfg['db_name']
    );

    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
