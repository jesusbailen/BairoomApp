<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bairoom · Política de privacidad</title>

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
        <h1 class="fw-bold mb-3">Política de privacidad</h1>

        <h5 class="fw-bold mt-4">Responsable del tratamiento</h5>
        <p>Bairoom tratará tus datos personales para la gestión de la plataforma y la comunicación con los usuarios.</p>

        <h5 class="fw-bold mt-4">Finalidades</h5>
        <ul>
          <li>Gestión de reservas y contratos.</li>
          <li>Atención al usuario y soporte.</li>
          <li>Comunicaciones relacionadas con el servicio.</li>
        </ul>

        <h5 class="fw-bold mt-4">Base legal</h5>
        <p>El tratamiento se basa en la ejecución del servicio y en el consentimiento del usuario cuando sea necesario.</p>

        <h5 class="fw-bold mt-4">Conservación</h5>
        <p>Los datos se conservarán durante la relación contractual y el tiempo necesario para cumplir obligaciones legales.</p>

        <h5 class="fw-bold mt-4">Derechos</h5>
        <p>Puedes solicitar acceso, rectificación, supresión, oposición o portabilidad escribiendo a [email].</p>
      </div>
    </main>

    <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

