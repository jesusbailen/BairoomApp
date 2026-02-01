<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

bairoom_require_role('Inquilino');
$user = bairoom_current_user();
$pdo = bairoom_db();

$error = '';
$success = '';
$habitacionId = (int) ($_GET['id'] ?? 0);
if ($habitacionId <= 0) {
  header('Location: inquilino-panel.php');
  exit;
}

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

$stmt = $pdo->prepare('
  SELECT h.*, p.nombre AS propiedad, p.ciudad, p.direccion, p.descripcion,
         (SELECT hi.ruta_imagen FROM habitacion_imagen hi WHERE hi.id_habitacion = h.id_habitacion AND hi.es_principal = 1 LIMIT 1) AS imagen
  FROM habitacion h
  JOIN propiedad p ON p.id_propiedad = h.id_propiedad
  WHERE h.id_habitacion = ?
');
$stmt->execute([$habitacionId]);
$habitacion = $stmt->fetch();
if (!$habitacion) {
  header('Location: inquilino-panel.php');
  exit;
}

$fallbackImages = [
  'img/hab1.png',
  'img/hab2.png',
  'img/hab3.png',
  'img/habsanjuanmar.png',
];
$fallbackImage = $fallbackImages[$habitacionId % count($fallbackImages)];
$habitacionImage = !empty($habitacion['imagen']) ? $habitacion['imagen'] : $fallbackImage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'reservar') {
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?? '';

    if (!$fechaInicio || !$fechaFin) {
      $error = 'Completa los datos de la reserva.';
    } elseif ($fechaInicio > $fechaFin) {
      $error = 'La fecha de salida debe ser posterior a la entrada.';
    } elseif (bairoom_days_diff($fechaInicio, $fechaFin) < 2) {
      $error = 'La reserva debe ser de al menos 3 días.';
    } else {
      $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM reserva r
        WHERE r.id_habitacion = ?
          AND r.estado = "aceptada"
          AND r.fecha_inicio <= ?
          AND r.fecha_fin >= ?
      ');
      $stmt->execute([$habitacionId, $fechaFin, $fechaInicio]);
      $overlap = (int) $stmt->fetchColumn();

      $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM bloqueo_habitacion b
        WHERE b.id_habitacion = ?
          AND b.fecha_inicio <= ?
          AND b.fecha_fin >= ?
      ');
      $stmt->execute([$habitacionId, $fechaFin, $fechaInicio]);
      $blocked = (int) $stmt->fetchColumn();

      if ($overlap > 0 || $blocked > 0) {
        $error = 'La habitación no está disponible en esas fechas.';
      } else {
        $stmt = $pdo->prepare('
          SELECT r.fecha_inicio, r.fecha_fin, h.tipo
          FROM reserva r
          JOIN habitacion h ON h.id_habitacion = r.id_habitacion
          WHERE r.id_usuario = ? AND r.estado IN ("pendiente","aceptada")
        ');
        $stmt->execute([$user['id_usuario']]);
        $prev = $stmt->fetchAll();

        $weeksNew = bairoom_week_keys($fechaInicio, $fechaFin);
        $conflictWeek = false;
        $conflictTipo = false;

        foreach ($prev as $row) {
          $weeksPrev = bairoom_week_keys($row['fecha_inicio'], $row['fecha_fin']);
          if (array_intersect($weeksNew, $weeksPrev)) {
            $conflictWeek = true;
            if ($habitacion['tipo'] && $habitacion['tipo'] === $row['tipo']) {
              $conflictTipo = true;
            }
          }
        }

        if ($conflictWeek) {
          $error = $conflictTipo
            ? 'No puedes reservar otra habitación del mismo tipo en la misma semana.'
            : 'Solo puedes hacer una reserva por semana.';
        } else {
          $stmt = $pdo->prepare('
            INSERT INTO reserva (fecha_inicio, fecha_fin, estado, num_personas, motivo, observaciones, id_usuario, id_habitacion, fecha_creacion)
            VALUES (?, ?, "pendiente", 1, "Reserva web", "", ?, ?, NOW())
          ');
          $stmt->execute([$fechaInicio, $fechaFin, $user['id_usuario'], $habitacionId]);
          header('Location: inquilino-panel.php?reserva=pendiente');
          exit;
        }
      }
    }
  }
}

$stmt = $pdo->prepare('
  SELECT r.fecha_inicio, r.fecha_fin
  FROM reserva r
  WHERE r.estado IN ("aceptada","pendiente") AND r.id_habitacion = ?
');
$stmt->execute([$habitacionId]);
$reservasAceptadas = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT fecha_inicio, fecha_fin
  FROM bloqueo_habitacion
  WHERE id_habitacion = ?
');
$stmt->execute([$habitacionId]);
$bloqueos = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT r.fecha_inicio, r.fecha_fin, h.tipo
  FROM reserva r
  JOIN habitacion h ON h.id_habitacion = r.id_habitacion
  WHERE r.id_usuario = ? AND r.estado IN ("pendiente","aceptada")
');
$stmt->execute([$user['id_usuario']]);
$reservasUsuario = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT r.id_reserva, r.estado, pg.estado AS pago_estado
  FROM reserva r
  LEFT JOIN pago pg ON pg.id_reserva = r.id_reserva
  WHERE r.id_usuario = ? AND r.id_habitacion = ?
  ORDER BY r.fecha_creacion DESC, r.id_reserva DESC
  LIMIT 1
');
$stmt->execute([$user['id_usuario'], $habitacionId]);
$reservaUsuario = $stmt->fetch();

$active = '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($habitacion['nombre'], ENT_QUOTES, 'UTF-8'); ?> · Bairoom</title>

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

  <body class="page-layout owner-panel-body">
    <?php include __DIR__ . '/includes/header-simple.php'; ?>

    <main class="container my-5">
      <section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 owner-panel-hero position-relative">
        <a href="inquilino-panel.php" class="btn btn-outline-secondary btn-sm property-back">Volver al panel</a>
        <div class="text-center">
          <i class="bi bi-house-door text-primary fs-1"></i>
          <h1 class="fw-bold mt-3"><?php echo htmlspecialchars($habitacion['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="text-muted mb-0">
            <?php echo htmlspecialchars($habitacion['propiedad'] . ' · ' . $habitacion['ciudad'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </section>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($reservaUsuario && ($reservaUsuario['estado'] ?? '') === 'aceptada' && ($reservaUsuario['pago_estado'] ?? '') === 'pagado'): ?>
        <div class="alert alert-success">
          Pago confirmado. Tu reserva está activa.
        </div>
      <?php elseif ($reservaUsuario && ($reservaUsuario['estado'] ?? '') === 'aceptada'): ?>
        <div class="alert alert-info">
          Tu reserva ha sido aceptada. Puedes continuar con el pago.
          <a class="fw-semibold" href="pago-stripe.php?reserva=<?php echo (int) $reservaUsuario['id_reserva']; ?>">Ir a pagar</a>
        </div>
      <?php elseif ($reservaUsuario && ($reservaUsuario['estado'] ?? '') === 'pendiente'): ?>
        <div class="alert alert-warning">
          Tu solicitud está pendiente de aprobación por el propietario.
        </div>
      <?php endif; ?>

      <section class="panel-preview mb-5">
        <div class="panel-frame">
          <div class="panel-grid">
            <div class="panel-card panel-wide">
              <div class="room-detail">
                <div class="room-detail-media">
                  <img src="<?php echo htmlspecialchars($habitacionImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($habitacion['nombre'], ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="room-detail-info">
                  <h4 class="fw-bold">Descripción de la habitación</h4>
                  <p class="text-muted">
                    <?php echo htmlspecialchars($habitacion['descripcion'] ?: 'Habitación cómoda y luminosa, ideal para estancias medias y largas.', ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                  <ul class="panel-list">
                    <li><i class="bi bi-tag"></i> Tipo: <?php echo htmlspecialchars($habitacion['tipo'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><i class="bi bi-people"></i> Capacidad: <?php echo (int) $habitacion['capacidad']; ?> plazas</li>
                    <li><i class="bi bi-aspect-ratio"></i> Tamaño: <?php echo number_format((float) $habitacion['m2'], 2); ?> m²</li>
                    <li><i class="bi bi-cash-coin"></i> Precio por noche: <?php echo number_format((float) $habitacion['precio_noche'], 2); ?> €</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="panel-card panel-narrow">
              <h4 class="fw-bold mb-3">Calendario y reserva</h4>
              <p class="text-muted mb-4">Selecciona fechas (mínimo 3 días).</p>

              <form method="post" class="tenant-reserve-form" id="reserveForm">
                <input type="hidden" name="action" value="reservar" />

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Entrada</label>
                    <input type="date" name="fecha_inicio" id="fechaInicio" class="form-control bairoom-input" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Salida</label>
                    <input type="date" name="fecha_fin" id="fechaFin" class="form-control bairoom-input" required />
                  </div>
                </div>

                <div class="tenant-calendar mt-4">
                  <div class="calendar-header">
                    <button type="button" class="btn btn-light btn-sm" id="calPrev"><i class="bi bi-chevron-left"></i></button>
                    <span id="calTitle"></span>
                    <button type="button" class="btn btn-light btn-sm" id="calNext"><i class="bi bi-chevron-right"></i></button>
                  </div>
                  <div class="calendar-grid" id="calendarGrid"></div>
                  <small class="text-muted d-block mt-2">Días marcados en rojo no disponibles.</small>
                </div>

                <button type="submit" class="btn btn-bairoom w-100 mt-4">Solicitar reserva</button>
              </form>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const reservasAceptadas = <?php echo json_encode($reservasAceptadas, JSON_UNESCAPED_UNICODE); ?>;
      const bloqueos = <?php echo json_encode($bloqueos, JSON_UNESCAPED_UNICODE); ?>;
      const reservasUsuario = <?php echo json_encode($reservasUsuario, JSON_UNESCAPED_UNICODE); ?>;
      const habitacionTipo = <?php echo json_encode($habitacion['tipo'], JSON_UNESCAPED_UNICODE); ?>;

      const fechaInicio = document.getElementById('fechaInicio');
      const fechaFin = document.getElementById('fechaFin');
      const calendarGrid = document.getElementById('calendarGrid');
      const calTitle = document.getElementById('calTitle');
      let currentMonth = new Date();

      function isDateBlocked(dateStr, ranges) {
        return ranges.some((r) => dateStr >= r.fecha_inicio && dateStr <= r.fecha_fin);
      }

      function getRange() {
        const start = fechaInicio.value;
        const end = fechaFin.value;
        if (!start || !end || start > end) return null;
        return { start, end };
      }

      function formatDateLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      function renderCalendar() {
        calendarGrid.innerHTML = '';
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const first = new Date(year, month, 1);
        const last = new Date(year, month + 1, 0);
        const ranges = [...reservasAceptadas, ...bloqueos];
        const selected = getRange();
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
          const dateStr = formatDateLocal(date);
          const cell = document.createElement('div');
          cell.className = 'calendar-cell';
          cell.textContent = day;
          if (isDateBlocked(dateStr, ranges)) {
            cell.classList.add('calendar-blocked');
          } else if (selected && dateStr >= selected.start && dateStr <= selected.end) {
            cell.classList.add('calendar-range');
            if (dateStr === selected.start || dateStr === selected.end) {
              cell.classList.add('calendar-selected');
            }
          }
          const todayStr = formatDateLocal(new Date());
          if (dateStr < todayStr) {
            cell.classList.add('calendar-blocked');
          }
          if (!cell.classList.contains('calendar-blocked')) {
            cell.addEventListener('click', () => {
              if (!fechaInicio.value || (fechaInicio.value && fechaFin.value)) {
                fechaInicio.value = dateStr;
                const minEnd = new Date(dateStr);
                minEnd.setDate(minEnd.getDate() + 2);
                fechaFin.value = formatDateLocal(minEnd);
              } else if (dateStr < fechaInicio.value) {
                const startDate = dateStr;
                const minEnd = new Date(startDate);
                minEnd.setDate(minEnd.getDate() + 2);
                fechaInicio.value = startDate;
                fechaFin.value = formatDateLocal(minEnd);
              } else {
                const startDate = fechaInicio.value;
                const endDate = dateStr;
                const minEnd = new Date(startDate);
                minEnd.setDate(minEnd.getDate() + 2);
                const minEndStr = formatDateLocal(minEnd);
                if (endDate < minEndStr) {
                  alert('La reserva debe ser de al menos 3 días.');
                  fechaFin.value = minEndStr;
                } else {
                  fechaFin.value = endDate;
                }
              }
              renderCalendar();
            });
          }
          calendarGrid.appendChild(cell);
        }
      }

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

      document.getElementById('reserveForm').addEventListener('submit', (event) => {
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
        if (diffDays < 2) {
          alert('La reserva debe ser de al menos 3 días.');
          event.preventDefault();
          return;
        }
      const weekNew = weekKeys(fechaInicio.value, fechaFin.value);
        let conflictWeek = false;
        let conflictTipo = false;
        reservasUsuario.forEach((r) => {
          const weekPrev = weekKeys(r.fecha_inicio, r.fecha_fin);
          const overlap = [...weekNew].some((k) => weekPrev.has(k));
          if (overlap) {
            conflictWeek = true;
            if (habitacionTipo && habitacionTipo === r.tipo) {
              conflictTipo = true;
            }
          }
        });
        if (conflictWeek) {
          alert(conflictTipo
            ? 'No puedes reservar otra habitación del mismo tipo en la misma semana.'
            : 'Solo puedes hacer una reserva por semana.');
          event.preventDefault();
        }
      });

      function jumpToDate(dateStr) {
        if (!dateStr) return;
        const date = new Date(dateStr);
        currentMonth = new Date(date.getFullYear(), date.getMonth(), 1);
        renderCalendar();
      }

      fechaInicio.addEventListener('change', () => {
        jumpToDate(fechaInicio.value);
      });

      fechaFin.addEventListener('change', () => {
        jumpToDate(fechaFin.value);
      });

      renderCalendar();
    </script>
  </body>
</html>



