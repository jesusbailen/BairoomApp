<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

function bairoom_find_reset(string $token): ?array
{
  $pdo = bairoom_db();
  $stmt = $pdo->prepare('
    SELECT pr.*, u.email
    FROM password_reset pr
    JOIN usuario u ON u.id_usuario = pr.id_usuario
    WHERE pr.usado_en IS NULL AND pr.expiracion >= NOW()
    ORDER BY pr.id_reset DESC
    LIMIT 25
  ');
  $stmt->execute();
  $rows = $stmt->fetchAll();
  foreach ($rows as $row) {
    if (password_verify($token, $row['token_hash'])) {
      return $row;
    }
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = trim($_POST['token'] ?? '');
  $password = $_POST['password'] ?? '';
  $repeat = $_POST['password_repeat'] ?? '';

  if ($token === '') {
    $error = 'Token inválido.';
  } elseif ($password === '' || $repeat === '') {
    $error = 'Completa todos los campos.';
  } elseif ($password !== $repeat) {
    $error = 'Las contraseñas no coinciden.';
  } elseif (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\\d/', $password)) {
    $error = 'La contraseña debe tener al menos 6 caracteres, letras y números.';
  } else {
    $reset = bairoom_find_reset($token);
    if (!$reset) {
      $error = 'El enlace no es válido o ha caducado.';
    } else {
      $pdo = bairoom_db();
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
      $stmt->execute([$hash, (int) $reset['id_usuario']]);

      $stmt = $pdo->prepare('UPDATE password_reset SET usado_en = NOW() WHERE id_reset = ?');
      $stmt->execute([(int) $reset['id_reset']]);

      $success = 'Tu contraseña se ha actualizado. Ya puedes iniciar sesión.';
    }
  }
} else {
  if ($token !== '' && !bairoom_find_reset($token)) {
    $error = 'El enlace no es válido o ha caducado.';
  }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom · Restablecer contraseña</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="css/styles.css" />
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
            <h1 class="fw-bold mb-3 text-center">Restablecer contraseña</h1>

            <?php if ($success): ?>
              <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
              <p class="text-center"><a href="login.php">Ir al login</a></p>
            <?php else: ?>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>

              <?php if ($token !== '' && !$error): ?>
                <form method="post" novalidate>
                  <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" />
                  <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control bairoom-input" name="password" required />
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Repite la nueva contraseña</label>
                    <input type="password" class="form-control bairoom-input" name="password_repeat" required />
                  </div>
                  <button type="submit" class="btn btn-bairoom w-100">Actualizar</button>
                </form>
              <?php elseif ($token === ''): ?>
                <p class="text-muted text-center">Falta el token de recuperación.</p>
                <p class="text-center"><a href="forgot-password.php">Solicitar nuevo enlace</a></p>
              <?php else: ?>
                <p class="text-center"><a href="forgot-password.php">Solicitar nuevo enlace</a></p>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
