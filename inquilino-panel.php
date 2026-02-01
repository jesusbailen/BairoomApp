<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/scripts/cron-finalizar-reservas.php';

bairoom_require_role('Inquilino');
$user = bairoom_current_user();
$pdo = bairoom_db();

$error = '';
$success = '';
$profileError = '';
$profileSuccess = '';
$resetDemoLink = '';

$stmt = $pdo->prepare('SELECT nombre, apellidos, email, telefono, estado FROM usuario WHERE id_usuario = ? LIMIT 1');
$stmt->execute([$user['id_usuario']]);
$userDb = $stmt->fetch() ?: [
  'nombre' => $user['nombre'] ?? '',
  'apellidos' => $user['apellidos'] ?? '',
  'email' => $user['email'] ?? '',
  'telefono' => '',
];

function bairoom_week_keys(string $start, string $end): array
{
  $keys = [];
  $cursor = new DateTime($start);
  $last = new DateTime($end);
  while ($cursor <= $last) {
    $keys[] = $cursor->format('o-W');
    $cursor->modify('+1 day');
  }
  return array_values(array_unique($keys));
}

function bairoom_days_diff(string $start, string $end): int
{
  $d1 = new DateTime($start);
  $d2 = new DateTime($end);
  return (int) $d1->diff($d2)->format('%a');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'cancelar') {
    $idReserva = (int) ($_POST['id_reserva'] ?? 0);
    if ($idReserva > 0) {
      $stmt = $pdo->prepare('DELETE FROM reserva WHERE id_reserva = ? AND id_usuario = ?');
      $stmt->execute([$idReserva, $user['id_usuario']]);
      $success = 'Reserva eliminada.';
    }
  }

  if ($action === 'actualizar_perfil') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '' || $apellidos === '' || $email === '') {
      $profileError = 'Completa nombre, apellidos y email.';
    } elseif (!preg_match('/^[\\p{L}\\s\\-\\\']{2,50}$/u', $nombre)) {
      $profileError = 'El nombre debe tener al menos 2 letras y solo puede incluir letras y espacios.';
    } elseif (!preg_match('/^[\\p{L}\\s\\-\\\']{2,100}$/u', $apellidos)) {
      $profileError = 'Los apellidos deben tener al menos 2 letras y solo pueden incluir letras y espacios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $profileError = 'El email no es válido.';
    } elseif ($telefono !== '' && !preg_match('/^[0-9]{9,15}$/', $telefono)) {
      $profileError = 'El teléfono debe tener entre 9 y 15 dígitos.';
    } else {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = ? AND id_usuario != ?');
      $stmt->execute([$email, $user['id_usuario']]);
      if ((int) $stmt->fetchColumn() > 0) {
        $profileError = 'Ese email ya está en uso.';
      } else {
        $stmt = $pdo->prepare('
          UPDATE usuario
          SET nombre = ?, apellidos = ?, email = ?, telefono = ?
          WHERE id_usuario = ?
        ');
        $stmt->execute([
          $nombre,
          $apellidos,
          $email,
          $telefono !== '' ? $telefono : null,
          $user['id_usuario'],
        ]);
        $user['nombre'] = $nombre;
        $user['apellidos'] = $apellidos;
        $user['email'] = $email;
        bairoom_login_user($user);
        $profileSuccess = 'Perfil actualizado correctamente.';
        $userDb = [
          'nombre' => $nombre,
          'apellidos' => $apellidos,
          'email' => $email,
          'telefono' => $telefono,
        ];
      }
    }
  }

  if ($action === 'cambiar_password') {
    $actual = $_POST['password_actual'] ?? '';
    $nuevo = $_POST['password_nuevo'] ?? '';
    $repite = $_POST['password_repite'] ?? '';

    if ($actual === '' || $nuevo === '' || $repite === '') {
      $profileError = 'Completa todos los campos de contraseña.';
    } elseif ($nuevo !== $repite) {
      $profileError = 'Las contraseñas nuevas no coinciden.';
    } elseif (strlen($nuevo) < 6 || !preg_match('/[A-Za-z]/', $nuevo) || !preg_match('/\\d/', $nuevo)) {
      $profileError = 'La nueva contraseña debe tener al menos 6 caracteres, letras y números.';
    } else {
      $stmt = $pdo->prepare('SELECT contrasena FROM usuario WHERE id_usuario = ?');
      $stmt->execute([$user['id_usuario']]);
      $hashActual = (string) $stmt->fetchColumn();
      if (!password_verify($actual, $hashActual)) {
        $profileError = 'La contraseña actual no es correcta.';
      } else {
        $hashNuevo = password_hash($nuevo, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
        $stmt->execute([$hashNuevo, $user['id_usuario']]);
        $profileSuccess = 'Contraseña actualizada correctamente.';
      }
    }
  }

  if ($action === 'reset_demo') {
    $token = bin2hex(random_bytes(16));
    $tokenHash = password_hash($token, PASSWORD_BCRYPT);
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare('
      INSERT INTO password_reset (id_usuario, token_hash, expiracion)
      VALUES (?, ?, ?)
    ');
    $stmt->execute([$user['id_usuario'], $tokenHash, $expira]);
    $resetDemoLink = 'Token demo: ' . $token;
    $profileSuccess = 'Se ha generado un token de recuperación (modo demo).';
  }

  if ($action === 'baja_logica') {
    $stmt = $pdo->prepare('UPDATE usuario SET estado = "inactivo" WHERE id_usuario = ?');
    $stmt->execute([$user['id_usuario']]);
    bairoom_logout_user();
    header('Location: login.php?desactivada=1');
    exit;
  }
}

$stmt = $pdo->prepare('SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1');
$stmt->execute(['laura@bairoom.com']);
$propietarioDemoId = (int) $stmt->fetchColumn();
if ($propietarioDemoId <= 0) {
  $propietarioDemoId = 2;
}

$stmt = $pdo->prepare('
  SELECT h.id_habitacion, h.nombre, h.tipo, h.capacidad, h.precio_noche, h.estado,
         p.nombre AS propiedad, p.ciudad, p.direccion,
         (SELECT hi.ruta_imagen FROM habitacion_imagen hi WHERE hi.id_habitacion = h.id_habitacion AND hi.es_principal = 1 LIMIT 1) AS imagen,
         (SELECT COUNT(*)
          FROM reserva r
          LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
          WHERE r.id_habitacion = h.id_habitacion
            AND r.estado = "aceptada"
            AND pg.estado = "pagado"
            AND r.fecha_inicio <= CURDATE()
            AND r.fecha_fin >= CURDATE()) AS ocupada_hoy
  FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE p.id_propietario = ? AND h.estado != "mantenimiento"
  ORDER BY h.id_habitacion DESC
');
$stmt->execute([$propietarioDemoId]);
$habitaciones = $stmt->fetchAll();

$fallbackImages = [
  'img/hab1.png',
  'img/hab2.png',
  'img/hab3.png',
  'img/habsanjuanmar.png',
];

$stmt = $pdo->prepare('
  SELECT r.*, h.nombre AS habitacion, h.tipo, p.ciudad, p.nombre AS propiedad,
         pg.estado AS pago_estado
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
  WHERE r.id_usuario = ?
  ORDER BY r.fecha_inicio DESC, pg.id_pago DESC
');
$stmt->execute([$user['id_usuario']]);
$reservas = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT r.fecha_inicio, r.fecha_fin, h.tipo
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  WHERE r.id_usuario = ? AND r.estado IN ("pendiente","aceptada")
');
$stmt->execute([$user['id_usuario']]);
$reservasUsuario = $stmt->fetchAll();

$today = date('Y-m-d');
$stmt = $pdo->prepare('
  SELECT r.id_reserva, r.fecha_inicio, r.fecha_fin,
         h.nombre AS habitacion, h.tipo, h.precio_noche,
         p.nombre AS propiedad, p.ciudad
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE r.id_usuario = ? AND r.estado = "aceptada"
    AND r.fecha_inicio <= ? AND r.fecha_fin >= ?
  ORDER BY r.fecha_inicio DESC
');
$stmt->execute([$user['id_usuario'], $today, $today]);
$estanciasActivas = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT COUNT(*) FROM reserva r
  LEFT JOIN pago p ON p.id_reserva = r.id_reserva
  WHERE r.id_usuario = ? AND r.estado = "aceptada"
    AND (p.estado IS NULL OR p.estado != "pagado")
');
$stmt->execute([$user['id_usuario']]);
$aceptadasCount = (int) $stmt->fetchColumn();

$active = '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel del Inquilino</title>

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
    <link rel="stylesheet" href="css/styles.css" />
    <script src="js/main.js" defer></script>
  </head>

  <body class="page-layout owner-panel-body">
    <?php include __DIR__ . '/includes/header-simple.php'; ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero">
    <div class="text-center">
      <i class="bi bi-house-heart text-primary fs-1"></i>
      <h1 class="fw-bold mt-3">Panel del inquilino</h1>
      <p class="text-muted mb-0">Gestiona reservas, contratos y pagos desde un solo lugar.</p>
    </div>
  </section>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($aceptadasCount > 0): ?>
    <div class="alert alert-info">
      Tienes reservas aceptadas. Puedes continuar con el pago desde tus reservas.
    </div>
  <?php endif; ?>

  <section class="panel-preview mb-5">
    <div class="panel-frame">
      <div class="panel-grid">
        <div class="panel-card panel-wide">
          <h4 class="fw-bold mb-3">Habitaciones disponibles</h4>
          <p class="text-muted mb-4">Selecciona una habitación para ver disponibilidad y reservar.</p>
          <div class="tenant-room-list">
            <?php foreach ($habitaciones as $hab): ?>
              <div class="tenant-room-card" data-room="<?php echo (int) $hab['id_habitacion']; ?>" data-tipo="<?php echo htmlspecialchars($hab['tipo'], ENT_QUOTES, 'UTF-8'); ?>">
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
                    $tipoLabel = ($hab['tipo'] ?? '') === 'doble' ? 'Doble' : 'Individual';
                  ?>
                  <span class="badge bg-light text-dark"><?php echo $tipoLabel; ?></span>
                  <span class="badge bg-light text-dark"><?php echo (int) $hab['capacidad']; ?> plazas</span>
                  <?php if ((int) ($hab['ocupada_hoy'] ?? 0) > 0): ?>
                    <span class="badge bg-light text-danger">Ocupada hoy</span>
                  <?php else: ?>
                    <span class="badge bg-light text-success">Disponible hoy</span>
                  <?php endif; ?>
                </div>
                </div>
              <div class="tenant-room-price">
                <strong><?php echo number_format((float) $hab['precio_noche'], 2); ?> € / noche</strong>
                <a href="habitacion-detalle.php?id=<?php echo (int) $hab['id_habitacion']; ?>" class="btn btn-bairoom btn-sm">Ver más</a>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$habitaciones): ?>
              <p class="text-muted">No hay habitaciones disponibles en este momento.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </section>

  <?php if ($estanciasActivas): ?>
    <section class="panel-preview mb-5">
      <div class="panel-frame">
        <div class="panel-card panel-wide panel-card--accent">
          <div class="d-flex justify-content-between align-items-center">
            <h4>Estancias actuales</h4>
            <span class="panel-badge panel-badge--good">
              <i class="bi bi-house-check"></i> Activas
            </span>
          </div>
          <div class="row g-3 mt-2">
            <?php foreach ($estanciasActivas as $stay): ?>
              <?php
                $inicio = new DateTime($stay['fecha_inicio']);
                $fin = new DateTime($stay['fecha_fin']);
                $hoy = new DateTime($today);
                $totalDias = (int) $inicio->diff($fin)->format('%a');
                $restantes = (int) $hoy->diff($fin)->format('%a');
              ?>
              <div class="col-md-6">
                <div class="panel-card h-100">
                  <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($stay['habitacion'], ENT_QUOTES, 'UTF-8'); ?></h5>
                  <span class="text-muted d-block mb-2">
                    <?php echo htmlspecialchars($stay['propiedad'] . ' · ' . $stay['ciudad'], ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <ul class="panel-list">
                    <li><i class="bi bi-calendar-event"></i> Entrada: <?php echo date('d/m/Y', strtotime($stay['fecha_inicio'])); ?></li>
                    <li><i class="bi bi-calendar-check"></i> Salida: <?php echo date('d/m/Y', strtotime($stay['fecha_fin'])); ?></li>
                    <li><i class="bi bi-hourglass-split"></i> Días restantes: <?php echo max($restantes, 0); ?></li>
                    <li><i class="bi bi-tag"></i> Tipo: <?php echo htmlspecialchars($stay['tipo'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><i class="bi bi-cash-coin"></i> Precio por noche: <?php echo number_format((float) $stay['precio_noche'], 2); ?> €</li>
                  </ul>
                  <span class="text-muted">Duración total: <?php echo max($totalDias, 0); ?> días</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="panel-preview">
    <div class="panel-frame">
      <div class="panel-card">
        <h4 class="fw-bold mb-3">Mis reservas</h4>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Habitación</th>
                <th>Tipo</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservas as $res): ?>
                <tr>
                  <td><?php echo htmlspecialchars($res['habitacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($res['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($res['fecha_inicio'])); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($res['fecha_fin'])); ?></td>
              <td><?php echo htmlspecialchars($res['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="d-flex gap-2">
                <?php if (($res['estado'] ?? '') === 'aceptada' && ($res['pago_estado'] ?? '') !== 'pagado'): ?>
                  <a href="pago-stripe.php?reserva=<?php echo (int) $res['id_reserva']; ?>" class="btn btn-sm btn-success">Pagar</a>
                <?php elseif (($res['pago_estado'] ?? '') === 'pagado'): ?>
                  <span class="badge badge-paid-bairoom">Pagado</span>
                  <a href="docs/reserva.php?id=<?php echo (int) $res['id_reserva']; ?>" class="btn btn-sm btn-outline-primary">Descargar reserva</a>
                <?php endif; ?>
                <?php if (($res['pago_estado'] ?? '') === 'pagado'): ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="alert('Esta reserva ya está pagada y no es reembolsable.');">
                    No reembolsable
                  </button>
                <?php else: ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="cancelar" />
                    <input type="hidden" name="id_reserva" value="<?php echo (int) $res['id_reserva']; ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar reserva?')">Eliminar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
              <?php endforeach; ?>
              <?php if (!$reservas): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">Aún no tienes reservas.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <section class="panel-preview mt-5" id="perfil">
    <div class="panel-frame">
      <div class="panel-card panel-wide">
        <h4 class="fw-bold mb-2">Mi perfil</h4>
        <?php
          $estadoCuenta = $userDb['estado'] ?? 'activo';
        ?>
        <span class="badge badge-outline mb-3">
          <?php echo $estadoCuenta === 'activo' ? 'Cuenta activa' : 'Cuenta inactiva'; ?>
        </span>
        <p class="text-muted mb-4">Gestiona tus datos y seguridad de la cuenta.</p>

        <?php if ($profileError): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($profileSuccess): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($profileSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($resetDemoLink): ?>
          <div class="alert alert-info"><?php echo htmlspecialchars($resetDemoLink, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Datos personales</h5>
            <form method="post" novalidate>
              <input type="hidden" name="action" value="actualizar_perfil" />
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control bairoom-input" name="nombre" value="<?php echo htmlspecialchars($userDb['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Apellidos</label>
                <input type="text" class="form-control bairoom-input" name="apellidos" value="<?php echo htmlspecialchars($userDb['apellidos'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control bairoom-input" name="email" value="<?php echo htmlspecialchars($userDb['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control bairoom-input" name="telefono" value="<?php echo htmlspecialchars($userDb['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </div>
              <button type="submit" class="btn btn-bairoom">Guardar cambios</button>
            </form>
          </div>

          <div class="col-lg-6">
            <h5 class="fw-bold mb-3">Seguridad</h5>
            <form method="post" novalidate>
              <input type="hidden" name="action" value="cambiar_password" />
              <div class="mb-3">
                <label class="form-label">Contraseña actual</label>
                <input type="password" class="form-control bairoom-input" name="password_actual" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control bairoom-input" name="password_nuevo" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Repite la nueva contraseña</label>
                <input type="password" class="form-control bairoom-input" name="password_repite" required />
              </div>
              <button type="submit" class="btn btn-outline-primary">Actualizar contraseña</button>
            </form>

            <form method="post" class="mt-3">
              <input type="hidden" name="action" value="reset_demo" />
              <button type="submit" class="btn btn-light w-100">Generar token de recuperación (demo)</button>
            </form>

            <form method="post" class="mt-3" onsubmit="return confirm('¿Seguro que quieres dar de baja tu cuenta?');">
              <input type="hidden" name="action" value="baja_logica" />
              <button type="submit" class="btn btn-outline-danger w-100">Darme de baja</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
  const habitaciones = <?php echo json_encode($habitaciones, JSON_UNESCAPED_UNICODE); ?>;
  const reservasAceptadas = <?php echo json_encode($reservasAceptadas, JSON_UNESCAPED_UNICODE); ?>;
  const bloqueos = <?php echo json_encode($bloqueos, JSON_UNESCAPED_UNICODE); ?>;
  const reservasUsuario = <?php echo json_encode($reservasUsuario, JSON_UNESCAPED_UNICODE); ?>;

  const reserveRoomId = document.getElementById('reserveRoomId');
  const reserveRoomName = document.getElementById('reserveRoomName');
  const fechaInicio = document.getElementById('fechaInicio');
  const fechaFin = document.getElementById('fechaFin');

  const calendarGrid = document.getElementById('calendarGrid');
  const calTitle = document.getElementById('calTitle');
  let currentMonth = new Date();
  let selectedRoom = null;

  function getRoomById(id) {
    return habitaciones.find((h) => Number(h.id_habitacion) === Number(id));
  }

  function rangesForRoom(id) {
    const ranges = [];
    reservasAceptadas.forEach((r) => {
      if (Number(r.id_habitacion) === Number(id)) {
        ranges.push({ start: r.fecha_inicio, end: r.fecha_fin });
      }
    });
    bloqueos.forEach((b) => {
      if (Number(b.id_habitacion) === Number(id)) {
        ranges.push({ start: b.fecha_inicio, end: b.fecha_fin });
      }
    });
    return ranges;
  }

  function isDateBlocked(dateStr, ranges) {
    return ranges.some((r) => dateStr >= r.start && dateStr <= r.end);
  }

  function renderCalendar() {
    if (!calendarGrid) return;
    calendarGrid.innerHTML = '';
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();
    const first = new Date(year, month, 1);
    const last = new Date(year, month + 1, 0);
    const ranges = selectedRoom ? rangesForRoom(selectedRoom.id_habitacion) : [];
    calTitle.textContent = first.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

    const weekDays = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
    weekDays.forEach((d) => {
      const cell = document.createElement('div');
      cell.className = 'calendar-cell calendar-head';
      cell.textContent = d;
      calendarGrid.appendChild(cell);
    });

    let startOffset = (first.getDay() + 6) % 7;
    for (let i = 0; i < startOffset; i += 1) {
      const cell = document.createElement('div');
      cell.className = 'calendar-cell calendar-empty';
      calendarGrid.appendChild(cell);
    }

    for (let day = 1; day <= last.getDate(); day += 1) {
      const date = new Date(year, month, day);
      const dateStr = date.toISOString().slice(0, 10);
      const cell = document.createElement('div');
      cell.className = 'calendar-cell';
      cell.textContent = day;
      if (selectedRoom && isDateBlocked(dateStr, ranges)) {
        cell.classList.add('calendar-blocked');
      }
      calendarGrid.appendChild(cell);
    }
  }

  document.querySelectorAll('.select-room').forEach((btn) => {
    btn.addEventListener('click', (event) => {
      const card = event.target.closest('.tenant-room-card');
      const roomId = card.dataset.room;
      selectedRoom = getRoomById(roomId);
      reserveRoomId.value = roomId;
      reserveRoomName.value = selectedRoom ? `${selectedRoom.nombre} · ${selectedRoom.ciudad}` : '';
      document.getElementById('calendarEmpty').classList.add('d-none');
      document.getElementById('reserveForm').classList.remove('tenant-reserve-hidden');
      renderCalendar();
    });
  });

  document.getElementById('calPrev').addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
    renderCalendar();
  });

  document.getElementById('calNext').addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
    renderCalendar();
  });

  function weekKeys(start, end) {
    const keys = new Set();
    let cursor = new Date(start);
    const last = new Date(end);
    while (cursor <= last) {
      const year = cursor.getFullYear();
      const firstJan = new Date(year, 0, 1);
      const day = Math.floor((cursor - firstJan) / 86400000);
      const week = Math.ceil((day + firstJan.getDay() + 1) / 7);
      keys.add(`${year}-${week}`);
      cursor.setDate(cursor.getDate() + 1);
    }
    return keys;
  }

  document.querySelector('.tenant-reserve-form').addEventListener('submit', (event) => {
    if (!reserveRoomId.value) {
      alert('Selecciona una habitación.');
      event.preventDefault();
      return;
    }
    if (!fechaInicio.value || !fechaFin.value) {
      alert('Selecciona las fechas.');
      event.preventDefault();
      return;
    }
    if (fechaInicio.value > fechaFin.value) {
      alert('La salida debe ser posterior a la entrada.');
      event.preventDefault();
      return;
    }
    const diffDays = (new Date(fechaFin.value) - new Date(fechaInicio.value)) / 86400000;
    if (diffDays < 3) {
      alert('La reserva debe ser de al menos 3 días.');
      event.preventDefault();
      return;
    }
    const room = getRoomById(reserveRoomId.value);
    const weekNew = weekKeys(fechaInicio.value, fechaFin.value);
    let conflictWeek = false;
    let conflictTipo = false;
    reservasUsuario.forEach((r) => {
      const weekPrev = weekKeys(r.fecha_inicio, r.fecha_fin);
      const overlap = [...weekNew].some((k) => weekPrev.has(k));
      if (overlap) {
        conflictWeek = true;
        if (room && room.tipo === r.tipo) {
          conflictTipo = true;
        }
      }
    });
    if (conflictWeek) {
      alert(conflictTipo
        ? 'No puedes reservar otra habitación del mismo tipo en la misma semana.'
        : 'Solo puedes hacer una reserva por semana.');
      event.preventDefault();
      return;
    }
  });

      renderCalendar();
    </script>
  </body>
</html>

