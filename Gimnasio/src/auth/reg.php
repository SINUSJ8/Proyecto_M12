<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Registro e Inicio de Sesión</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <!-- Mostrar mensaje de confirmación o error almacenado en la sesión -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div id="mensaje-flotante" class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_SESSION['mensaje']); ?></p>
            <a href="log.php" class="btn-general">Iniciar Sesión</a>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div id="mensaje-flotante" class="mensaje-error">
            <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Contenedor del formulario de registro de usuario -->
    <div class="form_container">
        <h2>Registro de Usuario</h2>
        <form action="registro.php" method="POST" onsubmit="return validarFormulario()">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required value="<?php echo isset($_SESSION['form_data']['nombre']) ? htmlspecialchars($_SESSION['form_data']['nombre']) : ''; ?>">

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">

            <label for="contrasenya">Contraseña:</label>
            <input type="password" id="contrasenya" name="contrasenya" required>

            <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" required>

            <button type="submit" class="btn-general">Registrarse</button>
        </form>
    </div>

    <!-- Botón para volver al inicio -->
    <div class="button-container">
        <a href="../../index.php" class="btn-general">Volver al inicio</a>
    </div>

    <!-- Enlace a validacion.js -->
    <script src="../../assets/js/validacion.js"></script>
</body>

</html>