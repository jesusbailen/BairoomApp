<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

bairoom_require_role('Propietario');
$user = bairoom_current_user();
$pdo = bairoom_db();

$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['aceptar_reserva', 'rechazar_reserva'], true)) {
  $reservaId = (int) ($_POST['id_reserva'] ?? 0);
  if ($reservaId > 0) {
    $stmt = $pdo->prepare('
      SELECT r.id_reserva, r.id_habitacion
      FROM reserva r
      JOIN habitacion h ON h.id_habitacion = r.id_habitacion
      WHERE r.id_reserva = ? AND h.id_propiedad = ?
    ');
    $stmt->execute([$reservaId, (int) ($_GET['propiedad'] ?? 0)]);
    $reservaRow = $stmt->fetch();
    if ($reservaRow) {
      if ($action === 'aceptar_reserva') {
        $stmt = $pdo->prepare('UPDATE reserva SET estado = "aceptada" WHERE id_reserva = ?');
        $stmt->execute([$reservaId]);
        $stmt = $pdo->prepare('UPDATE habitacion SET estado = "ocupada" WHERE id_habitacion = ?');
        $stmt->execute([(int) $reservaRow['id_habitacion']]);
      } else {
        $stmt = $pdo->prepare('UPDATE reserva SET estado = "rechazada" WHERE id_reserva = ?');
        $stmt->execute([$reservaId]);
      }
    }
  }
  header('Location: propiedad-panel.php?propiedad=' . (int) ($_GET['propiedad'] ?? 0));
  exit;
}

$propiedadId = (int) ($_GET['propiedad'] ?? 0);
if ($propiedadId <= 0) {
  header('Location: propietario-panel.php');
  exit;
}

$stmt = $pdo->prepare('
  SELECT p.*,
    (SELECT COUNT(*) FROM habitacion h WHERE h.id_propiedad = p.id_propiedad) AS total_habitaciones,
    (SELECT COUNT(*) FROM habitacion h WHERE h.id_propiedad = p.id_propiedad AND h.estado = "ocupada") AS ocupadas
  FROM propiedad p
  WHERE p.id_propiedad = ? AND p.id_propietario = ?
');
$stmt->execute([$propiedadId, $user['id_usuario']]);
$propiedad = $stmt->fetch();
if (!$propiedad) {
  header('Location: propietario-panel.php');
  exit;
}

$today = date('Y-m-d');
$next30 = date('Y-m-d', strtotime('+30 days'));
$prev30 = date('Y-m-d', strtotime('-30 days'));
$fallbackInicio = date('d/m/Y', strtotime('-1 month'));
$fallbackFin = date('d/m/Y', strtotime('+5 months'));

$metrics = [
  'rentabilidad' => 0.0,
  'ocupacion' => 0,
  'contratos_activos' => 0,
  'proximas_salidas' => 0,
  'liquidaciones' => 0,
  'cobros_pendientes' => 0,
  'incidencias' => 0,
];

$total = (int) $propiedad['total_habitaciones'];
$ocupadas = (int) $propiedad['ocupadas'];
$metrics['ocupacion'] = $total > 0 ? (int) round(($ocupadas / $total) * 100) : 0;

$stmt = $pdo->prepare('
  SELECT COUNT(*) FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  LEFT JOIN pago p ON p.id_reserva = r.id_reserva
  WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
    AND r.fecha_inicio <= ? AND r.fecha_fin >= ?
');
$stmt->execute([$propiedadId, $today, $today]);
$metrics['contratos_activos'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('
  SELECT COUNT(*) FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  LEFT JOIN pago p ON p.id_reserva = r.id_reserva
  WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
    AND r.fecha_fin BETWEEN ? AND ?
');
$stmt->execute([$propiedadId, $today, $next30]);
$metrics['proximas_salidas'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('
  SELECT COUNT(*) FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  LEFT JOIN pago p ON p.id_reserva = r.id_reserva
  WHERE h.id_propiedad = ? AND r.estado = "aceptada" AND p.estado = "pagado"
    AND r.fecha_fin BETWEEN ? AND ?
');
$stmt->execute([$propiedadId, $prev30, $today]);
$metrics['liquidaciones'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('
  SELECT COUNT(*) FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  LEFT JOIN pago p ON p.id_reserva = r.id_reserva
  WHERE h.id_propiedad = ? AND r.estado = "aceptada"
    AND (p.estado IS NULL OR p.estado IN ("pendiente","fallido"))
');
$stmt->execute([$propiedadId]);
$metrics['cobros_pendientes'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('
  SELECT h.*,
    (SELECT r.fecha_inicio
     FROM reserva r
     WHERE r.id_habitacion = h.id_habitacion AND r.estado IN ("aceptada","pendiente")
     ORDER BY r.fecha_inicio DESC, r.id_reserva DESC
     LIMIT 1) AS fecha_inicio,
    (SELECT r.fecha_fin
     FROM reserva r
     WHERE r.id_habitacion = h.id_habitacion AND r.estado IN ("aceptada","pendiente")
     ORDER BY r.fecha_fin DESC, r.id_reserva DESC
     LIMIT 1) AS fecha_fin,
    (SELECT r.fecha_creacion
     FROM reserva r
     WHERE r.id_habitacion = h.id_habitacion AND r.estado IN ("aceptada","pendiente")
     ORDER BY r.fecha_creacion DESC, r.id_reserva DESC
     LIMIT 1) AS fecha_creacion,
    (SELECT u.nombre
     FROM reserva r
     JOIN usuario u ON u.id_usuario = r.id_usuario
     WHERE r.id_habitacion = h.id_habitacion AND r.estado IN ("aceptada","pendiente")
     ORDER BY r.fecha_inicio DESC, r.id_reserva DESC
     LIMIT 1) AS inquilino_nombre,
    (SELECT r.estado
     FROM reserva r
     WHERE r.id_habitacion = h.id_habitacion AND r.estado IN ("aceptada","pendiente")
     ORDER BY r.fecha_creacion DESC, r.id_reserva DESC
     LIMIT 1) AS reserva_estado,
    (SELECT p.estado
     FROM pago p
     JOIN reserva r ON r.id_reserva = p.id_reserva
     WHERE r.id_habitacion = h.id_habitacion
     ORDER BY p.id_pago DESC
     LIMIT 1) AS pago_estado
  FROM habitacion h
  WHERE h.id_propiedad = ?
  ORDER BY h.id_habitacion DESC
');
$stmt->execute([$propiedadId]);
$habitaciones = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT r.id_reserva, r.fecha_inicio, r.fecha_fin, r.estado,
         h.nombre AS habitacion, h.precio_noche AS precio_noche,
         CONCAT(u.nombre, " ", u.apellidos) AS inquilino
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN usuario u ON u.id_usuario = r.id_usuario
  WHERE h.id_propiedad = ? AND r.estado = "pendiente"
  ORDER BY r.fecha_creacion DESC, r.id_reserva DESC
');
$stmt->execute([$propiedadId]);
$solicitudesPendientes = $stmt->fetchAll();

// Calcular rentabilidad por noche según duración de la estancia
$totalRentabilidad = 0.0;
foreach ($habitaciones as &$hab) {
  $hab['noches'] = 0;
  $hab['rentabilidad_actual'] = 0.0;
  if (!empty($hab['fecha_inicio']) && !empty($hab['fecha_fin']) && ($hab['estado'] ?? '') !== 'disponible') {
    $inicio = new DateTime($hab['fecha_inicio']);
    $fin = new DateTime($hab['fecha_fin']);
    $hab['noches'] = max((int) $inicio->diff($fin)->format('%a'), 0) + 1;
    $precioNoche = (float) ($hab['precio_noche'] ?? $hab['precio_mensual'] ?? 0);
    $hab['rentabilidad_actual'] = $precioNoche * $hab['noches'];
    $totalRentabilidad += $hab['rentabilidad_actual'];
  }
}
unset($hab);
$metrics['rentabilidad'] = $totalRentabilidad;
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($propiedad['nombre'], ENT_QUOTES, 'UTF-8'); ?> · Panel</title>

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

  <body class="page-layout property-panel-body">
    <?php
    $active = '';
    include __DIR__ . '/../includes/header-simple.php';
    ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 property-panel-hero position-relative">
        <a href="propietario-panel.php" class="btn btn-outline-secondary btn-sm property-back">Volver al panel</a>
        <div class="text-center">
          <i class="bi bi-house-door text-primary fs-1"></i>
          <h1 class="fw-bold mt-3"><?php echo htmlspecialchars($propiedad['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="text-muted mb-0">
            <?php echo htmlspecialchars($propiedad['direccion'] . ' · ' . $propiedad['ciudad'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </section>

      <section class="panel-preview">
        <div class="panel-frame">
          <div class="panel-grid">
            <div class="panel-card panel-wide panel-card--accent">
              <div class="d-flex justify-content-between align-items-center">
                <h4>Rentabilidad total</h4>
                <span class="panel-badge panel-badge--good">
                  <i class="bi bi-graph-up"></i> +12%
                </span>
              </div>
              <strong><?php echo number_format($metrics['rentabilidad'], 2); ?> €</strong>
              <span>Ocupación <?php echo (int) $metrics['ocupacion']; ?>% · <?php echo (int) $propiedad['total_habitaciones']; ?> habitaciones</span>
              <ul class="panel-list">
                <li>
                  <i class="bi bi-calendar2-check"></i>
                  Cobros al día:
                  <?php echo $metrics['cobros_pendientes'] === 0 ? 'OK' : 'Pendiente (' . (int) $metrics['cobros_pendientes'] . ')'; ?>
                </li>
                <li><i class="bi bi-file-earmark-text"></i> <?php echo (int) $metrics['contratos_activos']; ?> contratos activos</li>
                <li><i class="bi bi-tools"></i> <?php echo (int) $metrics['incidencias']; ?> incidencias abiertas</li>
              </ul>
            </div>
            <div class="panel-card panel-narrow">
              <h4>Próximas salidas</h4>
              <strong><?php echo (int) $metrics['proximas_salidas']; ?></strong>
              <span>En los próximos 30 días</span>
            </div>
            <div class="panel-card panel-narrow">
              <h4>Liquidaciones</h4>
              <strong><?php echo (int) $metrics['liquidaciones']; ?></strong>
              <span>De los próximos 30 días</span>
            </div>
            <div class="panel-card panel-wide">
              <h4>Seguimiento de incidencias</h4>
              <ul class="panel-list">
                <li><i class="bi bi-check-circle-fill"></i> 0 resueltas</li>
                <li><i class="bi bi-clock"></i> 0 en progreso</li>
                <li><i class="bi bi-bell"></i> 0 pendientes de revisión</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

  <section class="panel-preview mt-4">
        <div class="text-center mb-4">
          <h2 class="fw-bold">Habitaciones de la vivienda</h2>
          <p class="text-muted">Estado y rentabilidad individual por habitación.</p>
        </div>
        <div class="panel-frame">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Habitación</th>
                  <th>Estado</th>
                  <th>Capacidad</th>
                  <th>Inicio</th>
                  <th>Salida</th>
                  <th>Precio por noche</th>
                  <th>Rentabilidad actual</th>
                  <th>Inquilino</th>
                  <th>Reserva</th>
                  <th>Pago</th>
                  <th>Reserva activa</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($habitaciones as $hab): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($hab['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php
                      $estado = strtolower((string) $hab['estado']);
                      $sinReserva = empty($hab['fecha_inicio']) || empty($hab['fecha_fin']);
                      if ($estado === 'ocupada' && $sinReserva) {
                        $estado = 'disponible';
                      }
                      $estadoClass = $estado === 'ocupada' ? 'estado-badge estado-ocupada' : ($estado === 'disponible' ? 'estado-badge estado-disponible' : 'estado-badge');
                      $reservaEstado = $hab['reserva_estado'] ?? '';
                      $pagoEstado = $hab['pago_estado'] ?? '';
                      $fechaInicio = $hab['fecha_inicio'] ?? '';
                      $fechaFin = $hab['fecha_fin'] ?? '';
                      $hoy = date('Y-m-d');
                      $reservaActiva = $pagoEstado === 'pagado' && $reservaEstado === 'aceptada'
                        && $fechaInicio !== '' && $fechaFin !== '' && $fechaInicio <= $hoy && $fechaFin >= $hoy;
                    ?>
                    <td>
                      <?php if ($reservaEstado === 'aceptada' && $pagoEstado !== 'pagado'): ?>
                        <span class="estado-badge estado-ocupada">pendiente pago</span>
                      <?php else: ?>
                        <span class="<?php echo $estadoClass; ?>">
                          <?php echo htmlspecialchars($hab['estado'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo (int) $hab['capacidad']; ?></td>
                    <td>
                      <?php
                        if ($estado === 'disponible') {
                          echo '-';
                        } else {
                          echo $hab['fecha_inicio'] ? date('d/m/Y', strtotime($hab['fecha_inicio'])) : $fallbackInicio;
                        }
                      ?>
                    </td>
                    <td>
                      <?php
                        if ($estado === 'disponible') {
                          echo '-';
                        } else {
                          echo $hab['fecha_fin'] ? date('d/m/Y', strtotime($hab['fecha_fin'])) : $fallbackFin;
                        }
                      ?>
                    </td>
                    <td><?php echo number_format((float) ($hab['precio_noche'] ?? $hab['precio_mensual'] ?? 0), 2); ?> €</td>
                    <td><?php echo number_format((float) $hab['rentabilidad_actual'], 2); ?> €</td>
                    <td><?php echo $hab['inquilino_nombre'] ? htmlspecialchars($hab['inquilino_nombre'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                    <td>
                      <?php
                        echo $hab['fecha_creacion']
                          ? date('d/m/Y H:i', strtotime($hab['fecha_creacion']))
                          : '-';
                      ?>
                    </td>
                    <td>
                      <?php if ($pagoEstado === 'pagado'): ?>
                        <span class="badge bg-success">Pagado</span>
                      <?php elseif ($reservaEstado === 'aceptada'): ?>
                        <span class="badge bg-warning text-dark">Pendiente</span>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($reservaActiva): ?>
                        <span class="badge bg-primary">Activa</span>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$habitaciones): ?>
                  <tr>
                    <td colspan="11" class="text-center text-muted">No hay habitaciones registradas.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
  </section>

  <?php if ($solicitudesPendientes): ?>
    <section class="panel-preview mt-4">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Solicitudes pendientes</h2>
        <p class="text-muted">Revisa y decide las reservas de esta vivienda.</p>
      </div>
      <div class="panel-frame">
        <div class="panel-card panel-wide">
          <div class="d-flex justify-content-end">
            <span class="panel-badge panel-badge--warn">
              <i class="bi bi-bell"></i> <?php echo count($solicitudesPendientes); ?>
            </span>
          </div>
          <div class="table-responsive mt-3">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Inquilino</th>
                  <th>Habitación</th>
                  <th>Entrada</th>
                  <th>Salida</th>
                  <th>Noches</th>
                  <th>Precio noche</th>
                  <th>Total</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($solicitudesPendientes as $sol): ?>
                  <?php
                    $inicio = new DateTime($sol['fecha_inicio']);
                    $fin = new DateTime($sol['fecha_fin']);
                    $noches = max((int) $inicio->diff($fin)->format('%a'), 0) + 1;
                    $precioNoche = (float) $sol['precio_noche'];
                    $total = $precioNoche * $noches;
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($sol['inquilino'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($sol['habitacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($sol['fecha_inicio'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($sol['fecha_fin'])); ?></td>
                    <td><?php echo $noches; ?></td>
                    <td><?php echo number_format($precioNoche, 2); ?> €</td>
                    <td><?php echo number_format($total, 2); ?> €</td>
                    <td class="d-flex gap-2">
                      <form method="post">
                        <input type="hidden" name="action" value="aceptar_reserva" />
                        <input type="hidden" name="id_reserva" value="<?php echo (int) $sol['id_reserva']; ?>" />
                        <button type="submit" class="btn btn-sm btn-success">Aceptar</button>
                      </form>
                      <form method="post">
                        <input type="hidden" name="action" value="rechazar_reserva" />
                        <input type="hidden" name="id_reserva" value="<?php echo (int) $sol['id_reserva']; ?>" />
                        <button type="submit" class="btn btn-sm btn-outline-danger">Rechazar</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
