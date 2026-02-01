<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contacto</title>

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

  <body class="page-layout contact-page">
    <?php
$hero_image = "";
$hero_alt = "Contacto Bairoom";
$hero_class = "hero-bairoom--lightnav hero-bairoom--noimage";
$active = "contacto";
include __DIR__ . "/includes/header-hero.php";
?>


    <!-- ================================
     SECCIÓN CONTACTO
================================ -->
    <section class="contacto-bairoom py-5">
      <div class="container">
        <div class="row justify-content-center">
          <!-- Card contenedora -->
          <div class="col-lg-8">
            <div class="contact-card p-5 contact-card-lower">
              <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                  <span class="contact-badge">Contacta con Bairoom</span>
                  <h2 class="contact-title">Hablemos de tu caso</h2>
                  <p class="contact-subtitle">
                    Cuéntanos en qué podemos ayudarte y te responderemos lo antes
                    posible con una propuesta clara y transparente.
                  </p>

                  <div class="contact-info">
                    <div class="contact-info-item">
                      <i class="bi bi-clock"></i>
                      <span>Respuesta media en 24-48h</span>
                    </div>
                    <div class="contact-info-item">
                      <i class="bi bi-shield-check"></i>
                      <span>Tratamos tus datos con privacidad</span>
                    </div>
                    <div class="contact-info-item">
                      <i class="bi bi-people"></i>
                      <span>Equipo local y soporte cercano</span>
                    </div>
                  </div>

                  <div class="contact-divider"></div>
                  <p class="contact-mini">
                    Preferimos hablar contigo por email para darte una respuesta
                    detallada y rápida.
                  </p>
                </div>

                <div class="col-lg-7">
                  <form class="contact-form">
                    <div class="row g-4">
                      <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input
                          type="text"
                          class="form-control bairoom-input"
                          placeholder="Tu nombre"
                        />
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input
                          type="email"
                          class="form-control bairoom-input"
                          placeholder="tu@email.com"
                        />
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input
                          type="tel"
                          class="form-control bairoom-input"
                          placeholder="+34 600 000 000"
                        />
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Soy</label>
                        <select class="form-select bairoom-input">
                          <option selected>Selecciona una opción</option>
                          <option>Inquilino</option>
                          <option>Propietario</option>
                          <option>Empresa / Partner</option>
                        </select>
                      </div>

                      <div class="col-12">
                        <label class="form-label">Mensaje</label>
                        <textarea
                          rows="5"
                          class="form-control bairoom-input"
                          placeholder="Explícanos brevemente tu caso"
                        ></textarea>
                      </div>
                    </div>

                    <div class="text-center mt-4">
                      <button type="submit" class="btn btn-bairoom px-5 py-3">
                        Enviar mensaje
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
  </body>
</html>



