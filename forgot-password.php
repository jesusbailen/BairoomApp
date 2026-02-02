<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$info = '';
$error = '';
$demoLink = '';

function bairoom_demo_link_notice(string $link): string
{
  return 'Modo demo: usa este enlace para continuar -> ' . $link;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Introduce un email válido.';
  } else {
    $pdo = bairoom_db();
    $stmt = $pdo->prepare('SELECT id_usuario, email, estado FROM usuario WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && ($user['estado'] ?? '') === 'activo') {
      $token = bin2hex(random_bytes(32));
      $tokenHash = password_hash($token, PASSWORD_BCRYPT);
      $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

      $stmt = $pdo->prepare('
        INSERT INTO password_reset (id_usuario, token_hash, expiracion)
        VALUES (?, ?, ?)
      ');
      $stmt->execute([(int) $user['id_usuario'], $tokenHash, $expira]);

      $link = bairoom_absolute_url('reset-password.php?token=' . urlencode($token));
      $demoLink = bairoom_demo_link_notice($link);
    }

    $info = 'Si el email existe, te hemos enviado un enlace de recuperación.';
  }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom · Recuperar contraseña</title>

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
            <h1 class="fw-bold mb-3 text-center">Recuperar contraseña</h1>
            <p class="text-muted text-center mb-4">Te enviaremos un enlace a tu correo.</p>

            <?php if ($info): ?>
              <div class="alert alert-info"><?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($demoLink): ?>
              <div class="alert alert-warning"><?php echo htmlspecialchars($demoLink, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control bairoom-input" name="email" required />
              </div>
              <button type="submit" class="btn btn-bairoom w-100">Enviar enlace</button>
            </form>

            <p class="text-center mt-4">
              <a href="login.php">Volver al login</a>
            </p>
          </div>
        </div>
      </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
