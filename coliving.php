<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom - Inicio</title>

    <!-- Bootstrap -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <!-- Styles -->
    <link rel="stylesheet" href="css/styles.css" />
    <script src='js/main.js' defer></script>  </head>

  
    <?php

$hero_image = "";
$hero_alt = "Coliving Bairoom";
$hero_class = "hero-bairoom--noimage hero-bairoom--lightnav";
$active = "coliving";
include __DIR__ . "/includes/header-hero.php";
?>

    <nav aria-label="breadcrumb" class="visually-hidden">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="index.php">Inicio</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Coliving</li>
      </ol>
    </nav>

    <!-- FORMULARIO FLOTANTE -->
    <div class="search-floating search-floating--lower container">
      <h2 class="steps-title text-center mb-3 search-copy">
        Encuentra la habitación perfecta en segundos.
      </h2>
      <p class="steps-subtitle text-center mb-4" style="margin-bottom: 20px !important;">
        Compara opciones, fechas y entra a vivir sin complicaciones.
      </p>
      <form class="search-box bairoom-soft-card p-4 rounded-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Ciudad</label>
            <input type="text" class="form-control" placeholder="Ej: Elche" />
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Entrada</label>
            <input type="date" class="form-control" />
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Salida</label>
            <input type="date" class="form-control" />
          </div>

          <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary btn-bairoom">
              Buscar habitación
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Aqi va todo el contenido de explicacion del proceso de alquilar y los beneficios, y tambien algo sobre el producto (app web Bairoon)-->
    <section class="steps-section bairoom-soft-card">
      <h2 class="steps-title">
        Solo necesitas 4 pasos para empezar a vivir sin complicaciones.
      </h2>

      <div class="steps-list">
        <div class="step step-1">
          <div class="step-number">1</div>
          <div class="step-content">
            <strong>Elige ciudad y habitación</strong>
            <p>Según tu estilo de vida y fechas disponibles.</p>
          </div>
        </div>

        <div class="step step-2">
          <div class="step-number">2</div>
          <div class="step-content">
            <strong>Envía tu solicitud</strong>
            <p>Proceso online, rápido y sin burocracia.</p>
          </div>
        </div>

        <div class="step step-3">
          <div class="step-number">3</div>
          <div class="step-content">
            <strong>Validamos tu perfil</strong>
            <p>Confirmamos la estancia y el encaje con la vivienda.</p>
          </div>
        </div>

        <div class="step step-4">
          <div class="step-number">4</div>
          <div class="step-content">
            <strong>Entra a vivir</strong>
            <p>Gestiona contratos, pagos y servicios desde tu panel.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="container panel-preview bairoom-soft-card">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Vista previa de la App (Inquilinos)</h2>
        <p class="text-muted">
          Todo lo importante de tu estancia en un solo lugar, con claridad y
          seguimiento.
        </p>
      </div>

      <div class="panel-frame">
        <div class="panel-grid">
          <div class="panel-card panel-wide panel-card--accent">
            <div class="d-flex justify-content-between align-items-center">
              <h4>Reserva activa</h4>
              <span class="panel-badge panel-badge--good">
                <i class="bi bi-shield-check"></i> Verificada
              </span>
            </div>
            <strong>Habitación exterior · Centro</strong>
            <span>Entrada 01/04 · Salida 30/06</span>
            <ul class="panel-list">
              <li><i class="bi bi-file-earmark-text"></i> Contrato firmado</li>
              <li><i class="bi bi-credit-card"></i> Próximo pago: 01/05</li>
              <li><i class="bi bi-tools"></i> Incidencias: 1 en curso</li>
            </ul>
          </div>
          <div class="panel-card panel-narrow">
            <h4>Estado de pagos</h4>
            <strong>OK</strong>
            <span>Último pago registrado</span>
          </div>
          <div class="panel-card panel-narrow">
            <h4>Mensajería</h4>
            <strong>2 nuevos</strong>
            <span>Actualizaciones del equipo</span>
          </div>
          <div class="panel-card panel-wide">
            <h4>Checklist de entrada</h4>
            <ul class="panel-list">
              <li>
                <i class="bi bi-check-circle-fill"></i> Documentación subida
              </li>
              <li><i class="bi bi-check-circle-fill"></i> Fianza confirmada</li>
              <li><i class="bi bi-clock"></i> Visita pendiente</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>


