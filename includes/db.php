<?php
declare(strict_types=1);

function bairoom_load_env(): array
{
  static $cache = null;
  if (is_array($cache)) {
    return $cache;
  }
  $hostName = $_SERVER['HTTP_HOST'] ?? '';
  $isHosting = stripos($hostName, '42web.io') !== false || stripos($hostName, 'infinityfree') !== false;
  $envPath = $isHosting ? __DIR__ . '/../.env.hosting' : __DIR__ . '/../.env.local';
  if (!is_file($envPath)) {
    $envPath = __DIR__ . '/../.env';
  }

  $env = [];
  if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $trimmed = trim($line);
      if ($trimmed === '' || substr($trimmed, 0, 1) === '#') {
        continue;
      }
      $parts = explode('=', $trimmed, 2);
      if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if (substr($key, 0, 3) === "\xEF\xBB\xBF") {
          $key = substr($key, 3);
        }
        if ($key !== '') {
          $env[$key] = $value;
          if (function_exists('putenv')) {
            @putenv($key . '=' . $value);
          }
        }
      }
    }
  }
  $cache = $env;
  return $env;
}

function bairoom_db(): PDO
{
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  $env = bairoom_load_env();

  $host = $env['BAIROOM_DB_HOST'] ?? (getenv('BAIROOM_DB_HOST') ?: '127.0.0.1');
  $name = $env['BAIROOM_DB_NAME'] ?? (getenv('BAIROOM_DB_NAME') ?: 'bairoom_pi2');
  $user = $env['BAIROOM_DB_USER'] ?? (getenv('BAIROOM_DB_USER') ?: 'root');
  $pass = $env['BAIROOM_DB_PASS'] ?? (getenv('BAIROOM_DB_PASS') ?: '');
  $charset = 'utf8mb4';

  $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  $pdo = new PDO($dsn, $user, $pass, $options);
  return $pdo;
}
