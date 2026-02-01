<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Propietarios</title>

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
  </head>

  <body>
    <?php
$hero_image = "";
$hero_alt = "Propietarios Bairoom";
$hero_class = "hero-bairoom--lightnav hero-bairoom--noimage";
$active = "propietarios";
include "includes/header-hero.php";
?>

    <!-- TÍTULO FLOTANTE SOBRE EL FORMULARIO -->
    <section class="container bairoom-owner-hero text-center owner-hero-intro">
      <h1 class="owner-title">
        Solicita un estudio gratuito y descubre cuánto puedes rentabilizar tu vivienda.
      </h1>
    </section>

    <!-- FORMULARIO FLOTANTE (lo reutilizamos como CTA rápido) -->
    <div class="search-floating container">
      <form class="search-box bairoom-soft-card p-4 rounded-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Ciudad</label>
            <input type="text" class="form-control" placeholder="Ej: Elche" />
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Tipo de vivienda</label>
            <select class="form-select">
              <option selected>Selecciona...</option>
              <option>Piso completo</option>
              <option>Habitaciones</option>
              <option>Coliving</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Nº habitaciones</label>
            <input
              type="number"
              class="form-control"
              placeholder="Ej: 3"
              min="1"
            />
          </div>

          <div class="col-md-3 d-grid">
            <a href="contacto.php" class="btn btn-primary btn-bairoom"
              >Solicitar estudio</a
            >
          </div>
        </div>
      </form>
    </div>      </div>
    
    <!-- =========================
         LANDING PROPIETARIOS
    ========================== -->

    <!-- Intro -->
    <section class="container bairoom-owner-hero text-center">
      <h1 class="owner-title">
        Convierte tu vivienda en un activo rentable y sin gestión.
      </h1>
      <p class="owner-subtitle">
        En Bairoom filtramos inquilinos, optimizamos ocupación y te damos un
        panel claro para ver ingresos, rentabilidad por habitación y estado de
        contratos.
      </p>

      <div
        class="owner-cta d-flex flex-column flex-sm-row justify-content-center gap-3"
      >
        <a href="contacto.php" class="btn btn-bairoom px-4"
          >Quiero que me llaméis</a
        >
        <a href="#pasos" class="btn btn-outline-dark px-4 owner-btn-outline"
          >Ver cómo funciona</a
        >
      </div>
    </section>

    <!-- Beneficios (cards premium) -->
    <section class="container my-5">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="owner-card p-4 h-100">
            <div class="owner-card-icon">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <h3 class="owner-card-title">Más rentabilidad</h3>
            <p class="owner-card-text">
              Estrategia de precios por habitación o vivienda, ocupación estable
              y menos vacíos.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="owner-card p-4 h-100">
            <div class="owner-card-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <h3 class="owner-card-title">Inquilinos filtrados</h3>
            <p class="owner-card-text">
              Validación de perfil, encaje con la vivienda y normas claras desde
              el primer día.
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="owner-card p-4 h-100">
            <div class="owner-card-icon"><i class="bi bi-house-check"></i></div>
            <h3 class="owner-card-title">Gestión integral</h3>
            <p class="owner-card-text">
              Contratos, incidencias, pagos y coordinación de limpieza: tú ves
              resultados, no tareas.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Pasos (cómo ser "Admin" de tu vivienda en Bairoom) -->
    <section class="steps-section" id="pasos">
      <h2 class="steps-title">Cómo ser "Admin" de tu vivienda en Bairoom</h2>
      <p class="steps-subtitle">
        4 pasos. Transparente, rápido y con control total desde tu panel.
      </p>

      <div class="steps-list">
        <div class="step step-1">
          <div class="step-number">1</div>
          <div class="step-content">
            <strong>Solicita estudio</strong>
            <p>
              Analizamos ubicación, demanda, precio óptimo y el mejor modelo
              (por habitaciones o completo).
            </p>
          </div>
        </div>

        <div class="step step-2">
          <div class="step-number">2</div>
          <div class="step-content">
            <strong>Alta de vivienda y condiciones</strong>
            <p>
              Definimos reglas, servicios incluidos y calendario de
              disponibilidad para minimizar vacíos.
            </p>
          </div>
        </div>

        <div class="step step-3">
          <div class="step-number">3</div>
          <div class="step-content">
            <strong>Publicación + filtrado</strong>
            <p>
              Captamos demanda y validamos perfiles: documentación, solvencia y
              encaje con convivencia.
            </p>
          </div>
        </div>

        <div class="step step-4">
          <div class="step-number">4</div>
          <div class="step-content">
            <strong>Panel del propietario</strong>
            <p>
              Ves ingresos mensuales, rentabilidad por habitación, contratos
              vigentes y ocupación.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Panel preview (bloque premium) -->
    <section class="container panel-preview">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Vista previa de la App (Propietarios)</h2>
        <p class="text-muted">
          Controla ingresos, ocupación y contratos con trazabilidad total.
        </p>
      </div>

      <div class="panel-frame">
        <div class="panel-grid">
          <div class="panel-card panel-wide panel-card--accent">
            <div class="d-flex justify-content-between align-items-center">
              <h4>Ingresos del mes</h4>
              <span class="panel-badge panel-badge--good"
                ><i class="bi bi-graph-up"></i> +12%</span
              >
            </div>
            <strong>2.430 €</strong>
            <span>Ocupación 94% · 7 habitaciones</span>
            <ul class="panel-list">
              <li><i class="bi bi-calendar2-check"></i> Cobros al día</li>
              <li>
                <i class="bi bi-file-earmark-text"></i> 4 contratos activos
              </li>
              <li><i class="bi bi-tools"></i> 1 incidencia abierta</li>
            </ul>
          </div>
          <div class="panel-card panel-narrow">
            <h4>Próximas salidas</h4>
            <strong>2</strong>
            <span>En los próximos 30 días</span>
          </div>
          <div class="panel-card panel-narrow">
            <h4>Liquidaciones</h4>
            <strong>3</strong>
            <span>Listas para descargar</span>
          </div>
          <div class="panel-card panel-wide">
            <h4>Seguimiento de incidencias</h4>
            <ul class="panel-list">
              <li><i class="bi bi-check-circle-fill"></i> 5 resueltas</li>
              <li><i class="bi bi-clock"></i> 1 en progreso</li>
              <li><i class="bi bi-bell"></i> 2 pendientes de revisión</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Cierre / CTA final -->
    <section class="container text-center my-5">
      <div class="owner-final p-4 p-md-5">
        <h2 class="owner-final-title">
          ¿Tienes una vivienda y quieres rentabilizarla bien?
        </h2>
        <p class="owner-final-text">
          Te decimos la estrategia óptima en tu zona y te damos un plan claro.
        </p>
        <a href="contacto.php" class="btn btn-bairoom px-5"
          >Hablar con Bairoom</a
        >
      </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
  </body>
</html>
