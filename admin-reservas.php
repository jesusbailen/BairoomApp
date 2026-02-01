<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

bairoom_require_role('Administrador');
$pdo = bairoom_db();

$error = '';
$success = '';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'crear_reserva') {
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    $idHabitacion = (int) ($_POST['id_habitacion'] ?? 0);
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'pendiente';

    if ($idUsuario <= 0 || $idHabitacion <= 0 || $fechaInicio === '' || $fechaFin === '') {
      $error = 'Completa los datos de la reserva.';
    } else {
      $stmt = $pdo->prepare('
        INSERT INTO reserva (fecha_inicio, fecha_fin, estado, num_personas, motivo, observaciones, id_usuario, id_habitacion, fecha_creacion)
        VALUES (?, ?, ?, 1, "Reserva admin", "", ?, ?, NOW())
      ');
      $stmt->execute([$fechaInicio, $fechaFin, $estado, $idUsuario, $idHabitacion]);
      $success = 'Reserva creada.';
    }
  }

  if ($action === 'actualizar_reserva') {
    $idReserva = (int) ($_POST['id_reserva'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    $idHabitacion = (int) ($_POST['id_habitacion'] ?? 0);
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'pendiente';

    if ($idReserva <= 0 || $idUsuario <= 0 || $idHabitacion <= 0 || $fechaInicio === '' || $fechaFin === '') {
      $error = 'Completa los datos de la reserva.';
    } else {
      $stmt = $pdo->prepare('
        UPDATE reserva
        SET fecha_inicio = ?, fecha_fin = ?, estado = ?, id_usuario = ?, id_habitacion = ?
        WHERE id_reserva = ?
      ');
      $stmt->execute([$fechaInicio, $fechaFin, $estado, $idUsuario, $idHabitacion, $idReserva]);
      $success = 'Reserva actualizada.';
    }
  }

  if ($action === 'eliminar_reserva') {
    $idReserva = (int) ($_POST['id_reserva'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM reserva WHERE id_reserva = ?');
    $stmt->execute([$idReserva]);
    $success = 'Reserva eliminada.';
  }
}

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
  SELECT COUNT(*)
  FROM reserva r
  JOIN usuario u ON u.id_usuario = r.id_usuario
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  $whereSql
");
$stmt->execute($params);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

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
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$reservas = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id_usuario, CONCAT(nombre, " ", apellidos) AS nombre FROM usuario ORDER BY nombre');
$usuarios = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id_habitacion, nombre FROM habitacion ORDER BY nombre');
$habitaciones = $stmt->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$editReserva = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM reserva WHERE id_reserva = ?');
  $stmt->execute([$editId]);
  $editReserva = $stmt->fetch();
}

$active = '';
include __DIR__ . '/includes/header-simple.php';
?>

<main class="container my-5 section-block">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold">Reservas</h1>
      <p class="text-muted mb-0">Listado, edición, inserción y control de estados.</p>
    </div>
    <a href="admin.php" class="btn btn-outline-secondary btn-sm">Volver al panel</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-4">
    <h4 class="fw-bold mb-3"><?php echo $editReserva ? 'Editar reserva' : 'Nueva reserva'; ?></h4>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="<?php echo $editReserva ? 'actualizar_reserva' : 'crear_reserva'; ?>" />
      <?php if ($editReserva): ?>
        <input type="hidden" name="id_reserva" value="<?php echo (int) $editReserva['id_reserva']; ?>" />
      <?php endif; ?>
      <div class="col-md-4">
        <label class="form-label">Usuario</label>
        <select name="id_usuario" class="form-select">
          <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo (int) $u['id_usuario']; ?>" <?php echo ($editReserva && (int) $editReserva['id_usuario'] === (int) $u['id_usuario']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Habitación</label>
        <select name="id_habitacion" class="form-select">
          <?php foreach ($habitaciones as $h): ?>
            <option value="<?php echo (int) $h['id_habitacion']; ?>" <?php echo ($editReserva && (int) $editReserva['id_habitacion'] === (int) $h['id_habitacion']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($h['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Entrada</label>
        <input type="date" class="form-control bairoom-input" name="fecha_inicio" value="<?php echo htmlspecialchars($editReserva['fecha_inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-2">
        <label class="form-label">Salida</label>
        <input type="date" class="form-control bairoom-input" name="fecha_fin" value="<?php echo htmlspecialchars($editReserva['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <?php
          $estadoActual = $editReserva['estado'] ?? 'pendiente';
          ?>
          <option value="pendiente" <?php echo $estadoActual === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
          <option value="aceptada" <?php echo $estadoActual === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
          <option value="rechazada" <?php echo $estadoActual === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
          <option value="cancelada" <?php echo $estadoActual === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
        </select>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-bairoom"><?php echo $editReserva ? 'Guardar cambios' : 'Crear reserva'; ?></button>
        <?php if ($editReserva): ?>
          <a href="admin-reservas.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap">
      <input type="text" name="q" class="form-control" placeholder="Buscar reserva" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" />
      <select name="estado" class="form-select">
        <option value="">Estado</option>
        <option value="pendiente" <?php echo $estadoFilter === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
        <option value="aceptada" <?php echo $estadoFilter === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
        <option value="rechazada" <?php echo $estadoFilter === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
        <option value="cancelada" <?php echo $estadoFilter === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
      </select>
      <select name="pago" class="form-select">
        <option value="">Pago</option>
        <option value="pendiente" <?php echo $pagoFilter === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
        <option value="pagado" <?php echo $pagoFilter === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
        <option value="fallido" <?php echo $pagoFilter === 'fallido' ? 'selected' : ''; ?>>Fallido</option>
        <option value="sin" <?php echo $pagoFilter === 'sin' ? 'selected' : ''; ?>>Sin pago</option>
      </select>
      <button type="submit" class="btn btn-outline-primary">Buscar</button>
    </form>
    <div class="text-muted">Total: <?php echo $totalRows; ?></div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Inquilino</th>
          <th>Habitación</th>
          <th>Entrada</th>
          <th>Salida</th>
          <th>Estado</th>
          <th>Pago</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $r): ?>
          <?php
            $puedeEliminar = ($r['estado'] === 'pendiente' && $r['fecha_inicio'] > date('Y-m-d'));
          ?>
          <tr>
            <td><?php echo htmlspecialchars($r['usuario'] . ' ' . $r['apellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['habitacion'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['fecha_fin'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
              <?php if ($r['estado'] === 'aceptada'): ?>
                <span class="badge bg-success">Aceptada</span>
              <?php elseif ($r['estado'] === 'pendiente'): ?>
                <span class="badge bg-warning text-dark">Pendiente</span>
              <?php elseif ($r['estado'] === 'rechazada'): ?>
                <span class="badge bg-danger">Rechazada</span>
              <?php else: ?>
                <span class="badge bg-secondary">Cancelada</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($r['pago_estado'] ?? '') === 'pagado'): ?>
                <span class="badge bg-success">Pagado</span>
              <?php elseif (($r['pago_estado'] ?? '') === 'pendiente'): ?>
                <span class="badge bg-warning text-dark">Pendiente</span>
              <?php elseif (($r['pago_estado'] ?? '') === 'fallido'): ?>
                <span class="badge bg-danger">Fallido</span>
              <?php else: ?>
                <span class="badge bg-secondary">Sin pago</span>
              <?php endif; ?>
            </td>
            <td class="d-flex gap-2">
              <a href="admin-reservas.php?edit=<?php echo (int) $r['id_reserva']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <?php if ($puedeEliminar): ?>
                <form method="post">
                  <input type="hidden" name="action" value="eliminar_reserva" />
                  <input type="hidden" name="id_reserva" value="<?php echo (int) $r['id_reserva']; ?>" />
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              <?php else: ?>
                <span class="text-muted">Histórico</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$reservas): ?>
          <tr>
            <td colspan="7" class="text-center text-muted">No hay reservas.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>&estado=<?php echo urlencode($estadoFilter); ?>&pago=<?php echo urlencode($pagoFilter); ?>&sort=<?php echo urlencode($sort); ?>&dir=<?php echo urlencode($dir); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
