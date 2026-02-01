<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Listado de Habitaciones - Bairoom</title>

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/styles.css" />
    <script src='js/main.js' defer></script>  </head>

  <body class="page-layout">
      <!-- NAVBAR -->
      <nav class="navbar navbar-expand-lg bg-light shadow-sm">
        <div class="container">
          <a class="navbar-brand fw-bold" href="index.php">
            <img src="img/logo_blanco.webp" alt="Bairoom" width="80" />
          </a>

          <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#nav"
          >
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav nav-pill-group ms-auto">
              <li class="nav-item">
                <a class="nav-link nav-pill" href="index.php">Inicio</a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-pill active" href="listado.php">Habitaciones</a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-pill" href="login.php">Iniciar Sesión</a>
              </li>
            </ul>
          </div></div>
    </nav>

      <!-- FILTROS -->
      <div class="container mt-4">
        <h2 class="mb-3">Habitaciones disponibles</h2>

        <form class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Ciudad</label>
            <input type="text" class="form-control" placeholder="Ej: Elche" />
          </div>

          <div class="col-md-3">
            <label class="form-label">Precio máximo (€)</label>
            <input type="number" class="form-control" placeholder="Ej: 400" />
          </div>

          <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select class="form-select">
              <option value="">Cualquiera</option>
              <option>Disponible</option>
              <option>Ocupada</option>
              <option>Mantenimiento</option>
            </select>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filtrar</button>
          </div>
        </form>
      </div>

      <!-- LISTADO DE CARDS -->
      <div class="container">
        <div class="row g-4">
          <!-- CARD 1 -->
          <div class="col-md-4">
            <div class="card shadow-sm">
              <img
                src="img/h1.jpg"
                class="card-img-top"
                style="height: 200px; object-fit: cover"
              />
              <div class="card-body">
                <h5 class="card-title">Centro Elche</h5>
                <p class="card-text">Habitación luminosa · 350€/mes</p>
                <a href="detalle.html" class="btn btn-primary">Ver detalle</a>
              </div>
            </div>
          </div>

          <!-- CARD 2 -->
          <div class="col-md-4">
            <div class="card shadow-sm">
              <img
                src="img/h2.jpg"
                class="card-img-top"
                style="height: 200px; object-fit: cover"
              />
              <div class="card-body">
                <h5 class="card-title">Alicante Centro</h5>
                <p class="card-text">Habitación moderna · 450€/mes</p>
                <a href="detalle.html" class="btn btn-primary">Ver detalle</a>
              </div>
            </div>
          </div>

          <!-- CARD 3 -->
          <div class="col-md-4">
            <div class="card shadow-sm">
              <img
                src="img/h3.jpg"
                class="card-img-top"
                style="height: 200px; object-fit: cover"
              />
              <div class="card-body">
                <h5 class="card-title">Santa Pola Playa</h5>
                <p class="card-text">Vistas al mar · 480€/mes</p>
                <a href="detalle.html" class="btn btn-primary">Ver detalle</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div class="container my-5">
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item disabled">
              <a class="page-link">Anterior</a>
            </li>

            <li class="page-item active">
              <a class="page-link" href="#">1</a>
            </li>

            <li class="page-item">
              <a class="page-link" href="#">2</a>
            </li>

            <li class="page-item">
              <a class="page-link" href="#">3</a>
            </li>

            <li class="page-item">
              <a class="page-link" href="#">Siguiente</a>
            </li>
          </ul>
        </nav>
      </div>

      <!-- FOOTER -->
          <?php include __DIR__ . "/includes/footer.php"; ?>

      <!-- Bootstrap JS -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>








