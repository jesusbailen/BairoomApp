<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/lib/simple-pdf.php';

ob_start();

bairoom_require_role('Inquilino');
$user = bairoom_current_user();
$pdo = bairoom_db();

$reservaId = (int) ($_GET['id'] ?? 0);
if ($reservaId <= 0) {
  header('Location: ../inquilino-panel.php');
  exit;
}

$stmt = $pdo->prepare('
  SELECT r.*, h.nombre AS habitacion, h.precio_noche, h.tipo,
         p.nombre AS propiedad, p.ciudad, u.nombre AS inquilino
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  JOIN usuario u ON u.id_usuario = r.id_usuario
  LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
  WHERE r.id_reserva = ? AND r.id_usuario = ? AND r.estado = "aceptada" AND pg.estado = "pagado"
  ORDER BY pg.id_pago DESC
  LIMIT 1
');
$stmt->execute([$reservaId, $user['id_usuario']]);
$reserva = $stmt->fetch();
if (!$reserva) {
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

$pdf = new BairoomSimplePDF();
$pdf->setHeader('Bairoom | Comprobante de reserva', 'Reserva #' . $reservaId);

$pdf->addSpacer(1);
$pdf->addLine('Datos del inquilino', 13, 'accent');
$pdf->addLine('Nombre: ' . $reserva['inquilino'], 12, 'dark');
$pdf->addSpacer(1);

$pdf->addLine('Detalles de la estancia', 13, 'accent');
$pdf->addLine('Habitación: ' . $reserva['habitacion'] . ' (' . $reserva['tipo'] . ')', 12, 'dark');
$pdf->addLine('Propiedad: ' . $reserva['propiedad'] . ' · ' . $reserva['ciudad'], 12, 'dark');
$pdf->addLine('Entrada: ' . $inicio->format('d/m/Y'), 12, 'dark');
$pdf->addLine('Salida: ' . $fin->format('d/m/Y'), 12, 'dark');
$pdf->addLine('Noches: ' . $noches, 12, 'dark');
$pdf->addSpacer(1);

$pdf->addLine('Resumen de pago', 13, 'accent');
$pdf->addLine('Precio por noche: ' . number_format($precioNoche, 2) . ' €', 12, 'dark');
$pdf->addLine('Subtotal alojamiento: ' . number_format($subtotal, 2) . ' €', 12, 'dark');
$pdf->addLine('Tasa turística: ' . number_format($totalTasa, 2) . ' €', 12, 'dark');
$pdf->addLine('Total pagado: ' . number_format($total, 2) . ' €', 12, 'success');
$pdf->addSpacer(1);

$pdf->addLine('Estado: Reserva confirmada y pagada.', 12, 'muted');

$filename = 'reserva_' . $reservaId . '.pdf';
if (ob_get_length()) {
  ob_clean();
}
$pdf->output($filename);
exit;


