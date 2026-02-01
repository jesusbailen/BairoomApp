<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre nosotros</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>


<body class="page-layout">

<?php
$hero_image = "img/sobrenosotros.webp";
$hero_alt = "Sobre nosotros";
$active = "sobrenosotros";
include __DIR__ . "/includes/header-hero.php";
?>            </div><div class="section-divider-bairoom"></div>


        <!-- ======================================================
   SECCIÓN SOBRE NOSOTROS · ESTILO MUPPY
====================================================== -->

        <section class="container sobre-wrapper spacer-xl about-history">

            <!-- Nuestra historia -->
            <h2 class="section-title">Nuestra historia</h2>

            <p class="section-text text-muted mb-3">
                Bairoom nace de la idea de conectar a propietarios e inquilinos a través de un sistema sencillo,
                seguro y sin burocracia. Apostamos por una nueva forma de gestionar habitaciones donde la confianza y la
                eficiencia son lo primero.
            </p>

            <p class="section-text text-muted mb-5">
                Queremos eliminar la incertidumbre del alquiler tradicional y crear una comunidad donde las relaciones
                y procesos sean transparentes, rápidos y digitales.
            </p>




            <div class="row g-4">

                <!-- Misión -->
                <div class="col-md-6">
                    <div class="bg-white card-sobre shadow-effect">
                        <h4><i class="bi bi-send me-2"></i>Misión</h4>
                        <p class="text-muted">
                            Simplificar el proceso de alquiler para todas las partes. Bairoom gestiona,
                            verifica y optimiza cada paso para que alquilar o encontrar habitación sea rápido,
                            seguro y sin esfuerzo.
                        </p>
                    </div>

                </div>

                <!-- Visión -->
                <div class="col-md-6">
                    <div class="bg-white card-sobre shadow-effect">
                        <h4><i class="bi bi-eye me-2"></i>Visión</h4>
                        <p class="text-muted">
                            Convertirnos en la plataforma líder en gestión de habitaciones,
                            aprovechando la tecnología para ofrecer experiencias de vivienda justas,
                            inteligentes y modernas tanto para propietarios como para inquilinos.
                        </p>
                    </div>


                </div>

                <div class="section-divider-bairoom"></div>
                <section class="container equipo-section team-premium-section my-5 py-5">

                    <h2 class="text-center fw-bold titulo-seccion">Nuestro equipo</h2>
                    <p class="text-center subtitulo-seccion mb-5">
                        Bairoom está impulsado por profesionales apasionados por el sector inmobiliario,
                        la tecnología y las nuevas formas de vivir.
                    </p>

                    <div class="row justify-content-center">
                        <div class="col-md-10">
                            <div
                                class="equipo-card team-card-premium shadow-lg p-5 rounded-4 d-flex align-items-center gap-4 flex-column flex-md-row">

                                <img src="img/fundador.jpeg" alt="Fundador Bairoom" class="foto-equipo team-photo-premium rounded-4">

                                <div>
                                    <h4 class="fw-bold mb-2">Jesús Bailén · Fundador</h4>
                                    <p class="mb-0 descripcion-equipo">
                                        Apasionado por conectar personas, optimizar procesos y crear soluciones
                                        eficientes
                                        en el sector del alquiler. Bairoom nace de la visión de transformar la
                                        experiencia
                                        de propietarios e inquilinos mediante transparencia, tecnología y confianza.
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                </section>


        </section>

        <div class="section-divider-bairoom section-divider-tight"></div>


        <section class="container colaboran-section my-5 py-5">

            <h2 class="text-center fw-bold titulo-seccion">Empresas que colaboran con Bairoom</h2>
            <p class="text-center subtitulo-seccion mb-5">
                Aliados estratégicos que nos ayudan a atraer inquilinos profesionales,
                filtrados y verificados de forma eficiente.
            </p>

            <div class="row justify-content-center align-items-center text-center g-4">
                <div class="col-6 col-md-3">
                    <img src="img/ceu.png" class="colab-logo" alt="Empresa colaboradora 1">
                </div>
                <div class="col-6 col-md-3">
                    <img src="img/corteingles.png" class="colab-logo" alt="Empresa colaboradora 2">
                </div>
                <div class="col-6 col-md-3">
                    <img src="img/umh.webp" class="colab-logo" alt="Empresa colaboradora 3">
                </div>
                <div class="col-6 col-md-3">
                    <img src="img/tempe.webp" class="colab-logo" alt="Empresa colaboradora 4">
                </div>
            </div>

        </section>





        <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>







