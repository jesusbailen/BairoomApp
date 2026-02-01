<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function bairoom_session_start(): void
{
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function bairoom_current_user(): ?array
{
  bairoom_session_start();
  return $_SESSION['bairoom_user'] ?? null;
}

function bairoom_login_user(array $user): void
{
  bairoom_session_start();
  $_SESSION['bairoom_user'] = $user;
}

function bairoom_logout_user(): void
{
  bairoom_session_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function bairoom_require_login(): void
{
  if (!bairoom_current_user()) {
    header('Location: login.php');
    exit;
  }
}

function bairoom_require_role(string $roleName): void
{
  $user = bairoom_current_user();
  if (!$user || ($user['rol_nombre'] ?? '') !== $roleName) {
    header('Location: login.php');
    exit;
  }
}

function bairoom_require_roles(array $roles): void
{
  $user = bairoom_current_user();
  if (!$user || !in_array($user['rol_nombre'] ?? '', $roles, true)) {
    header('Location: login.php');
    exit;
  }
}

function bairoom_authenticate(string $email, string $password): ?array
{
  $pdo = bairoom_db();
  $stmt = $pdo->prepare('
    SELECT u.id_usuario, u.nombre, u.apellidos, u.email, u.contrasena, u.estado, r.id_rol, r.nombre AS rol_nombre
    FROM usuario u
    INNER JOIN rol r ON r.id_rol = u.id_rol
    WHERE u.email = :email
    LIMIT 1
  ');
  $stmt->execute(['email' => $email]);
  $row = $stmt->fetch();

  if (!$row || $row['estado'] !== 'activo') {
    return null;
  }

  if (!password_verify($password, $row['contrasena'])) {
    return null;
  }

  return bairoom_build_user_session($row);
}

function bairoom_find_user_by_email(string $email): ?array
{
  $pdo = bairoom_db();
  $stmt = $pdo->prepare('
    SELECT u.id_usuario, u.nombre, u.apellidos, u.email, u.contrasena, u.estado, r.id_rol, r.nombre AS rol_nombre
    FROM usuario u
    INNER JOIN rol r ON r.id_rol = u.id_rol
    WHERE u.email = :email
    LIMIT 1
  ');
  $stmt->execute(['email' => $email]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function bairoom_build_user_session(array $row): array
{
  return [
    'id_usuario' => (int) $row['id_usuario'],
    'nombre' => $row['nombre'],
    'apellidos' => $row['apellidos'],
    'email' => $row['email'],
    'id_rol' => (int) $row['id_rol'],
    'rol_nombre' => $row['rol_nombre'],
  ];
}
