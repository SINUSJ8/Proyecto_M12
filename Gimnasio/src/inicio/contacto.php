<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Registro e Inicio de Sesión</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <?php include '../Includes/header.php'; ?>
</head>

<body>
    <!-- Contenedor del formulario de registro de usuario -->
    <div class="form_container">
        <h2>Formulario de contacto</h2>
        <form action="formulario.php" method="POST" onsubmit="return validarFormulario()">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" >

            <label for="descripcion">Tu consulta:</label>
            <input type="text" id="descripcion" name="descripcion" required>
                        
            <input type="checkbox" id="condiciones" name="condiciones" required>
            <label for="condiciones">Quiero recibir la Newsletter y acepto los términos y condiciones.</label>
            <br>
            <br>
            <button type="submit" class="btn-general">Enviar</button>
        </form>
    </div>
    <div class="button-container">
        <a href="../../index.php" class="btn-general">Volver al inicio</a>
    </div>
    <script src="../../assets/js/validacion.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>