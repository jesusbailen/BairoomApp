<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

bairoom_require_role('Inquilino');
$pdo = bairoom_db();
$env = bairoom_load_env();

$secretKey = $env['BAIROOM_STRIPE_SECRET'] ?? (getenv('BAIROOM_STRIPE_SECRET') ?: '');
if ($secretKey === '') {
  http_response_code(500);
  echo 'Stripe no está configurado.';
  exit;
}

$sessionId = $_GET['session_id'] ?? '';
if ($sessionId === '') {
  header('Location: ../inquilino-panel.php');
  exit;
}

\Stripe\Stripe::setApiKey($secretKey);

try {
  $session = \Stripe\Checkout\Session::retrieve($sessionId);
} catch (Exception $e) {
  http_response_code(500);
  echo 'No se pudo verificar el pago.';
  exit;
}

$reservaId = (int) ($session->client_reference_id ?? 0);
if ($reservaId <= 0) {
  header('Location: ../inquilino-panel.php');
  exit;
}

$stmt = $pdo->prepare('
  UPDATE pago
  SET estado = "pagado",
      stripe_payment_intent = ?,
      stripe_session_id = ?,
      fecha_pago = NOW()
  WHERE id_reserva = ?
');
$stmt->execute([$session->payment_intent ?? null, $session->id, $reservaId]);

$stmt = $pdo->prepare('
  UPDATE habitacion h
  JOIN reserva r ON r.id_habitacion = h.id_habitacion
  SET h.estado = "ocupada"
  WHERE r.id_reserva = ?
');
$stmt->execute([$reservaId]);

$active = '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pago confirmado · Bairoom</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../css/styles.css" />
  </head>
  <body class="page-layout owner-panel-body">
    <?php include __DIR__ . '/../includes/header-simple.php'; ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero">
        <div class="text-center">
          <h1 class="fw-bold">Pago confirmado</h1>
          <p class="text-muted mb-4">Tu reserva queda registrada y el pago se ha completado.</p>
          <a href="../inquilino-panel.php" class="btn btn-bairoom">Volver a mi panel</a>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
