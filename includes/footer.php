<?php
require_once __DIR__ . '/config.php';
?>
<!-- FOOTER BAIROOM -->
<footer class="footer-bairoom">
  <div class="container footer-shell">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="<?php echo bairoom_url('index.php'); ?>" class="footer-logo-link">
          <img src="<?php echo bairoom_url('img/logo.webp'); ?>" alt="Bairoom" class="footer-logo" />
        </a>
        <p class="footer-tagline">
          Alquiler transparente para propietarios e inquilinos. Gestiona tu
          estancia con claridad y confianza.
        </p>
      </div>

      <div class="footer-col">
        <h5 class="footer-title">Secciones</h5>
        <ul class="footer-list">
          <li><a href="<?php echo bairoom_url('index.php'); ?>">Inicio</a></li>
          <li><a href="<?php echo bairoom_url('sobrenosotros.php'); ?>">Sobre nosotros</a></li>
          <li><a href="<?php echo bairoom_url('coliving.php'); ?>">Coliving</a></li>
          <li><a href="<?php echo bairoom_url('propietarios.php'); ?>">Propietarios</a></li>
          <li><a href="<?php echo bairoom_url('contacto.php'); ?>">Contacto</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5 class="footer-title">Acceso</h5>
        <ul class="footer-list">
          <li><a href="<?php echo bairoom_url('login.php'); ?>">Iniciar sesión</a></li>
          <li><a href="<?php echo bairoom_url('listado.php'); ?>">Listado de habitaciones</a></li>
          <li><a href="<?php echo bairoom_url('coliving.php'); ?>">Panel inquilino</a></li>
          <li><a href="<?php echo bairoom_url('propietarios.php'); ?>">Panel propietario</a></li>
        </ul>
      </div>

            <div class="footer-col">
        <h5 class="footer-title">Legal</h5>
        <ul class="footer-list">
          <li><a href="<?php echo bairoom_url('aviso-legal.php'); ?>">Aviso legal</a></li>
          <li><a href="<?php echo bairoom_url('privacidad.php'); ?>">Privacidad</a></li>
          <li><a href="<?php echo bairoom_url('cookies.php'); ?>">Cookies</a></li>
          <li><a href="<?php echo bairoom_url('terminos.php'); ?>">Términos</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© 2026 Bairoom. Todos los derechos reservados.</span>
      <div class="footer-social">
        <a href="https://instagram.com/bairoom.rent"
          ><i class="bi bi-instagram"></i
        ></a>
        <a href="https://www.linkedin.com/in/jesusbailen/"
          ><i class="bi bi-linkedin"></i
        ></a>
        <a href="#"><i class="bi bi-tiktok"></i></a>
      </div>
    </div>
  </div>
</footer>
