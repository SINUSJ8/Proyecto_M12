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

    <!-- Contenedor del formulario de registro de usuario -->
    <div class="form_container">
        <h2>Registro de Usuario</h2>
        <!-- Formulario de registro con validación de formulario en JavaScript -->
        <form action="registro.php" method="POST" onsubmit="return validarFormulario()">
            <!-- Campo de entrada para el nombre del usuario, requerido -->
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>

            <!-- Campo de entrada para el email del usuario, requerido -->
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <!-- Campo de entrada para la contraseña del usuario, requerido -->
            <label for="contrasenya">Contraseña:</label>
            <input type="password" id="contrasenya" name="contrasenya" required>

            <!-- Campo de entrada para confirmar la contraseña, requerido para validar coincidencia -->
            <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" required>

            <!-- Botón para enviar el formulario de registro -->
            <button type="submit">Registrarse</button>
        </form>
    </div>



    <!-- Enlace al archivo JavaScript para validación de formularios en el registro -->
    <script src="assets/js/validacion.js"></script>
</body>

</html>