<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
$active = $active ?? "";
$user = bairoom_current_user();
if (!function_exists("bairoom_nav_class")) {
  function bairoom_nav_class($key, $active) {
    return $active === $key ? " active" : "";
  }
}
?>
<header class="header-bairoom-simple">
  <div class="container header-simple-shell">
    <nav class="navbar navbar-expand-lg w-100">
      <a class="navbar-brand" href="<?php echo bairoom_url('index.php'); ?>">
        <img src="<?php echo bairoom_url('img/logo.webp'); ?>" class="hero-logo-img" alt="Bairoom" />
      </a>

      <button
        class="navbar-toggler border-0"
        type="button"
        id="openMenu"
        aria-label="Abrir menú"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
        <ul class="navbar-nav nav-pill-group align-items-lg-center text-center">
          <li class="nav-item">
            <a
              class="nav-link fw-bold nav-pill<?php echo bairoom_nav_class("sobrenosotros", $active); ?>"
              href="<?php echo bairoom_url('sobrenosotros.php'); ?>"
            >
              SOBRE NOSOTROS
            </a>
          </li>

          <li class="nav-item">
            <a
              class="nav-link fw-bold nav-pill<?php echo bairoom_nav_class("coliving", $active); ?>"
              href="<?php echo bairoom_url('coliving.php'); ?>"
            >
              COLIVING
            </a>
          </li>

          <li class="nav-item">
            <a
              class="nav-link fw-bold nav-pill<?php echo bairoom_nav_class("propietarios", $active); ?>"
              href="<?php echo bairoom_url('propietarios.php'); ?>"
            >
              PROPIETARIOS
            </a>
          </li>

          <li class="nav-item">
            <a
              class="nav-link fw-bold nav-pill<?php echo bairoom_nav_class("contacto", $active); ?>"
              href="<?php echo bairoom_url('contacto.php'); ?>"
            >
              CONTACTO
            </a>
          </li>
          <?php if ($user && ($user['rol_nombre'] ?? '') === 'Propietario'): ?>
            <li class="nav-item">
              <a class="nav-link fw-bold nav-pill nav-pill--panel" href="<?php echo bairoom_url('propietario/propietario-panel.php'); ?>">
                MI PANEL
              </a>
            </li>
          <?php elseif ($user && ($user['rol_nombre'] ?? '') === 'Inquilino'): ?>
            <li class="nav-item">
              <a class="nav-link fw-bold nav-pill nav-pill--panel" href="<?php echo bairoom_url('inquilino-panel.php'); ?>">
                MI PANEL
              </a>
            </li>
          <?php endif; ?>
        </ul>

        <ul class="navbar-nav align-items-lg-center text-center ms-lg-3">
          <?php if ($user): ?>
            <li class="nav-item d-none d-lg-block">
              <span class="nav-link fw-bold nav-user-greeting">Hola, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <li class="nav-item d-none d-lg-block">
              <a href="<?php echo bairoom_url('logout.php'); ?>" class="btn btn-session">Cerrar sesión</a>
            </li>
            <li class="nav-item d-lg-none mt-3">
              <span class="nav-link fw-bold nav-user-greeting">Hola, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
            </li>
            <li class="nav-item d-lg-none mt-2">
              <a href="<?php echo bairoom_url('logout.php'); ?>" class="btn btn-primary w-100">Cerrar sesión</a>
            </li>
          <?php else: ?>
            <li class="nav-item d-none d-lg-block">
              <a href="<?php echo bairoom_url('login.php'); ?>" class="btn btn-session">Iniciar sesión</a>
            </li>
            <li class="nav-item d-none d-lg-block ms-2">
              <a href="<?php echo bairoom_url('registro.php'); ?>" class="btn btn-outline-primary btn-session">Registrarse</a>
            </li>

            <li class="nav-item d-lg-none mt-3">
              <a href="<?php echo bairoom_url('login.php'); ?>" class="btn btn-primary w-100">Iniciar sesión</a>
            </li>
            <li class="nav-item d-lg-none mt-2">
              <a href="<?php echo bairoom_url('registro.php'); ?>" class="btn btn-outline-primary w-100">Registrarse</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </nav>
  </div>
</header>
  <div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-top">
      <a href="<?php echo bairoom_url('index.php'); ?>">
        <img src="<?php echo bairoom_url('img/logo.webp'); ?>" alt="Bairoom" class="mobile-menu-logo" />
      </a>
      <button class="close-menu" id="closeMenu" aria-label="Cerrar menú">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="mobile-menu-body">
      <?php if ($user): ?>
        <div class="mobile-menu-greeting nav-user-greeting">Hola, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <nav class="mobile-menu-links">
        <a href="<?php echo bairoom_url('sobrenosotros.php'); ?>">Sobre nosotros</a>
        <a href="<?php echo bairoom_url('coliving.php'); ?>">Coliving</a>
        <a href="<?php echo bairoom_url('propietarios.php'); ?>">Propietarios</a>
        <a href="<?php echo bairoom_url('contacto.php'); ?>">Contacto</a>
        <?php if ($user && ($user['rol_nombre'] ?? '') === 'Propietario'): ?>
          <a href="<?php echo bairoom_url('propietario/propietario-panel.php'); ?>">Mi panel</a>
        <?php elseif ($user && ($user['rol_nombre'] ?? '') === 'Inquilino'): ?>
          <a href="<?php echo bairoom_url('inquilino-panel.php'); ?>">Mi panel</a>
        <?php endif; ?>
      </nav>

      <div class="mobile-menu-actions">
        <?php if ($user): ?>
          <a href="<?php echo bairoom_url('logout.php'); ?>" class="btn-mobile-primary">Cerrar sesión</a>
        <?php else: ?>
          <a href="<?php echo bairoom_url('login.php'); ?>" class="btn-mobile-primary">Iniciar sesión</a>
          <a href="<?php echo bairoom_url('registro.php'); ?>" class="btn-mobile-secondary">Registrarse</a>
        <?php endif; ?>
      </div>

      <div class="mobile-menu-legal">
        <a href="#">Legal</a>
        <a href="#">Cookies</a>
        <a href="#">Privacidad</a>
      </div>

      <div class="mobile-menu-social">
        <a href="https://instagram.com/bairoom.rent"><i class="bi bi-instagram"></i></a>
        <a href="https://www.linkedin.com/in/jesusbailen/"><i class="bi bi-linkedin"></i></a>
        <a href="#"><i class="bi bi-tiktok"></i></a>
      </div>
    </div>
  </div>
