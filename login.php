<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$info = '';
$showReactivate = false;
$emailValue = '';

if (isset($_GET['desactivada'])) {
  $info = 'Tu cuenta ha sido dada de baja. Puedes reactivarla iniciando sesión.';
}
if (isset($_GET['reactivada'])) {
  $info = 'Cuenta reactivada correctamente. Ya puedes acceder.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'login';
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $emailValue = $email;

  if ($email === '' || $password === '') {
    $error = 'Por favor, completa email y contraseña.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'El email no tiene un formato válido.';
  } elseif (strlen($password) < 6) {
    $error = 'La contraseña debe tener al menos 6 caracteres.';
  } else {
    $userRow = bairoom_find_user_by_email($email);
    if (!$userRow) {
      header('Location: registro.php?email=' . urlencode($email) . '&from=login');
      exit;
    }

    $passwordOk = password_verify($password, $userRow['contrasena']);
    if (!$passwordOk) {
      $error = 'Credenciales incorrectas.';
    } elseif ($userRow['estado'] !== 'activo') {
      if ($action === 'reactivar') {
        $pdo = bairoom_db();
        $stmt = $pdo->prepare('UPDATE usuario SET estado = "activo" WHERE id_usuario = ?');
        $stmt->execute([(int) $userRow['id_usuario']]);
        $userRow['estado'] = 'activo';
        $user = bairoom_build_user_session($userRow);
        bairoom_login_user($user);
        header('Location: login.php?reactivada=1');
        exit;
      }
      $showReactivate = true;
      $error = 'Tu cuenta está inactiva. Puedes reactivarla.';
    } else {
      $user = bairoom_build_user_session($userRow);
      bairoom_login_user($user);
      if ($user['rol_nombre'] === 'Administrador') {
        header('Location: admin.php');
        exit;
      }
      if ($user['rol_nombre'] === 'Propietario') {
        header('Location: propietario/propietario-panel.php');
        exit;
      }
      header('Location: inquilino-panel.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom | Iniciar sesión</title>

    <!-- Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />

    <!-- Styles -->
    <link rel="stylesheet" href="css/styles.css" />
    <script src='js/main.js' defer></script>  </head>

  <body class="login-page">
    <main class="login-shell">
      <div class="login-card">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm login-back">
          Volver atrás
        </a>

        <a href="index.php" class="login-logo-link">
          <img src="img/logo.webp" alt="Bairoom" class="login-logo" />
        </a>

        <h1 class="login-title">Bienvenido</h1>
        <p class="login-subtitle">Accede a tu panel de Bairoom</p>

        <form class="login-form" method="post" novalidate>
          <?php if ($info): ?>
            <div class="alert alert-info text-start" role="alert">
              <?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert alert-danger text-start" role="alert">
              <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input
              type="email"
              class="form-control bairoom-input"
              placeholder="tu@email.com"
              name="email"
              value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>"
              required
            />
          </div>

          <div class="mb-2">
            <label class="form-label">Contraseña</label>
            <div class="input-group login-password">
              <input
                id="loginPassword"
                type="password"
                class="form-control bairoom-input"
                placeholder="********"
                name="password"
                required
              />
              <button
                class="btn btn-password-toggle"
                type="button"
                aria-label="Mostrar contraseña"
                data-toggle="password"
              >
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="text-end mb-4">
            <a href="inquilino-panel.php#perfil" class="login-link">¿Has olvidado tu contraseña?</a>
          </div>

          <button type="submit" class="btn btn-bairoom w-100 py-3" name="action" value="login">
            Continuar
          </button>
          <?php if ($showReactivate): ?>
            <button type="submit" class="btn btn-outline-primary w-100 py-3 mt-2" name="action" value="reactivar">
              Reactivar cuenta
            </button>
          <?php endif; ?>
        </form>

        <p class="login-footer">
          ¿No tienes cuenta? <a href="registro.php">Crear cuenta</a>
        </p>
      </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
      <script>
      document.querySelectorAll('[data-toggle="password"]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const input = btn.closest('.login-password').querySelector('input');
          const icon = btn.querySelector('i');
          const isPassword = input.type === 'password';
          input.type = isPassword ? 'text' : 'password';
          icon.classList.toggle('bi-eye');
          icon.classList.toggle('bi-eye-slash');
          btn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
      });
    </script>
  </body>
</html>
