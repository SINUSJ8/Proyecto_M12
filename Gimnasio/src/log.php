<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Registro e Inicio de Sesión</title>
    <!-- Enlace al archivo CSS para estilos de la página -->
    <link rel="stylesheet" href="/Gimnasio/assets/css/estilos.css">
</head>

<body>
    <!-- Mensaje de confirmación, mostrado si existe en la URL como parámetro 'mensaje' -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Mensaje de error, mostrado si existe en la URL como parámetro 'error' -->
    <?php if (isset($_GET['error'])): ?>
        <div class="mensaje-error">
            <p><?php echo htmlspecialchars($_GET['error']); ?></p>
        </div>
    <?php endif; ?>



    <!-- Contenedor del formulario de inicio de sesión -->
    <div class="form_container">
        <h2>Inicio de Sesión</h2>
        <!-- Formulario de inicio de sesión para usuarios registrados -->
        <form action="login.php" method="POST">
            <!-- Campo de entrada para el email en el inicio de sesión, requerido -->
            <label for="email_login">Email:</label>
            <input type="email" id="email_login" name="email" required>

            <!-- Campo de entrada para la contraseña en el inicio de sesión, requerido -->
            <label for="contrasenya_login">Contraseña:</label>
            <input type="password" id="contrasenya_login" name="contrasenya" required>

            <!-- Botón para enviar el formulario de inicio de sesión -->
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
    <div class="form_container">
        <button onclick="window.location.href='../index.php'">Volver a la Página Principal</button>
    </div>

    <!-- Enlace al archivo JavaScript para validación de formularios en el registro -->
    <script src="../assets/js/validacion.js"></script>
</body>

</html>