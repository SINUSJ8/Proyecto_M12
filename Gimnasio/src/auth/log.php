<?php
session_start();
require_once('../includes/general.php');
require_once('../includes/header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $contrasenya = $_POST['contrasenya'];
    iniciarSesionUsuario($email, $contrasenya);
}
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

        <!-- Contenedor del formulario -->
        <div class="form_container">
            <h2>Inicio de Sesión</h2>
            <form action="log.php" method="POST">
                <label for="email_login">Email:</label>
                <input type="email" id="email_login" name="email" required
                    value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">

                <label for="contrasenya_login">Contraseña:</label>
                <input type="password" id="contrasenya_login" name="contrasenya" required>

                <button type="submit" class="btn-general">Iniciar Sesión</button>
            </form>
        </div>

        <!-- Botón para volver a la página principal -->
        <div class="button-container">
            <a href="../../index.php" class="button">Volver a la Página Principal</a>
        </div>
    </main>

    <script src="../../assets/js/validacion.js"></script>
</body>
<?php include '../includes/footer.php'; ?>

</html>