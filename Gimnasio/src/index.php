<?php if (isset($_GET['mensaje'])): ?>
    <div class="mensaje-confirmacion">
        <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="mensaje-error">
        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
    </div>
<?php endif; ?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Bienvenido</title>
    <link rel="stylesheet" href="/Gimnasio/assets/css/estilos.css">
</head>

<body>
    <!-- Imagen de portada del gimnasio -->
    <div class="image-container">
        <img src="/Gimnasio/assets/imgs/gym.webp" alt="Gimnasio" class="gym-image">
    </div>

    <h2>Bienvenido al Gimnasio</h2>
    <p>Elige una opción para continuar:</p>

    <div class="button_container">
        <!-- Botón para redirigir a la página de registro -->
        <a href="src/reg.php">
            <button>Registrarse</button>
        </a>

        <!-- Botón para redirigir a la página de inicio de sesión -->
        <a href="src/log.php">
            <button>Iniciar Sesión</button>
        </a>
    </div>
</body>

</html>