<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/lib/simple-pdf.php';

ob_start();

bairoom_require_role('Administrador');
$pdo = bairoom_db();

$q = trim($_GET['q'] ?? '');
$estadoFilter = trim($_GET['estado'] ?? '');
$pagoFilter = trim($_GET['pago'] ?? '');
$sort = $_GET['sort'] ?? 'fecha_inicio';
$dir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$sortMap = [
  'fecha_inicio' => 'r.fecha_inicio',
  'fecha_fin' => 'r.fecha_fin',
  'estado' => 'r.estado',
  'habitacion' => 'h.nombre',
  'usuario' => 'u.nombre',
];
$sortSql = $sortMap[$sort] ?? $sortMap['fecha_inicio'];

$whereSql = [];
$params = [];
if ($q !== '') {
  $whereSql[] = '(u.nombre LIKE ? OR u.apellidos LIKE ? OR h.nombre LIKE ? OR p.nombre LIKE ?)';
  $like = '%' . $q . '%';
  $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($estadoFilter !== '') {
  $whereSql[] = 'r.estado = ?';
  $params[] = $estadoFilter;
}
if ($pagoFilter !== '') {
  if ($pagoFilter === 'sin') {
    $whereSql[] = '(pg.estado IS NULL)';
  } else {
    $whereSql[] = 'pg.estado = ?';
    $params[] = $pagoFilter;
  }
}
$whereSql = $whereSql ? ('WHERE ' . implode(' AND ', $whereSql)) : '';

$stmt = $pdo->prepare("
  SELECT r.*, u.nombre AS usuario, u.apellidos, h.nombre AS habitacion, p.nombre AS propiedad,
         pg.estado AS pago_estado
  FROM reserva r
  JOIN usuario u ON u.id_usuario = r.id_usuario
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
  $whereSql
  ORDER BY $sortSql $dir
");
$stmt->execute($params);
$reservas = $stmt->fetchAll();

$pdf = new BairoomSimplePDF();
$pdf->setHeader('Bairoom | Historial de reservas', 'Generado: ' . date('d/m/Y H:i'));

$filters = [];
if ($q !== '') {
  $filters[] = 'Búsqueda: ' . $q;
}
if ($estadoFilter !== '') {
  $filters[] = 'Estado: ' . $estadoFilter;
}
if ($pagoFilter !== '') {
  $filters[] = 'Pago: ' . $pagoFilter;
}
if ($filters) {
  $pdf->addLine('Filtros: ' . implode(' · ', $filters), 11, 'muted');
  $pdf->addSpacer(1);
}

$pdf->addLine('Total reservas: ' . count($reservas), 12, 'accent');
$pdf->addSpacer(1);

if (!$reservas) {
  $pdf->addLine('No hay reservas para los filtros seleccionados.', 12, 'muted');
} else {
  foreach ($reservas as $r) {
    $usuario = trim($r['usuario'] . ' ' . $r['apellidos']);
    $pagoEstado = $r['pago_estado'] ?? 'sin';
    $line = sprintf(
      '#%d | %s | %s | %s - %s | %s | pago: %s',
      (int) $r['id_reserva'],
      $usuario,
      $r['habitacion'],
      $r['fecha_inicio'],
      $r['fecha_fin'],
      $r['estado'],
      $pagoEstado
    );
    $pdf->addLine($line, 11, 'dark');
  }
}

$filename = 'reservas_admin_' . date('Ymd_His') . '.pdf';
if (ob_get_length()) {
  ob_clean();
}
$pdf->output($filename);
exit;
