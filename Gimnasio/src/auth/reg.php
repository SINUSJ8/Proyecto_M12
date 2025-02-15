<?php
session_start();
require_once('../includes/header.php');
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
    <main>
        <div class="content-wrapper">
            <!-- Mostrar mensaje de confirmación o error -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div id="mensaje-flotante" class="mensaje-confirmacion" title="Registro exitoso, ahora puedes iniciar sesión.">
                    <p><?php echo htmlspecialchars($_SESSION['mensaje']); ?></p>
                    <a href="log.php" class="btn-general" title="Iniciar sesión ahora.">Iniciar Sesión</a>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div id="mensaje-flotante" class="mensaje-error" title="Se produjo un error en el registro.">
                    <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Contenedor del formulario de registro -->
            <div class="form_container">
                <h2 title="Formulario para registrar una nueva cuenta en el gimnasio.">Registro de Usuario</h2>

                <form action="registro.php" method="POST" onsubmit="return validarFormulario()">
                    <label for="nombre" title="Introduce tu nombre completo.">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required
                        value="<?php echo isset($_SESSION['form_data']['nombre']) ? htmlspecialchars($_SESSION['form_data']['nombre']) : ''; ?>"
                        title="Introduce tu nombre completo aquí.">

                    <label for="email" title="Introduce una dirección de correo válida.">Email:</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>"
                        title="Introduce tu dirección de correo electrónico.">

                    <label for="contrasenya" title="Introduce una contraseña segura de al menos 6 caracteres.">Contraseña:</label>
                    <input type="password" id="contrasenya" name="contrasenya" required title="Introduce una contraseña segura.">

                    <label for="confirmar_contrasenya" title="Vuelve a escribir tu contraseña para confirmarla.">Confirmar Contraseña:</label>
                    <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" required title="Repite la contraseña para confirmar.">

                    <button type="submit" class="btn-general" title="Registrarse en el gimnasio.">Registrarse</button>
                </form>

                <?php unset($_SESSION['form_data']); ?>
            </div>

            <!-- Botón para volver al inicio -->
            <div class="button-container">
                <a href="../../index.php" class="btn-general" title="Volver a la página de inicio.">Volver al inicio</a>
            </div>
        </div>

        <script src="../../assets/js/validacion.js"></script>
    </main>
</body>

<?php include '../includes/footer.php'; ?>

</html>