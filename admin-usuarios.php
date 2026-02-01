<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

bairoom_require_role('Administrador');
$user = bairoom_current_user();
$pdo = bairoom_db();

$error = '';
$success = '';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$q = trim($_GET['q'] ?? '');
$estadoFilter = trim($_GET['estado'] ?? '');
$rolFilter = (int) ($_GET['rol'] ?? 0);
$sort = $_GET['sort'] ?? 'nombre';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$sortMap = [
  'nombre' => 'u.nombre',
  'email' => 'u.email',
  'rol' => 'r.nombre',
  'estado' => 'u.estado',
  'fecha' => 'u.fecha_alta',
];
$sortSql = $sortMap[$sort] ?? $sortMap['nombre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = (int) ($_POST['id_rol'] ?? 3);

    if ($nombre === '' || $apellidos === '' || $email === '' || $password === '') {
      $error = 'Completa los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
      $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = ?');
      $stmt->execute([$email]);
      if ((int) $stmt->fetchColumn() > 0) {
        $error = 'El email ya está registrado.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('
          INSERT INTO usuario (nombre, apellidos, email, contrasena, telefono, fecha_alta, estado, id_rol)
          VALUES (?, ?, ?, ?, ?, ?, "activo", ?)
        ');
        $stmt->execute([
          $nombre,
          $apellidos,
          $email,
          $hash,
          $telefono !== '' ? $telefono : null,
          date('Y-m-d'),
          $rol,
        ]);
        $success = 'Usuario creado correctamente.';
      }
    }
  }

  if ($action === 'actualizar') {
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = (int) ($_POST['id_rol'] ?? 3);
    $password = $_POST['password'] ?? '';

    if ($idUsuario === (int) $user['id_usuario']) {
      $error = 'No puedes modificar tu propio rol.';
    } elseif ($nombre === '' || $apellidos === '' || $email === '') {
      $error = 'Completa los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'El email no es válido.';
    } else {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = ? AND id_usuario != ?');
      $stmt->execute([$email, $idUsuario]);
      if ((int) $stmt->fetchColumn() > 0) {
        $error = 'El email ya está en uso.';
      } else {
        $stmt = $pdo->prepare('
          UPDATE usuario
          SET nombre = ?, apellidos = ?, email = ?, telefono = ?, id_rol = ?
          WHERE id_usuario = ?
        ');
        $stmt->execute([
          $nombre,
          $apellidos,
          $email,
          $telefono !== '' ? $telefono : null,
          $rol,
          $idUsuario,
        ]);
        if ($password !== '') {
          $hash = password_hash($password, PASSWORD_BCRYPT);
          $stmt = $pdo->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
          $stmt->execute([$hash, $idUsuario]);
        }
        $success = 'Usuario actualizado.';
      }
    }
  }

  if ($action === 'desactivar') {
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    if ($idUsuario === (int) $user['id_usuario']) {
      $error = 'No puedes darte de baja a ti mismo.';
    } else {
      $stmt = $pdo->prepare('UPDATE usuario SET estado = "inactivo" WHERE id_usuario = ?');
      $stmt->execute([$idUsuario]);
      $success = 'Usuario dado de baja.';
    }
  }

  if ($action === 'reactivar') {
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    $stmt = $pdo->prepare('UPDATE usuario SET estado = "activo" WHERE id_usuario = ?');
    $stmt->execute([$idUsuario]);
    $success = 'Usuario reactivado.';
  }
}

$roles = $pdo->query('SELECT id_rol, nombre FROM rol ORDER BY id_rol')->fetchAll();

$whereSql = [];
$params = [];
if ($q !== '') {
  $whereSql[] = '(u.nombre LIKE ? OR u.apellidos LIKE ? OR u.email LIKE ?)';
  $like = '%' . $q . '%';
  $params = array_merge($params, [$like, $like, $like]);
}
if ($estadoFilter !== '') {
  $whereSql[] = 'u.estado = ?';
  $params[] = $estadoFilter;
}
if ($rolFilter > 0) {
  $whereSql[] = 'u.id_rol = ?';
  $params[] = $rolFilter;
}
$whereSql = $whereSql ? ('WHERE ' . implode(' AND ', $whereSql)) : '';

$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM usuario u
  INNER JOIN rol r ON r.id_rol = u.id_rol
  $whereSql
