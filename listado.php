<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$user = bairoom_current_user();
$pdo = bairoom_db();

$ciudad = trim($_GET['ciudad'] ?? '');
$precioMax = (float) ($_GET['precio_max'] ?? 0);
$estado = trim($_GET['estado'] ?? '');

$whereSql = [];
$params = [];
if ($ciudad !== '') {
  $whereSql[] = 'p.ciudad LIKE ?';
  $params[] = '%' . $ciudad . '%';
}
if ($precioMax > 0) {
  $whereSql[] = 'h.precio_noche <= ?';
  $params[] = $precioMax;
}
if ($estado !== '') {
  $whereSql[] = 'h.estado = ?';
  $params[] = $estado;
}
$whereSql = $whereSql ? ('WHERE ' . implode(' AND ', $whereSql)) : '';

$stmt = $pdo->prepare("
  SELECT h.id_habitacion, h.nombre, h.tipo, h.capacidad, h.precio_noche, h.estado,
         p.nombre AS propiedad, p.ciudad, p.direccion, p.id_propietario,
         (SELECT hi.ruta_imagen FROM habitacion_imagen hi WHERE hi.id_habitacion = h.id_habitacion AND hi.es_principal = 1 LIMIT 1) AS imagen,
         (SELECT COUNT(*)
          FROM reserva r
          LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
          WHERE r.id_habitacion = h.id_habitacion
            AND r.estado = 'aceptada'
            AND pg.estado = 'pagado'
            AND r.fecha_inicio <= CURDATE()
            AND r.fecha_fin >= CURDATE()) AS ocupada_hoy
  FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  $whereSql
  ORDER BY h.id_habitacion DESC
");
$stmt->execute($params);
$habitaciones = $stmt->fetchAll();

$fallbackImages = [
  'img/hab1.png',
  'img/hab2.png',
  'img/hab3.png',
  'img/habsanjuanmar.png',
];

$active = '';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Listado de Habitaciones - Bairoom</title>

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
    <?php include __DIR__ . '/includes/header-simple.php'; ?>

    <main class="container my-5 section-block">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="fw-bold">Habitaciones disponibles</h1>
          <p class="text-muted mb-0">Explora habitaciones reales y solicita tu reserva.</p>
        </div>
      </div>

      <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
          <label class="form-label">Ciudad</label>
          <input type="text" class="form-control" name="ciudad" placeholder="Ej: Elche" value="<?php echo htmlspecialchars($ciudad, ENT_QUOTES, 'UTF-8'); ?>" />
        </div>

        <div class="col-md-3">
          <label class="form-label">Precio máximo (€)</label>
          <input type="number" class="form-control" name="precio_max" step="0.01" placeholder="Ej: 400" value="<?php echo $precioMax > 0 ? htmlspecialchars((string) $precioMax, ENT_QUOTES, 'UTF-8') : ''; ?>" />
        </div>

        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select class="form-select" name="estado">
            <option value="">Cualquiera</option>
            <option value="disponible" <?php echo $estado === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
            <option value="ocupada" <?php echo $estado === 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
            <option value="mantenimiento" <?php echo $estado === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
          </select>
        </div>

        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary w-100">Filtrar</button>
        </div>
      </form>

      <div class="card-bairoom shadow-sm p-4 p-md-5 rounded-4">
        <div class="tenant-room-list">
          <?php foreach ($habitaciones as $hab): ?>
            <div class="tenant-room-card">
              <div class="tenant-room-media">
                <?php
                  $fallbackImage = $fallbackImages[((int) $hab['id_habitacion']) % count($fallbackImages)];
                  $imageSrc = !empty($hab['imagen']) ? $hab['imagen'] : $fallbackImage;
                ?>
                <img src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($hab['nombre'], ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <div class="tenant-room-info">
                <h5><?php echo htmlspecialchars($hab['nombre'], ENT_QUOTES, 'UTF-8'); ?></h5>
                <span class="text-muted"><?php echo htmlspecialchars($hab['propiedad'] . ' · ' . $hab['ciudad'], ENT_QUOTES, 'UTF-8'); ?></span>
                <div class="tenant-room-meta">
                  <?php
                    $tipoRaw = strtolower((string) ($hab['tipo'] ?? 'individual'));
                    $tipoLabel = 'Individual';
                    if ($tipoRaw === 'doble') {
                      $tipoLabel = 'Doble';
                    } elseif ($tipoRaw === 'suite') {
                      $tipoLabel = 'Suite';
                    } elseif ($tipoRaw === 'estudio') {
                      $tipoLabel = 'Estudio';
                    }
                  ?>
                  <span class="badge bg-light text-dark"><?php echo $tipoLabel; ?></span>
                  <span class="badge bg-light text-dark"><?php echo (int) $hab['capacidad']; ?> plazas</span>
                  <?php if ($hab['estado'] === 'mantenimiento'): ?>
                    <span class="badge bg-light text-muted">Mantenimiento</span>
                  <?php elseif ((int) ($hab['ocupada_hoy'] ?? 0) > 0): ?>
                    <span class="badge bg-light text-danger">Ocupada hoy</span>
                  <?php elseif ($hab['estado'] === 'ocupada'): ?>
                    <span class="badge bg-light text-warning">Ocupada</span>
                  <?php else: ?>
                    <span class="badge bg-light text-success">Disponible</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="tenant-room-price">
                <strong><?php echo number_format((float) $hab['precio_noche'], 2); ?> € / noche</strong>
                <div class="d-flex gap-2 mt-2">
                  <a href="habitacion-detalle.php?id=<?php echo (int) $hab['id_habitacion']; ?>" class="btn btn-outline-secondary btn-sm">Ver más</a>
                  <?php if (!$user): ?>
                    <a href="registro.php" class="btn btn-bairoom btn-sm">Reservar</a>
                  <?php elseif (($user['rol_nombre'] ?? '') === 'Inquilino'): ?>
                    <a href="habitacion-detalle.php?id=<?php echo (int) $hab['id_habitacion']; ?>" class="btn btn-bairoom btn-sm">Reservar</a>
                  <?php elseif (($user['rol_nombre'] ?? '') === 'Propietario' && (int) $hab['id_propietario'] === (int) ($user['id_usuario'] ?? 0)): ?>
                    <span class="btn btn-outline-secondary btn-sm disabled">Tu propiedad</span>
                  <?php else: ?>
                    <span class="btn btn-outline-secondary btn-sm disabled">No disponible</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$habitaciones): ?>
            <p class="text-muted mb-0">No hay habitaciones disponibles para los filtros actuales.</p>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
