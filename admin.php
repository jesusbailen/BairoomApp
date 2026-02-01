<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
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
  <body class="page-layout">
    <?php
    $active = '';
    include __DIR__ . '/includes/header-simple.php';
    ?>

    <main class="container my-5">
      <div class="text-center mb-4">
        <h1 class="fw-bold">Panel de Administración</h1>
        <p class="text-muted">Gestiona usuarios, recursos y reservas.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card-sobre p-4 h-100">
            <h4 class="fw-bold">Usuarios</h4>
            <p class="text-muted">Alta, edición, roles y baja lógica.</p>
            <a href="admin-usuarios.php" class="btn btn-bairoom">Gestionar</a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-sobre p-4 h-100">
            <h4 class="fw-bold">Recursos</h4>
            <p class="text-muted">Propiedades y habitaciones reservables.</p>
            <a href="admin-recursos.php" class="btn btn-bairoom">Gestionar</a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-sobre p-4 h-100">
            <h4 class="fw-bold">Reservas</h4>
            <p class="text-muted">Listado, edición y control de estados.</p>
            <a href="admin-reservas.php" class="btn btn-bairoom">Gestionar</a>
          </div>
        </div>
      </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
