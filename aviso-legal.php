<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom · Aviso legal</title>

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
    <script src='js/main.js' defer></script>  </head>

  <body class="page-layout legal-page">
    <?php
$active = "";
include __DIR__ . "/includes/header-simple.php";
?>

    <main class="container my-5 section-block legal-main">
      <div class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 legal-card">
        <h1 class="fw-bold mb-3">Aviso legal</h1><h5 class="fw-bold mt-4">Titular del sitio</h5>
<ul>
  <li><strong>Nombre legal:</strong> [Nombre legal de la empresa]</li>
  <li><strong>CIF/NIF:</strong> [CIF/NIF]</li>
  <li><strong>Domicilio:</strong> [Dirección completa]</li>
  <li><strong>Email de contacto:</strong> [email@dominio.com]</li>
  <li><strong>Registro:</strong> [Datos registrales]</li>
</ul>
<h5 class="fw-bold mt-4">Objeto</h5>
<p>Este sitio ofrece información sobre los servicios de Bairoom y canales de contacto.</p>
<h5 class="fw-bold mt-4">Propiedad intelectual</h5>
<p>Los contenidos, marcas y diseños son propiedad de Bairoom o de terceros con licencia.</p>
<h5 class="fw-bold mt-4">Responsabilidad</h5>
<p>Nos esforzamos por mantener la información actualizada, pero no garantizamos la ausencia de errores.</p>
<h5 class="fw-bold mt-4">Legislación aplicable</h5>
<p>Se aplicará la legislación española, salvo disposición en contrario.</p>
      </div>
    </main>

    <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>