");
$stmt->execute($params);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
  SELECT u.*, r.nombre AS rol_nombre
  FROM usuario u
  INNER JOIN rol r ON r.id_rol = u.id_rol
  $whereSql
  ORDER BY $sortSql $dir
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$editUser = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM usuario WHERE id_usuario = ?');
  $stmt->execute([$editId]);
  $editUser = $stmt->fetch();
}

$active = '';
include __DIR__ . '/includes/header-simple.php';
?>

<main class="container my-5 section-block">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold">Usuarios</h1>
      <p class="text-muted mb-0">Listado, alta, edición, roles y baja lógica.</p>
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
    <h4 class="fw-bold mb-3"><?php echo $editUser ? 'Editar usuario' : 'Nuevo usuario'; ?></h4>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="<?php echo $editUser ? 'actualizar' : 'crear'; ?>" />
      <?php if ($editUser): ?>
        <input type="hidden" name="id_usuario" value="<?php echo (int) $editUser['id_usuario']; ?>" />
      <?php endif; ?>
      <div class="col-md-3">
        <label class="form-label">Nombre</label>
        <input type="text" class="form-control bairoom-input" name="nombre" value="<?php echo htmlspecialchars($editUser['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">Apellidos</label>
        <input type="text" class="form-control bairoom-input" name="apellidos" value="<?php echo htmlspecialchars($editUser['apellidos'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control bairoom-input" name="email" value="<?php echo htmlspecialchars($editUser['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
      </div>
      <div class="col-md-3">
        <label class="form-label">Teléfono</label>
        <input type="text" class="form-control bairoom-input" name="telefono" value="<?php echo htmlspecialchars($editUser['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label"><?php echo $editUser ? 'Nueva contraseña' : 'Contraseña'; ?></label>
        <input type="password" class="form-control bairoom-input" name="password" <?php echo $editUser ? '' : 'required'; ?> />
      </div>
      <div class="col-md-3">
        <label class="form-label">Rol</label>
        <select name="id_rol" class="form-select">
          <?php foreach ($roles as $rol): ?>
            <option value="<?php echo (int) $rol['id_rol']; ?>" <?php echo ($editUser && (int) $editUser['id_rol'] === (int) $rol['id_rol']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-bairoom"><?php echo $editUser ? 'Guardar cambios' : 'Crear usuario'; ?></button>
        <?php if ($editUser): ?>
          <a href="admin-usuarios.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap">
      <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o email" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" />
      <select name="estado" class="form-select">
        <option value="">Estado</option>
        <option value="activo" <?php echo $estadoFilter === 'activo' ? 'selected' : ''; ?>>Activo</option>
        <option value="inactivo" <?php echo $estadoFilter === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
      </select>
      <select name="rol" class="form-select">
        <option value="0">Rol</option>
        <?php foreach ($roles as $rol): ?>
          <option value="<?php echo (int) $rol['id_rol']; ?>" <?php echo $rolFilter === (int) $rol['id_rol'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-primary">Buscar</button>
    </form>
    <div class="text-muted">Total: <?php echo $totalRows; ?></div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th><a href="?sort=nombre&dir=<?php echo $dir === 'asc' ? 'desc' : 'asc'; ?>">Nombre</a></th>
          <th>Email</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Alta</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['rol_nombre'], ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td>
              <?php if ($row['estado'] === 'activo'): ?>
                <span class="badge bg-success">Activo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactivo</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['fecha_alta'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="d-flex gap-2">
              <a href="admin-usuarios.php?edit=<?php echo (int) $row['id_usuario']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
              <?php if ((int) $row['id_usuario'] !== (int) $user['id_usuario']): ?>
                <?php if ($row['estado'] === 'activo'): ?>
                  <form method="post">
                    <input type="hidden" name="action" value="desactivar" />
                    <input type="hidden" name="id_usuario" value="<?php echo (int) $row['id_usuario']; ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">Dar de baja</button>
                  </form>
                <?php else: ?>
                  <form method="post">
                    <input type="hidden" name="action" value="reactivar" />
                    <input type="hidden" name="id_usuario" value="<?php echo (int) $row['id_usuario']; ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-success">Reactivar</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?>
          <tr>
            <td colspan="6" class="text-center text-muted">No hay usuarios.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>&estado=<?php echo urlencode($estadoFilter); ?>&rol=<?php echo urlencode((string) $rolFilter); ?>&sort=<?php echo urlencode($sort); ?>&dir=<?php echo urlencode($dir); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
