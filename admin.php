<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/scripts/cron-finalizar-reservas.php';
bairoom_require_role('Administrador');
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel de Administración</title>

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
  <body class="page-layout owner-panel-body admin-panel">
    <?php
    $active = '';
    include __DIR__ . '/includes/header-simple.php';
    ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-4 owner-panel-hero text-center">
        <i class="bi bi-speedometer2 text-primary fs-1"></i>
        <h1 class="fw-bold mt-3">Panel de administración</h1>
        <p class="text-muted mb-0">Elige una acción para gestionar la plataforma.</p>
      </section>

      <section class="panel-preview">
        <div class="panel-frame">
          <div class="panel-grid">
            <div class="panel-card panel-narrow">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 48px; height: 48px;">
                  <i class="bi bi-people text-primary fs-4"></i>
                </span>
                <div>
                  <h4 class="fw-bold mb-0">Usuarios</h4>
                  <span class="text-muted">Gestión de clientes y roles</span>
                </div>
              </div>
              <ul class="text-muted mb-3" style="padding-left: 1rem;">
                <li>Alta, edición y baja lógica</li>
                <li>Asignación de roles</li>
                <li>Búsqueda y paginación</li>
              </ul>
              <a href="admin-usuarios.php" class="btn btn-bairoom btn-sm">Gestionar usuarios</a>
            </div>

            <div class="panel-card panel-narrow">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 48px; height: 48px;">
                  <i class="bi bi-building text-primary fs-4"></i>
                </span>
                <div>
                  <h4 class="fw-bold mb-0">Recursos</h4>
                  <span class="text-muted">Propiedades y habitaciones</span>
                </div>
              </div>
              <ul class="text-muted mb-3" style="padding-left: 1rem;">
                <li>Crear y editar propiedades</li>
                <li>Control de habitaciones</li>
                <li>Estados y disponibilidad</li>
              </ul>
              <a href="admin-recursos.php" class="btn btn-bairoom btn-sm">Gestionar recursos</a>
            </div>

            <div class="panel-card panel-narrow">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 48px; height: 48px;">
                  <i class="bi bi-calendar2-check text-primary fs-4"></i>
                </span>
                <div>
                  <h4 class="fw-bold mb-0">Reservas</h4>
                  <span class="text-muted">Seguimiento y control</span>
                </div>
              </div>
              <ul class="text-muted mb-3" style="padding-left: 1rem;">
                <li>Listado de reservas activas</li>
                <li>Editar o eliminar pendientes</li>
                <li>Filtrar por estado y pago</li>
              </ul>
              <a href="admin-reservas.php" class="btn btn-bairoom btn-sm">Gestionar reservas</a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

