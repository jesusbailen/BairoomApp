<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

bairoom_require_role('Administrador');
$pdo = bairoom_db();

$error = '';
$success = '';

$propHasEstado = false;
try {
  $stmt = $pdo->query("SHOW COLUMNS FROM propiedad LIKE 'estado'");
  $propHasEstado = (bool) $stmt->fetch();
} catch (Throwable $e) {
  $propHasEstado = false;
}

$owners = $pdo->query('
  SELECT u.id_usuario, CONCAT(u.nombre, " ", u.apellidos) AS nombre
  FROM usuario u
  JOIN rol r ON r.id_rol = u.id_rol
  WHERE r.nombre = "Propietario"
  ORDER BY u.nombre
')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'crear_propiedad') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad = (int) ($_POST['capacidad_total'] ?? 0);
    $propietario = (int) ($_POST['id_propietario'] ?? 0);

    if ($nombre === '' || $direccion === '' || $ciudad === '' || $capacidad <= 0 || $propietario <= 0) {
      $error = 'Completa los datos de la propiedad.';
    } else {
      $stmt = $pdo->prepare('
        INSERT INTO propiedad (nombre, direccion, ciudad, cp, descripcion, capacidad_total, id_propietario)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([$nombre, $direccion, $ciudad, $cp, $descripcion, $capacidad, $propietario]);
      $success = 'Propiedad creada.';
    }
  }

  if ($action === 'actualizar_propiedad') {
    $id = (int) ($_POST['id_propiedad'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad = (int) ($_POST['capacidad_total'] ?? 0);
    $propietario = (int) ($_POST['id_propietario'] ?? 0);

    if ($id <= 0 || $nombre === '' || $direccion === '' || $ciudad === '' || $capacidad <= 0 || $propietario <= 0) {
      $error = 'Completa los datos de la propiedad.';
    } else {
      $stmt = $pdo->prepare('
        UPDATE propiedad
        SET nombre = ?, direccion = ?, ciudad = ?, cp = ?, descripcion = ?, capacidad_total = ?, id_propietario = ?
        WHERE id_propiedad = ?
      ');
      $stmt->execute([$nombre, $direccion, $ciudad, $cp, $descripcion, $capacidad, $propietario, $id]);
      $success = 'Propiedad actualizada.';
    }
  }

  if ($action === 'desactivar_propiedad' && $propHasEstado) {
    $id = (int) ($_POST['id_propiedad'] ?? 0);
    $stmt = $pdo->prepare('UPDATE propiedad SET estado = "inactiva" WHERE id_propiedad = ?');
    $stmt->execute([$id]);
    $success = 'Propiedad desactivada.';
  }

  if ($action === 'activar_propiedad' && $propHasEstado) {
    $id = (int) ($_POST['id_propiedad'] ?? 0);
    $stmt = $pdo->prepare('UPDATE propiedad SET estado = "activa" WHERE id_propiedad = ?');
    $stmt->execute([$id]);
    $success = 'Propiedad activada.';
  }

  if ($action === 'crear_habitacion') {
    $nombre = trim($_POST['nombre'] ?? '');
    $m2 = (float) ($_POST['m2'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? 'individual');
    $capacidad = (int) ($_POST['capacidad'] ?? 1);
    $precio = (float) ($_POST['precio_noche'] ?? 0);
    $estado = trim($_POST['estado'] ?? 'disponible');
    $propiedad = (int) ($_POST['id_propiedad'] ?? 0);

    if ($nombre === '' || $propiedad <= 0 || $precio <= 0) {
      $error = 'Completa los datos de la habitación.';
    } else {
      $stmt = $pdo->prepare('
        INSERT INTO habitacion (nombre, m2, tipo, capacidad, precio_noche, estado, id_propiedad)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([$nombre, $m2 ?: null, $tipo, $capacidad, $precio, $estado, $propiedad]);
      $success = 'Habitación creada.';
    }
  }

  if ($action === 'actualizar_habitacion') {
    $id = (int) ($_POST['id_habitacion'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $m2 = (float) ($_POST['m2'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? 'individual');
    $capacidad = (int) ($_POST['capacidad'] ?? 1);
    $precio = (float) ($_POST['precio_noche'] ?? 0);
    $estado = trim($_POST['estado'] ?? 'disponible');
    $propiedad = (int) ($_POST['id_propiedad'] ?? 0);

    if ($id <= 0 || $nombre === '' || $propiedad <= 0 || $precio <= 0) {
      $error = 'Completa los datos de la habitación.';
    } else {
      $stmt = $pdo->prepare('
        UPDATE habitacion
        SET nombre = ?, m2 = ?, tipo = ?, capacidad = ?, precio_noche = ?, estado = ?, id_propiedad = ?
        WHERE id_habitacion = ?
      ');
      $stmt->execute([$nombre, $m2 ?: null, $tipo, $capacidad, $precio, $estado, $propiedad, $id]);
      $success = 'Habitación actualizada.';
    }
  }

  if ($action === 'desactivar_habitacion') {
    $id = (int) ($_POST['id_habitacion'] ?? 0);
    $stmt = $pdo->prepare('UPDATE habitacion SET estado = "mantenimiento" WHERE id_habitacion = ?');
    $stmt->execute([$id]);
    $success = 'Habitación desactivada.';
  }

  if ($action === 'activar_habitacion') {
    $id = (int) ($_POST['id_habitacion'] ?? 0);
    $stmt = $pdo->prepare('UPDATE habitacion SET estado = "disponible" WHERE id_habitacion = ?');
    $stmt->execute([$id]);
    $success = 'Habitación activada.';
  }
}

$qprop = trim($_GET['qprop'] ?? '');
$estadoProp = trim($_GET['estadoprop'] ?? '');
$pageProp = max(1, (int) ($_GET['pageprop'] ?? 1));
$perPage = 10;
$offsetProp = ($pageProp - 1) * $perPage;

$propWhere = [];
$propParams = [];
if ($qprop !== '') {
  $propWhere[] = '(p.nombre LIKE ? OR p.ciudad LIKE ?)';
  $like = '%' . $qprop . '%';
  $propParams = array_merge($propParams, [$like, $like]);
}
if ($propHasEstado && $estadoProp !== '') {
  $propWhere[] = 'p.estado = ?';
  $propParams[] = $estadoProp;
}
$propWhere = $propWhere ? ('WHERE ' . implode(' AND ', $propWhere)) : '';

$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM propiedad p
  $propWhere
");
$stmt->execute($propParams);
$totalProps = (int) $stmt->fetchColumn();
$totalPropPages = max(1, (int) ceil($totalProps / $perPage));

$stmt = $pdo->prepare("
  SELECT p.*, CONCAT(u.nombre, ' ', u.apellidos) AS propietario
  FROM propiedad p
  JOIN usuario u ON u.id_usuario = p.id_propietario
  $propWhere
  ORDER BY p.id_propiedad DESC
  LIMIT $perPage OFFSET $offsetProp
");
$stmt->execute($propParams);
$propiedades = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id_propiedad, nombre FROM propiedad ORDER BY nombre');
$allPropiedades = $stmt->fetchAll();

$qhab = trim($_GET['qhab'] ?? '');
$estadoHab = trim($_GET['estadohab'] ?? '');
$pageHab = max(1, (int) ($_GET['pagehab'] ?? 1));
$offsetHab = ($pageHab - 1) * $perPage;
$habWhere = [];
$habParams = [];
if ($qhab !== '') {
  $habWhere[] = '(h.nombre LIKE ? OR p.nombre LIKE ?)';
  $like = '%' . $qhab . '%';
  $habParams = array_merge($habParams, [$like, $like]);
}
if ($estadoHab !== '') {
  $habWhere[] = 'h.estado = ?';
  $habParams[] = $estadoHab;
}
$habWhere = $habWhere ? ('WHERE ' . implode(' AND ', $habWhere)) : '';

$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  $habWhere
");
$stmt->execute($habParams);
$totalHabs = (int) $stmt->fetchColumn();
$totalHabPages = max(1, (int) ceil($totalHabs / $perPage));

$stmt = $pdo->prepare("
  SELECT h.*, p.nombre AS propiedad
  FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  $habWhere
  ORDER BY h.id_habitacion DESC
  LIMIT $perPage OFFSET $offsetHab
");
$stmt->execute($habParams);
$habitaciones = $stmt->fetchAll();

$editPropId = (int) ($_GET['edit_prop'] ?? 0);
$editHabId = (int) ($_GET['edit_hab'] ?? 0);
$editProp = null;
$editHab = null;
if ($editPropId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM propiedad WHERE id_propiedad = ?');
  $stmt->execute([$editPropId]);
  $editProp = $stmt->fetch();
}
if ($editHabId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM habitacion WHERE id_habitacion = ?');
  $stmt->execute([$editHabId]);
  $editHab = $stmt->fetch();
}

$active = '';
include __DIR__ . '/includes/header-simple.php';
?>

<main class="container my-5 section-block">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold">Recursos</h1>
      <p class="text-muted mb-0">Propiedades (contenedor) y habitaciones (reservables).</p>
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
    <h4 class="fw-bold mb-3"><?php echo $editProp ? 'Editar propiedad' : 'Nueva propiedad'; ?></h4>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="<?php echo $editProp ? 'actualizar_propiedad' : 'crear_propiedad'; ?>" />
      <?php if ($editProp): ?>
        <input type="hidden" name="id_propiedad" value="<?php echo (int) $editProp['id_propiedad']; ?>" />
      <?php endif; ?>
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" class="form-control bairoom-input" name="nombre" value="<?php echo htmlspecialchars($editProp['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-4">
        <label class="form-label">Dirección</label>
        <input type="text" class="form-control bairoom-input" name="direccion" value="<?php echo htmlspecialchars($editProp['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-4">
        <label class="form-label">Ciudad</label>
        <input type="text" class="form-control bairoom-input" name="ciudad" value="<?php echo htmlspecialchars($editProp['ciudad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">CP</label>
        <input type="text" class="form-control bairoom-input" name="cp" value="<?php echo htmlspecialchars($editProp['cp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">Capacidad total</label>
        <input type="number" min="1" class="form-control bairoom-input" name="capacidad_total" value="<?php echo htmlspecialchars($editProp['capacidad_total'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">Propietario</label>
        <select name="id_propietario" class="form-select">
          <?php foreach ($owners as $owner): ?>
            <option value="<?php echo (int) $owner['id_usuario']; ?>" <?php echo ($editProp && (int) $editProp['id_propietario'] === (int) $owner['id_usuario']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($owner['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-12">
        <label class="form-label">Descripción</label>
        <textarea class="form-control bairoom-input" name="descripcion" rows="2"><?php echo htmlspecialchars($editProp['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-bairoom"><?php echo $editProp ? 'Guardar cambios' : 'Crear propiedad'; ?></button>
        <?php if ($editProp): ?>
          <a href="admin-recursos.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap">
      <input type="text" name="qprop" class="form-control" placeholder="Buscar propiedad" value="<?php echo htmlspecialchars($qprop, ENT_QUOTES, 'UTF-8'); ?>" />
      <?php if ($propHasEstado): ?>
        <select name="estadoprop" class="form-select">
          <option value="">Estado</option>
          <option value="activa" <?php echo $estadoProp === 'activa' ? 'selected' : ''; ?>>Activa</option>
          <option value="inactiva" <?php echo $estadoProp === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
        </select>
      <?php endif; ?>
      <button type="submit" class="btn btn-outline-primary">Buscar</button>
    </form>
    <div class="text-muted">Total: <?php echo $totalProps; ?></div>
  </div>

  <div class="table-responsive mb-5">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Ciudad</th>
          <th>Capacidad</th>
          <th>Propietario</th>
          <?php if ($propHasEstado): ?>
            <th>Estado</th>
          <?php endif; ?>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($propiedades as $prop): ?>
          <tr>
            <td><?php echo htmlspecialchars($prop['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($prop['ciudad'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $prop['capacidad_total']; ?></td>
            <td><?php echo htmlspecialchars($prop['propietario'], ENT_QUOTES, 'UTF-8'); ?></td>
            <?php if ($propHasEstado): ?>
              <td>
                <?php if (($prop['estado'] ?? 'activa') === 'activa'): ?>
                  <span class="badge bg-success">Activa</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactiva</span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
            <td class="d-flex gap-2">
              <a href="admin-recursos.php?edit_prop=<?php echo (int) $prop['id_propiedad']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <?php if ($propHasEstado): ?>
                <?php if (($prop['estado'] ?? 'activa') === 'activa'): ?>
                  <form method="post">
                    <input type="hidden" name="action" value="desactivar_propiedad" />
                    <input type="hidden" name="id_propiedad" value="<?php echo (int) $prop['id_propiedad']; ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">Desactivar</button>
                  </form>
                <?php else: ?>
                  <form method="post">
                    <input type="hidden" name="action" value="activar_propiedad" />
                    <input type="hidden" name="id_propiedad" value="<?php echo (int) $prop['id_propiedad']; ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$propiedades): ?>
          <tr>
            <td colspan="<?php echo $propHasEstado ? '6' : '5'; ?>" class="text-center text-muted">No hay propiedades.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="mb-5">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPropPages; $i++): ?>
        <li class="page-item <?php echo $i === $pageProp ? 'active' : ''; ?>">
          <a class="page-link" href="?pageprop=<?php echo $i; ?>&qprop=<?php echo urlencode($qprop); ?>&estadoprop=<?php echo urlencode($estadoProp); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <div class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-4">
    <h4 class="fw-bold mb-3"><?php echo $editHab ? 'Editar habitación' : 'Nueva habitación'; ?></h4>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="<?php echo $editHab ? 'actualizar_habitacion' : 'crear_habitacion'; ?>" />
      <?php if ($editHab): ?>
        <input type="hidden" name="id_habitacion" value="<?php echo (int) $editHab['id_habitacion']; ?>" />
      <?php endif; ?>
      <div class="col-md-4">
        <label class="form-label">Nombre</label>
        <input type="text" class="form-control bairoom-input" name="nombre" value="<?php echo htmlspecialchars($editHab['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-2">
        <label class="form-label">m²</label>
        <input type="number" step="0.01" class="form-control bairoom-input" name="m2" value="<?php echo htmlspecialchars($editHab['m2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
          <?php
          $tipoActual = $editHab['tipo'] ?? 'individual';
          ?>
          <option value="individual" <?php echo $tipoActual === 'individual' ? 'selected' : ''; ?>>Individual</option>
          <option value="doble" <?php echo $tipoActual === 'doble' ? 'selected' : ''; ?>>Doble</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Capacidad</label>
        <input type="number" min="1" class="form-control bairoom-input" name="capacidad" value="<?php echo htmlspecialchars($editHab['capacidad'] ?? 1, ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-2">
        <label class="form-label">Precio noche</label>
        <input type="number" step="0.01" class="form-control bairoom-input" name="precio_noche" value="<?php echo htmlspecialchars($editHab['precio_noche'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <?php
          $estadoActual = $editHab['estado'] ?? 'disponible';
          ?>
          <option value="disponible" <?php echo $estadoActual === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
          <option value="ocupada" <?php echo $estadoActual === 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
          <option value="mantenimiento" <?php echo $estadoActual === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Propiedad</label>
        <select name="id_propiedad" class="form-select">
          <?php foreach ($allPropiedades as $prop): ?>
            <option value="<?php echo (int) $prop['id_propiedad']; ?>" <?php echo ($editHab && (int) $editHab['id_propiedad'] === (int) $prop['id_propiedad']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($prop['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-bairoom"><?php echo $editHab ? 'Guardar cambios' : 'Crear habitación'; ?></button>
        <?php if ($editHab): ?>
          <a href="admin-recursos.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap">
      <input type="text" name="qhab" class="form-control" placeholder="Buscar habitación" value="<?php echo htmlspecialchars($qhab, ENT_QUOTES, 'UTF-8'); ?>" />
      <select name="estadohab" class="form-select">
        <option value="">Estado</option>
        <option value="disponible" <?php echo $estadoHab === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
        <option value="ocupada" <?php echo $estadoHab === 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
        <option value="mantenimiento" <?php echo $estadoHab === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
      </select>
      <button type="submit" class="btn btn-outline-primary">Buscar</button>
    </form>
    <div class="text-muted">Total: <?php echo $totalHabs; ?></div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Habitación</th>
          <th>Propiedad</th>
          <th>Tipo</th>
          <th>Precio</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($habitaciones as $hab): ?>
          <tr>
            <td><?php echo htmlspecialchars($hab['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($hab['propiedad'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($hab['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $hab['precio_noche'], 2); ?> €</td>
            <td>
              <?php if ($hab['estado'] === 'disponible'): ?>
                <span class="badge bg-success">Disponible</span>
              <?php elseif ($hab['estado'] === 'ocupada'): ?>
                <span class="badge bg-warning text-dark">Ocupada</span>
              <?php else: ?>
                <span class="badge bg-secondary">Mantenimiento</span>
              <?php endif; ?>
            </td>
            <td class="d-flex gap-2">
              <a href="admin-recursos.php?edit_hab=<?php echo (int) $hab['id_habitacion']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <?php if ($hab['estado'] !== 'mantenimiento'): ?>
                <form method="post">
                  <input type="hidden" name="action" value="desactivar_habitacion" />
                  <input type="hidden" name="id_habitacion" value="<?php echo (int) $hab['id_habitacion']; ?>" />
                  <button type="submit" class="btn btn-sm btn-outline-danger">Desactivar</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="action" value="activar_habitacion" />
                  <input type="hidden" name="id_habitacion" value="<?php echo (int) $hab['id_habitacion']; ?>" />
                  <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$habitaciones): ?>
          <tr>
            <td colspan="6" class="text-center text-muted">No hay habitaciones.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalHabPages; $i++): ?>
        <li class="page-item <?php echo $i === $pageHab ? 'active' : ''; ?>">
          <a class="page-link" href="?pagehab=<?php echo $i; ?>&qhab=<?php echo urlencode($qhab); ?>&estadohab=<?php echo urlencode($estadoHab); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
