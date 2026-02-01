<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

bairoom_require_role('Inquilino');
$user = bairoom_current_user();
$pdo = bairoom_db();

$reservaId = (int) ($_GET['reserva'] ?? 0);
if ($reservaId <= 0) {
  header('Location: inquilino-panel.php');
  exit;
}

$stmt = $pdo->prepare('
  SELECT r.*, h.nombre AS habitacion, h.precio_noche, p.nombre AS propiedad, p.ciudad
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE r.id_reserva = ? AND r.id_usuario = ?
');
$stmt->execute([$reservaId, $user['id_usuario']]);
$reserva = $stmt->fetch();
if (!$reserva) {
  header('Location: inquilino-panel.php');
  exit;
}

$stmt = $pdo->prepare('SELECT id_pago, estado FROM pago WHERE id_reserva = ? ORDER BY id_pago DESC LIMIT 1');
$stmt->execute([$reservaId]);
$pago = $stmt->fetch();
$pagoEstado = $pago['estado'] ?? '';
$pagado = $pagoEstado === 'pagado';

$inicio = new DateTime($reserva['fecha_inicio']);
$fin = new DateTime($reserva['fecha_fin']);
$noches = max((int) $inicio->diff($fin)->format('%a'), 0) + 1;
$precioNoche = (float) $reserva['precio_noche'];
$subtotal = $precioNoche * $noches;
$tasaTuristica = 1.5;
$totalTasa = $tasaTuristica * $noches;
$total = $subtotal + $totalTasa;

$active = '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pago de reserva · Bairoom</title>
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
  <body class="page-layout owner-panel-body">
    <?php include __DIR__ . '/includes/header-simple.php'; ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero position-relative">
        <a href="inquilino-panel.php" class="btn btn-outline-secondary btn-sm property-back">Volver al panel</a>
        <div class="text-center">
          <i class="bi bi-credit-card text-primary fs-1"></i>
          <h1 class="fw-bold mt-3">Pasarela de pago</h1>
          <p class="text-muted mb-0">Completa el pago de tu reserva con Stripe.</p>
        </div>
      </section>

      <section class="panel-preview">
        <div class="panel-frame">
          <div class="panel-card panel-wide">
            <h4 class="fw-bold mb-3">Resumen de tu reserva</h4>
            <ul class="panel-list">
              <li><i class="bi bi-house-door"></i> <?php echo htmlspecialchars($reserva['habitacion'], ENT_QUOTES, 'UTF-8'); ?></li>
              <li><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($reserva['propiedad'] . ' · ' . $reserva['ciudad'], ENT_QUOTES, 'UTF-8'); ?></li>
              <li><i class="bi bi-calendar-event"></i> Entrada: <?php echo date('d/m/Y', strtotime($reserva['fecha_inicio'])); ?></li>
              <li><i class="bi bi-calendar-check"></i> Salida: <?php echo date('d/m/Y', strtotime($reserva['fecha_fin'])); ?></li>
              <li><i class="bi bi-moon-stars"></i> Noches: <?php echo $noches; ?></li>
              <li><i class="bi bi-cash-coin"></i> Precio por noche: <?php echo number_format($precioNoche, 2); ?> €</li>
            </ul>

            <div class="alert alert-info mt-4">
              <div class="d-flex justify-content-between">
                <span>Subtotal alojamiento</span>
                <strong><?php echo number_format($subtotal, 2); ?> €</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span>Tasa turística (<?php echo number_format($tasaTuristica, 2); ?> € / noche)</span>
                <strong><?php echo number_format($totalTasa, 2); ?> €</strong>
              </div>
              <hr />
              <div class="d-flex justify-content-between">
                <span>Total a pagar</span>
                <strong><?php echo number_format($total, 2); ?> €</strong>
              </div>
              <?php if (($reserva['estado'] ?? '') !== 'aceptada'): ?>
                <div class="mt-3 text-muted">Tu reserva todavía no ha sido aceptada.</div>
              <?php elseif ($pagado): ?>
                <div class="mt-3 text-success fw-semibold">Pago confirmado. ¡Gracias!</div>
              <?php else: ?>
                <div class="mt-3 text-muted">Serás redirigido a Stripe para completar el pago.</div>
              <?php endif; ?>
            </div>

            <?php if (($reserva['estado'] ?? '') === 'aceptada' && !$pagado): ?>
              <form method="post" action="stripe/checkout.php" class="mt-3">
                <input type="hidden" name="reserva" value="<?php echo (int) $reservaId; ?>" />
                <button type="submit" class="btn btn-bairoom">Pagar con Stripe</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
