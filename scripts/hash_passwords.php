<?php
declare(strict_types=1);

$users = [
  'admin@bairoom.com' => 'admin123',
  'laura@bairoom.com' => 'prop123',
  'carlos@bairoom.com' => 'prop123',
  'marta@bairoom.com' => 'prop123',
  'ana@bairoom.com' => 'inquilino123',
  'pablo@bairoom.com' => 'inquilino123',
  'sergio@bairoom.com' => 'inquilino123',
  'lucia@bairoom.com' => 'inquilino123',
  'david@bairoom.com' => 'inquilino123',
  'elena@bairoom.com' => 'inquilino123',
];

$host = getenv('BAIROOM_DB_HOST') ?: '127.0.0.1';
$name = getenv('BAIROOM_DB_NAME') ?: 'bairoom_pi2';
$user = getenv('BAIROOM_DB_USER') ?: 'root';
$pass = getenv('BAIROOM_DB_PASS') ?: '';

$mysqli = @mysqli_connect($host, $user, $pass, $name);
if (!$mysqli) {
  fwrite(STDERR, "Error de conexión MySQL: " . mysqli_connect_error() . PHP_EOL);
  exit(1);
}

$stmt = mysqli_prepare($mysqli, 'UPDATE usuario SET contrasena = ? WHERE email = ?');
if (!$stmt) {
  fwrite(STDERR, "Error preparando consulta: " . mysqli_error($mysqli) . PHP_EOL);
  exit(1);
}

foreach ($users as $email => $plain) {
  $hash = password_hash($plain, PASSWORD_DEFAULT);
  mysqli_stmt_bind_param($stmt, 'ss', $hash, $email);
  mysqli_stmt_execute($stmt);
  echo "Actualizado: {$email}\n";
}

mysqli_stmt_close($stmt);
mysqli_close($mysqli);
