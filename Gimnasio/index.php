<?php require_once __DIR__ . '/src/includes/general.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Bienvenido</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estilos.css">
</head>

<body>
    <header>
        <?php require_once __DIR__ . '/src/includes/header.php'; ?>
    </header>
    <main>
        <!-- Mensajes de error o confirmación -->
        <?php if (isset($_GET['error'])): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['mensaje'])): ?>
            <p class="mensaje-confirmacion"><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        <?php endif; ?>

        <!-- Imagen de portada -->
        <div class="image-container">
            <img src="<?php echo BASE_URL; ?>assets/imgs/portada2.jpg" alt="Gimnasio" class="slide-in-left" width="100%">
            <img src="<?php echo BASE_URL; ?>assets/imgs/gym-portada2.jpg" alt="Gimnasio" class="slide-in-right" width="100%">
        </div>
        <br>

        <!-- Slider -->
        <img src="<?php echo BASE_URL; ?>assets/imgs/Bienvenida.png" alt="Gimnasio" class="gym-image" width="70%">
        <div class="slider">
            <!-- Botones de navegación -->
            <input type="radio" name="radio-btn" id="radio1" checked>
            <input type="radio" name="radio-btn" id="radio2">
            <input type="radio" name="radio-btn" id="radio3">
            <input type="radio" name="radio-btn" id="radio4">
            <input type="radio" name="radio-btn" id="radio5">

            <!-- Imágenes -->
            <div class="slides">
                <div class="slide first"><img src="<?php echo BASE_URL; ?>assets/imgs/cardio2.png" alt="Image 1"></div>
                <div class="slide"><img src="<?php echo BASE_URL; ?>assets/imgs/entrenamiento_funcional2.jpg" alt="Image 2"></div>
                <div class="slide"><img src="<?php echo BASE_URL; ?>assets/imgs/pesas.avif" alt="Image 3"></div>
                <div class="slide"><img src="<?php echo BASE_URL; ?>assets/imgs/pilates2.jpg" alt="Image 4"></div>
                <div class="slide"><img src="<?php echo BASE_URL; ?>assets/imgs/yoga2.jpg" alt="Image 5"></div>
            </div>
        </div>
        <script src="<?php echo BASE_URL; ?>assets/js/slider.js"></script>
        <p>¡Únete al equipo!</p>

        <!-- Botones de acción -->
        <div class="button-container">
            <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="btn-general">Registrarse</a>
            <a href="<?php echo BASE_URL; ?>src/auth/log.php" class="btn-general">Iniciar Sesión</a>
        </div>
    </main>
    <img src="<?php echo BASE_URL; ?>assets/imgs/redes.jpg" alt="redes" width="50%">
</body>

<?php include __DIR__ . '/src/includes/footer.php'; ?>

</html>