<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

bairoom_require_roles(['Administrador', 'Propietario']);
$user = bairoom_current_user();
$pdo = bairoom_db();

$isAdmin = ($user['rol_nombre'] ?? '') === 'Administrador';
$propietarios = [];
if ($isAdmin) {
  $stmt = $pdo->query("SELECT id_usuario, nombre, apellidos FROM usuario WHERE id_rol = 2 ORDER BY nombre");
  $propietarios = $stmt->fetchAll();
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

// Crear o actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'save') {
    $id = (int) ($_POST['id_propiedad'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $capacidad = (int) ($_POST['capacidad_total'] ?? 0);
    $id_propietario = $isAdmin ? (int) ($_POST['id_propietario'] ?? 0) : (int) $user['id_usuario'];

    if ($id > 0) {
      $stmt = $pdo->prepare('
        UPDATE propiedad
        SET nombre = ?, direccion = ?, ciudad = ?, cp = ?, descripcion = ?, capacidad_total = ?, id_propietario = ?
        WHERE id_propiedad = ?
      ');
      $stmt->execute([$nombre, $direccion, $ciudad, $cp, $descripcion, $capacidad, $id_propietario, $id]);
      $propId = $id;
    } else {
      $stmt = $pdo->prepare('
        INSERT INTO propiedad (nombre, direccion, ciudad, cp, descripcion, capacidad_total, id_propietario)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([$nombre, $direccion, $ciudad, $cp, $descripcion, $capacidad, $id_propietario]);
      $propId = (int) $pdo->lastInsertId();
    }

    $imgPath = bairoom_upload_image('imagen', 'propiedades');
    if ($imgPath) {
      $stmt = $pdo->prepare('
        INSERT INTO propiedad_imagen (id_propiedad, ruta_imagen, es_principal)
        VALUES (?, ?, 1)
      ');
      $stmt->execute([$propId, $imgPath]);
    }

    header('Location: propiedades.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int) ($_POST['id_propiedad'] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare('DELETE FROM propiedad WHERE id_propiedad = ?');
      $stmt->execute([$id]);
    }
    header('Location: propiedades.php');
    exit;
  }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM propiedad WHERE id_propiedad = ?');
  $stmt->execute([$editId]);
  $editRow = $stmt->fetch();
}

if ($isAdmin) {
  $stmt = $pdo->query('SELECT p.*, u.nombre AS propietario_nombre, u.apellidos AS propietario_apellidos
    FROM propiedad p
    JOIN usuario u ON u.id_usuario = p.id_propietario
    ORDER BY p.id_propiedad DESC');
  $propiedades = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT * FROM propiedad WHERE id_propietario = ? ORDER BY id_propiedad DESC');
  $stmt->execute([$user['id_usuario']]);
  $propiedades = $stmt->fetchAll();
}

$active = '';
include __DIR__ . '/../includes/header-simple.php';
?>

<main class="container my-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold">Propiedades</h1>
    <p class="text-muted">Gestiona tus viviendas y habitaciones.</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card-sobre p-4">
        <h4 class="fw-bold mb-3"><?php echo $editRow ? 'Editar propiedad' : 'Nueva propiedad'; ?></h4>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save" />
          <input type="hidden" name="id_propiedad" value="<?php echo (int) ($editRow['id_propiedad'] ?? 0); ?>" />

          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control bairoom-input" name="nombre" required value="<?php echo htmlspecialchars($editRow['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <div class="mb-3">
            <label class="form-label">Direcci�n</label>
            <input class="form-control bairoom-input" name="direccion" required value="<?php echo htmlspecialchars($editRow['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Ciudad</label>
              <input class="form-control bairoom-input" name="ciudad" required value="<?php echo htmlspecialchars($editRow['ciudad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="col-md-6">
              <label class="form-label">CP</label>
              <input class="form-control bairoom-input" name="cp" value="<?php echo htmlspecialchars($editRow['cp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Descripci�n</label>
            <textarea class="form-control bairoom-input" name="descripcion" rows="3"><?php echo htmlspecialchars($editRow['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Capacidad total</label>
              <input type="number" min="1" class="form-control bairoom-input" name="capacidad_total" required value="<?php echo htmlspecialchars($editRow['capacidad_total'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <?php if ($isAdmin): ?>
              <div class="col-md-6">
                <label class="form-label">Propietario</label>
                <select class="form-select bairoom-input" name="id_propietario" required>
                  <option value="">Selecciona</option>
                  <?php foreach ($propietarios as $p): ?>
                    <?php $pid = (int) $p['id_usuario']; ?>
                    <option value="<?php echo $pid; ?>" <?php echo ($editRow && (int) $editRow['id_propietario'] === $pid) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Imagen principal (opcional)</label>
            <input type="file" class="form-control" name="imagen" accept="image/*" />
          </div>

          <button type="submit" class="btn btn-bairoom w-100"><?php echo $editRow ? 'Guardar cambios' : 'Crear propiedad'; ?></button>
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
                <th>Ciudad</th>
                <?php if ($isAdmin): ?>
                  <th>Propietario</th>
                <?php endif; ?>
                <th>Capacidad</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($propiedades as $prop): ?>
                <tr>
                  <td><?php echo htmlspecialchars($prop['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($prop['ciudad'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <?php if ($isAdmin): ?>
                    <td><?php echo htmlspecialchars($prop['propietario_nombre'] . ' ' . $prop['propietario_apellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <?php endif; ?>
                  <td><?php echo (int) $prop['capacidad_total']; ?></td>
                  <td class="d-flex gap-2">
                    <a href="propiedades.php?edit=<?php echo (int) $prop['id_propiedad']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                    <a href="habitaciones.php?propiedad=<?php echo (int) $prop['id_propiedad']; ?>" class="btn btn-sm btn-outline-primary">Habitaciones</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id_propiedad" value="<?php echo (int) $prop['id_propiedad']; ?>" />
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('�Eliminar propiedad?')">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$propiedades): ?>
                <tr>
                  <td colspan="<?php echo $isAdmin ? 5 : 4; ?>" class="text-center text-muted">Sin propiedades.</td>
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




