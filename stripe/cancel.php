<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

bairoom_require_role('Inquilino');
$reservaId = (int) ($_GET['reserva'] ?? 0);
$active = '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pago cancelado · Bairoom</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="../css/styles.css" />
    <script src="../js/main.js" defer></script>
  </head>
  <body class="page-layout owner-panel-body">
    <?php include __DIR__ . '/../includes/header-simple.php'; ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero">
        <div class="text-center">
          <h1 class="fw-bold">Pago cancelado</h1>
          <p class="text-muted mb-4">No se ha realizado ningún cargo.</p>
          <div class="d-flex justify-content-center gap-3 flex-wrap">
            <?php if ($reservaId > 0): ?>
              <a href="../pago-stripe.php?reserva=<?php echo $reservaId; ?>" class="btn btn-outline-secondary">Volver al pago</a>
            <?php endif; ?>
            <a href="../inquilino-panel.php" class="btn btn-bairoom">Volver a mi panel</a>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
