<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../scripts/cron-finalizar-reservas.php';
require_once __DIR__ . '/../includes/db.php';

bairoom_require_role('Propietario');
$user = bairoom_current_user();
$pdo = bairoom_db();

$stmt = $pdo->prepare('
  SELECT p.*,
    (SELECT COUNT(*) FROM habitacion h WHERE h.id_propiedad = p.id_propiedad) AS total_habitaciones,
    (SELECT COUNT(*) FROM habitacion h WHERE h.id_propiedad = p.id_propiedad AND h.estado = "ocupada") AS ocupadas
  FROM propiedad p
  WHERE p.id_propietario = ?
  ORDER BY p.id_propiedad DESC
');
$stmt->execute([$user['id_usuario']]);
$propiedades = $stmt->fetchAll();

$selectedId = (int) ($_GET['propiedad'] ?? 0);
if ($selectedId <= 0 && $propiedades) {
  $selectedId = (int) $propiedades[0]['id_propiedad'];
}

$selected = null;
foreach ($propiedades as $p) {
  if ((int) $p['id_propiedad'] === $selectedId) {
    $selected = $p;
    break;
  }
}

$today = date('Y-m-d');
$next30 = date('Y-m-d', strtotime('+30 days'));
$prev30 = date('Y-m-d', strtotime('-30 days'));

$metrics = [
  'rentabilidad' => 0.0,
  'ocupacion' => 0,
  'contratos_activos' => 0,
  'proximas_salidas' => 0,
  'liquidaciones' => 0,
  'cobros_pendientes' => 0,
  'incidencias' => 0,
];

$totalRentabilidad = 0.0;
$stmt = $pdo->prepare('
  SELECT COALESCE(SUM(h.precio_noche), 0) AS total_rentabilidad
  FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE p.id_propietario = ? AND h.estado = "ocupada"
');
$stmt->execute([$user['id_usuario']]);
$totalRentabilidad = (float) $stmt->fetchColumn();

if ($selected) {
  $stmt = $pdo->prepare('
    SELECT COALESCE(SUM(precio_noche), 0) AS rentabilidad
    FROM habitacion
    WHERE id_propiedad = ? AND estado = "ocupada"
  ');
  $stmt->execute([$selectedId]);
  $metrics['rentabilidad'] = (float) $stmt->fetchColumn();

  $total = (int) $selected['total_habitaciones'];
  $ocupadas = (int) $selected['ocupadas'];
  $metrics['ocupacion'] = $total > 0 ? (int) round(($ocupadas / $total) * 100) : 0;

  $stmt = $pdo->prepare('
    SELECT COUNT(*) FROM reserva r
    JOIN habitacion h ON h.id_habitacion = r.id_habitacion
    LEFT JOIN pago p ON p.id_reserva = r.id_reserva
    WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
      AND r.fecha_inicio <= ? AND r.fecha_fin >= ?
  ');
  $stmt->execute([$selectedId, $today, $today]);
  $metrics['contratos_activos'] = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare('
    SELECT COUNT(*) FROM reserva r
    JOIN habitacion h ON h.id_habitacion = r.id_habitacion
    LEFT JOIN pago p ON p.id_reserva = r.id_reserva
    WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
      AND r.fecha_fin BETWEEN ? AND ?
  ');
  $stmt->execute([$selectedId, $today, $next30]);
  $metrics['proximas_salidas'] = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare('
    SELECT COUNT(*) FROM reserva r
    JOIN habitacion h ON h.id_habitacion = r.id_habitacion
    LEFT JOIN pago p ON p.id_reserva = r.id_reserva
    WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
      AND r.fecha_fin BETWEEN ? AND ?
  ');
  $stmt->execute([$selectedId, $prev30, $today]);
  $metrics['liquidaciones'] = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare('
    SELECT COUNT(*) FROM reserva r
    JOIN habitacion h ON h.id_habitacion = r.id_habitacion
    LEFT JOIN pago p ON p.id_reserva = r.id_reserva
    WHERE h.id_propiedad = ? AND r.estado = "aceptada"
      AND (p.estado IS NULL OR p.estado IN ("pendiente","fallido"))
  ');
  $stmt->execute([$selectedId]);
  $metrics['cobros_pendientes'] = (int) $stmt->fetchColumn();
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel del Propietario</title>

    <!-- Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />

    <!-- Styles -->
    <link rel="stylesheet" href="../css/styles.css" />
    <script src="../js/main.js" defer></script>
  </head>

  <body class="page-layout owner-panel-body">
    <?php
    $active = '';
    include __DIR__ . '/../includes/header-simple.php';
    ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero">
        <div class="text-center">
          <i class="bi bi-graph-up-arrow text-primary fs-1"></i>
          <h1 class="fw-bold mt-3">Panel del Propietario</h1>
          <p class="text-muted mb-0">
            Rentabilidad, contratos y estado de tus viviendas en tiempo real.
          </p>
        </div>
      </section>

      <section class="panel-preview mb-4">
        <div class="text-center mb-4">
          <h2 class="fw-bold">Tus viviendas</h2>
          <p class="text-muted">Selecciona una vivienda para ver su panel detallado.</p>
        </div>
        <div class="row g-4">
          <?php if ($propiedades): ?>
            <?php foreach ($propiedades as $prop): ?>
              <div class="col-md-6 col-lg-4">
                <a href="propiedad-panel.php?propiedad=<?php echo (int) $prop['id_propiedad']; ?>" class="text-decoration-none">
                  <div class="role-card role-card--owner h-100 property-card">
                    <span class="role-tag"><i class="bi bi-house-door"></i> Vivienda</span>
                    <h3 class="mb-2"><?php echo htmlspecialchars($prop['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="mb-3"><?php echo htmlspecialchars($prop['ciudad'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <ul class="role-meta">
                      <li><i class="bi bi-door-open"></i> Habitaciones: <?php echo (int) $prop['total_habitaciones']; ?></li>
                      <li><i class="bi bi-people"></i> Capacidad: <?php echo (int) $prop['capacidad_total']; ?></li>
                    </ul>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center text-muted">No tienes propiedades registradas.</div>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel-preview">
        <div class="text-center mb-4">
          <h2 class="fw-bold">Rentabilidad total con Bairoom</h2>
          <p class="text-muted">Resumen agregado de todas tus viviendas.</p>
        </div>
        <div class="panel-frame">
          <div class="panel-grid">
            <div class="panel-card panel-wide panel-card--accent">
              <div class="d-flex justify-content-between align-items-center">
                <h4>Rentabilidad total mensual</h4>
                <span class="panel-badge panel-badge--good">
                  <i class="bi bi-cash-coin"></i> Total
                </span>
              </div>
              <strong><?php echo number_format($totalRentabilidad, 2); ?> €</strong>
              <span>Sumando habitaciones ocupadas de todas tus viviendas</span>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>



