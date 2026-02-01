<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';
$prefillEmail = trim($_GET['email'] ?? '');
$fromLogin = isset($_GET['from']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $apellidos = trim($_POST['apellidos'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $telefono = trim($_POST['telefono'] ?? '');

  if ($nombre === '' || $apellidos === '' || $email === '' || $password === '') {
    $error = 'Completa todos los campos obligatorios.';
  } elseif (!preg_match('/^[\\p{L}\\s\\-\\\']{2,50}$/u', $nombre)) {
    $error = 'El nombre debe tener al menos 2 letras y solo puede incluir letras y espacios.';
  } elseif (!preg_match('/^[\\p{L}\\s\\-\\\']{2,100}$/u', $apellidos)) {
    $error = 'Los apellidos deben tener al menos 2 letras y solo pueden incluir letras y espacios.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'El email no es válido.';
  } elseif ($telefono !== '' && !preg_match('/^[0-9]{9,15}$/', $telefono)) {
    $error = 'El teléfono debe tener entre 9 y 15 dígitos.';
  } elseif (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\\d/', $password)) {
    $error = 'La contraseña debe tener al menos 6 caracteres, letras y números.';
  } else {
    $pdo = bairoom_db();
    $stmt = $pdo->prepare('SELECT id_usuario, estado FROM usuario WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    if ($existing) {
      if (($existing['estado'] ?? '') === 'inactivo') {
        $error = 'Esta cuenta está inactiva. Inicia sesión para reactivarla.';
      } else {
        $error = 'El email ya está registrado.';
      }
    } else {
      $stmt = $pdo->prepare('SELECT id_rol FROM rol WHERE nombre = ? LIMIT 1');
      $stmt->execute(['Inquilino']);
      $idRol = (int) $stmt->fetchColumn();
      if ($idRol <= 0) {
        $idRol = 3;
      }

      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare('
        INSERT INTO usuario (nombre, apellidos, email, contrasena, telefono, fecha_alta, estado, id_rol)
        VALUES (?, ?, ?, ?, ?, ?, "activo", ?)
      ');
      $stmt->execute([
        $nombre,
        $apellidos,
        $email,
        $hash,
        $telefono !== '' ? $telefono : null,
        date('Y-m-d'),
        $idRol,
      ]);

      $userRow = [
        'id_usuario' => (int) $pdo->lastInsertId(),
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'rol_nombre' => 'Inquilino',
      ];
      bairoom_login_user($userRow);
      header('Location: inquilino-panel.php?registro=ok');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom · Registro</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="css/styles.css" />
    <script src="js/main.js" defer></script>
  </head>

  <body class="page-layout">
    <?php
    $active = '';
    include __DIR__ . '/includes/header-simple.php';
    ?>

    <main class="container my-5 section-block">
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="card-bairoom shadow-sm p-4 p-md-5 rounded-4">
            <h1 class="fw-bold mb-3 text-center">Crear cuenta</h1>
            <p class="text-muted text-center mb-4">Regístrate como inquilino para reservar habitaciones.</p>
            <?php if ($fromLogin): ?>
              <div class="alert alert-info text-start">
                No encontramos tu cuenta. Regístrate para continuar.
              </div>
            <?php endif; ?>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control bairoom-input" name="nombre" required value="<?php echo htmlspecialchars($nombre ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label">Apellidos</label>
                <input type="text" class="form-control bairoom-input" name="apellidos" required value="<?php echo htmlspecialchars($apellidos ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control bairoom-input" name="email" required value="<?php echo htmlspecialchars($email ?? $prefillEmail ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label">Teléfono (opcional)</label>
                <input type="text" class="form-control bairoom-input" name="telefono" value="<?php echo htmlspecialchars($telefono ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control bairoom-input" name="password" required />
              </div>

              <button type="submit" class="btn btn-bairoom w-100">Registrarse</button>
            </form>

            <p class="text-center mt-4">
              ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
            </p>
          </div>
        </div>
      </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
