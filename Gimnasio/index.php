<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Bienvenido</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>

<body>
    <header>
    <?php include 'src/Includes/header.php'; ?>
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
            <img src="assets/imgs/gym.webp" alt="Gimnasio" class="gym-image">
        </div>

        <h2 class="section-title">Bienvenido al Gimnasio</h2>
        <p>Elige una opción para continuar:</p>

        <!-- Botones de acción -->
        <div class="button-container">
            <a href="src/auth/reg.php" class="btn-general">Registrarse</a>
            <a href="src/auth/log.php" class="btn-general">Iniciar Sesión</a>
        </div>
    </main>
</body>

<?php include 'src/includes/footer.php'; ?>

</html>