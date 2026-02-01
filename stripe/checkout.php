<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

bairoom_require_role('Inquilino');
$user = bairoom_current_user();
$pdo = bairoom_db();
$env = bairoom_load_env();

$secretKey = $env['BAIROOM_STRIPE_SECRET'] ?? (getenv('BAIROOM_STRIPE_SECRET') ?: '');
if ($secretKey === '') {
  http_response_code(500);
  echo 'Stripe no está configurado.';
  exit;
}

$reservaId = (int) ($_POST['reserva'] ?? 0);
if ($reservaId <= 0) {
  header('Location: ../inquilino-panel.php');
  exit;
}

$stmt = $pdo->prepare('
  SELECT r.*, h.nombre AS habitacion, h.precio_noche, h.id_habitacion,
         p.nombre AS propiedad, p.ciudad
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE r.id_reserva = ? AND r.id_usuario = ?
  LIMIT 1
');
$stmt->execute([$reservaId, $user['id_usuario']]);
$reserva = $stmt->fetch();

if (!$reserva || ($reserva['estado'] ?? '') !== 'aceptada') {
  header('Location: ../inquilino-panel.php');
  exit;
}

$inicio = new DateTime($reserva['fecha_inicio']);
$fin = new DateTime($reserva['fecha_fin']);
$noches = max((int) $inicio->diff($fin)->format('%a'), 0) + 1;

$precioNoche = (float) $reserva['precio_noche'];
$subtotal = $precioNoche * $noches;
$tasaTuristica = 1.5;
$totalTasa = $tasaTuristica * $noches;
$total = $subtotal + $totalTasa;

$stmt = $pdo->prepare('SELECT id_pago, estado FROM pago WHERE id_reserva = ? ORDER BY id_pago DESC LIMIT 1');
$stmt->execute([$reservaId]);
$pago = $stmt->fetch();

if ($pago && ($pago['estado'] ?? '') === 'pagado') {
  header('Location: ../pago-stripe.php?reserva=' . $reservaId . '&pagado=1');
  exit;
}

if ($pago) {
  $stmt = $pdo->prepare('UPDATE pago SET importe = ?, estado = "pendiente" WHERE id_pago = ?');
  $stmt->execute([$total, $pago['id_pago']]);
} else {
  $stmt = $pdo->prepare('
    INSERT INTO pago (id_reserva, estado, importe, moneda)
    VALUES (?, "pendiente", ?, "EUR")
  ');
  $stmt->execute([$reservaId, $total]);
}

\Stripe\Stripe::setApiKey($secretKey);

function bairoom_base_url(): string
{
  $env = bairoom_load_env();
  if (!empty($env['BAIROOM_BASE_URL'])) {
    return rtrim($env['BAIROOM_BASE_URL'], '/');
  }
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  $basePath = rtrim(str_replace('/stripe/checkout.php', '', $script), '/');
  return $scheme . '://' . $host . $basePath;
}

$baseUrl = bairoom_base_url();
$successUrl = $baseUrl . '/stripe/success.php?session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/stripe/cancel.php?reserva=' . $reservaId;

$session = \Stripe\Checkout\Session::create([
  'mode' => 'payment',
  'client_reference_id' => (string) $reservaId,
  'metadata' => [
    'reserva_id' => (string) $reservaId,
    'user_id' => (string) $user['id_usuario'],
    'habitacion_id' => (string) $reserva['id_habitacion'],
  ],
  'line_items' => [
    [
      'price_data' => [
        'currency' => 'eur',
        'product_data' => [
          'name' => 'Alojamiento - ' . $reserva['habitacion'],
        ],
        'unit_amount' => (int) round($precioNoche * 100),
      ],
      'quantity' => $noches,
    ],
    [
      'price_data' => [
        'currency' => 'eur',
        'product_data' => [
          'name' => 'Tasa turística',
        ],
        'unit_amount' => (int) round($tasaTuristica * 100),
      ],
      'quantity' => $noches,
    ],
  ],
  'success_url' => $successUrl,
  'cancel_url' => $cancelUrl,
]);

$stmt = $pdo->prepare('UPDATE pago SET stripe_session_id = ? WHERE id_reserva = ?');
$stmt->execute([$session->id, $reservaId]);

header('Location: ' . $session->url, true, 303);
exit;
