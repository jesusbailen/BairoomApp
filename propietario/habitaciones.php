<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

bairoom_require_roles(['Administrador', 'Propietario']);
$user = bairoom_current_user();
$pdo = bairoom_db();
$isAdmin = ($user['rol_nombre'] ?? '') === 'Administrador';

$propiedadId = (int) ($_GET['propiedad'] ?? 0);
if ($propiedadId <= 0) {
  header('Location: propiedades.php');
  exit;
}

$stmt = $pdo->prepare('SELECT * FROM propiedad WHERE id_propiedad = ?');
$stmt->execute([$propiedadId]);
$propiedad = $stmt->fetch();
if (!$propiedad) {
  header('Location: propiedades.php');
  exit;
}

if (!$isAdmin && (int) $propiedad['id_propietario'] !== (int) $user['id_usuario']) {
  header('Location: propiedades.php');
  exit;
}

function bairoom_upload_image(string $field, string $subdir): ?string
{
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  $baseDir = __DIR__ . '/../img/uploads/' . $subdir;
  if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
  }
  $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
  $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
  $filename = uniqid('img_', true) . ($safeExt ? '.' . $safeExt : '');
  $targetPath = $baseDir . '/' . $filename;
  if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
    return 'img/uploads/' . $subdir . '/' . $filename;
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'save') {
    $id = (int) ($_POST['id_habitacion'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $m2 = (float) ($_POST['m2'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? 'individual');
    $capacidad = (int) ($_POST['capacidad'] ?? 1);
    $precio = (float) ($_POST['precio_noche'] ?? 0);
    $estado = $_POST['estado'] ?? 'disponible';

    if ($id > 0) {
      $stmt = $pdo->prepare('
        UPDATE habitacion
        SET nombre = ?, m2 = ?, tipo = ?, capacidad = ?, precio_noche = ?, estado = ?
        WHERE id_habitacion = ? AND id_propiedad = ?
      ');
      $stmt->execute([$nombre, $m2, $tipo, $capacidad, $precio, $estado, $id, $propiedadId]);
      $habId = $id;
    } else {
      $stmt = $pdo->prepare('
        INSERT INTO habitacion (nombre, m2, tipo, capacidad, precio_noche, estado, id_propiedad)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([$nombre, $m2, $tipo, $capacidad, $precio, $estado, $propiedadId]);
      $habId = (int) $pdo->lastInsertId();
    }

    $imgPath = bairoom_upload_image('imagen', 'habitaciones');
    if ($imgPath) {
      $stmt = $pdo->prepare('
        INSERT INTO habitacion_imagen (id_habitacion, ruta_imagen, es_principal)
        VALUES (?, ?, 1)
      ');
      $stmt->execute([$habId, $imgPath]);
    }

    header('Location: habitaciones.php?propiedad=' . $propiedadId);
    exit;
  }

  if ($action === 'delete') {
    $id = (int) ($_POST['id_habitacion'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('DELETE FROM habitacion WHERE id_habitacion = ? AND id_propiedad = ?');
      $stmt->execute([$id, $propiedadId]);
    }
    header('Location: habitaciones.php?propiedad=' . $propiedadId);
    exit;
  }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM habitacion WHERE id_habitacion = ? AND id_propiedad = ?');
  $stmt->execute([$editId, $propiedadId]);
  $editRow = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM habitacion WHERE id_propiedad = ? ORDER BY id_habitacion DESC');
$stmt->execute([$propiedadId]);
$habitaciones = $stmt->fetchAll();

$active = '';
include __DIR__ . '/../includes/header-simple.php';
?>

<main class="container my-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold">Habitaciones</h1>
    <p class="text-muted"><?php echo htmlspecialchars($propiedad['nombre'], ENT_QUOTES, 'UTF-8'); ?></p>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card-sobre p-4">
        <h4 class="fw-bold mb-3"><?php echo $editRow ? 'Editar habitación' : 'Nueva habitación'; ?></h4>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save" />
          <input type="hidden" name="id_habitacion" value="<?php echo (int) ($editRow['id_habitacion'] ?? 0); ?>" />

          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control bairoom-input" name="nombre" required value="<?php echo htmlspecialchars($editRow['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">m²</label>
              <input type="number" step="0.01" min="0" class="form-control bairoom-input" name="m2" value="<?php echo htmlspecialchars($editRow['m2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Capacidad</label>
              <input type="number" min="1" class="form-control bairoom-input" name="capacidad" required value="<?php echo htmlspecialchars($editRow['capacidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Tipo</label>
              <?php $tipoActual = $editRow['tipo'] ?? 'individual'; ?>
              <select class="form-select bairoom-input" name="tipo">
                <option value="individual" <?php echo $tipoActual === 'individual' ? 'selected' : ''; ?>>Individual</option>
                <option value="doble" <?php echo $tipoActual === 'doble' ? 'selected' : ''; ?>>Doble</option>
                <option value="suite" <?php echo $tipoActual === 'suite' ? 'selected' : ''; ?>>Suite</option>
                <option value="estudio" <?php echo $tipoActual === 'estudio' ? 'selected' : ''; ?>>Estudio</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Precio por noche</label>
              <input type="number" step="0.01" min="0" class="form-control bairoom-input" name="precio_noche" required value="<?php echo htmlspecialchars($editRow['precio_noche'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select bairoom-input" name="estado">
                <?php $estadoActual = $editRow['estado'] ?? 'disponible'; ?>
                <option value="disponible" <?php echo $estadoActual === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="ocupada" <?php echo $estadoActual === 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
                <option value="mantenimiento" <?php echo $estadoActual === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
              </select>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Imagen principal (opcional)</label>
            <input type="file" class="form-control" name="imagen" accept="image/*" />
          </div>

          <button type="submit" class="btn btn-bairoom w-100"><?php echo $editRow ? 'Guardar cambios' : 'Crear habitación'; ?></button>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card-sobre p-4">
        <h4 class="fw-bold mb-3">Listado</h4>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Capacidad</th>
                <th>Tipo</th>
                <th>Precio por noche</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($habitaciones as $hab): ?>
                <tr>
                  <td><?php echo htmlspecialchars($hab['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo (int) $hab['capacidad']; ?></td>
                  <td><?php echo htmlspecialchars($hab['tipo'] ?? 'individual', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo number_format((float) $hab['precio_noche'], 2); ?> €</td>
                  <td><?php echo htmlspecialchars($hab['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="d-flex gap-2">
                    <a href="habitaciones.php?propiedad=<?php echo $propiedadId; ?>&edit=<?php echo (int) $hab['id_habitacion']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id_habitacion" value="<?php echo (int) $hab['id_habitacion']; ?>" />
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar habitación?')">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$habitaciones): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">Sin habitaciones.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>


