<?php
session_start();
require 'db_connection.php'; // Conexión a la base de datos

// Verificar si el usuario ha iniciado sesión, de lo contrario redirigir al inicio
if (!isset($_SESSION['id_usuario'])) {
    header("Location: /Gimnasio/index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos actuales del usuario (nombre, email y teléfono)
$stmt = $conn->prepare("SELECT nombre, email, telefono FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nombre, $email, $telefono);
$stmt->fetch();
$stmt->close();

// Si no hay teléfono registrado, establecer $telefono como vacío para el campo de entrada
if (empty($telefono)) {
    $telefono = '';
}

// Procesar la actualización de datos cuando el formulario se envía (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuevo_nombre = $_POST['nombre']; // Mantener nombre actual si no se cambia
    $nuevo_telefono = $_POST['telefono'];
    $nueva_contrasenya = $_POST['contrasenya'];

    // Validar el teléfono si se proporciona y asegurarse de que tenga exactamente 9 dígitos
    if (!empty($nuevo_telefono) && !preg_match('/^\d{9}$/', $nuevo_telefono)) {
        header("Location: usuario.php?error=El+teléfono+debe+tener+exactamente+9+digitos");
        exit();
    } elseif (empty($nuevo_telefono)) {
        $nuevo_telefono = $telefono; // Mantener el valor actual si el campo está vacío
    }

    // Preparar actualización según si hay nueva contraseña
    if (!empty($nueva_contrasenya)) {
        $password_hash = password_hash($nueva_contrasenya, PASSWORD_DEFAULT); // Encriptar contraseña nueva
        $stmt = $conn->prepare("UPDATE usuario SET nombre = ?, telefono = ?, contrasenya = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssi", $nuevo_nombre, $nuevo_telefono, $password_hash, $id_usuario);
    } else {
        // Actualizar sin cambiar la contraseña si no se ha ingresado nada
        $stmt = $conn->prepare("UPDATE usuario SET nombre = ?, telefono = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $nuevo_nombre, $nuevo_telefono, $id_usuario);
    }

    // Ejecutar la actualización y redirigir con mensaje de éxito si es correcto
    if ($stmt->execute()) {
        header("Location: usuario.php?mensaje=Datos+actualizados+correctamente");
        exit();
    } else {
        echo "Error al actualizar los datos: " . $stmt->error; // Mostrar error en caso de fallo
    }

    $stmt->close(); // Cerrar la declaración preparada
}

$conn->close(); // Cerrar conexión a la base de datos
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Usuario</title>
    <link rel="stylesheet" href="/Gimnasio/assets/css/estilos.css"> <!-- Enlace al archivo CSS para los estilos de la página -->
</head>

<body>
    <h2>Perfil del Usuario</h2>

    <!-- Mostrar mensaje de confirmación si los datos se actualizaron correctamente -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Formulario para que el usuario actualice sus datos personales -->
    <div class="form_container">
        <form action="usuario.php" method="POST" onsubmit="return valFormUsuario();">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" disabled>

            <!-- Campo de teléfono (exactamente 9 dígitos) -->
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" maxlength="9" pattern="\d{9}" title="Debe contener exactamente 9 dígitos numéricos" autocomplete="off">

            <!-- Campo de contraseña opcional para actualizar (dejar en blanco para mantener) -->
            <label for="contrasenya">Contraseña (dejar en blanco para no cambiarla):</label>
            <input type="password" id="contrasenya" name="contrasenya" autocomplete="new-password">

            <!-- Campo para confirmar la nueva contraseña -->
            <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" autocomplete="new-password">

            <!-- Botón de envío del formulario -->
            <button type="submit">Actualizar Datos</button>
        </form>
    </div>
    <div class="form_container">
        <form action="logout.php" method="post">
            <button type="submit">Logout</button>
        </form>
    </div>

    <!-- Enlace al archivo JavaScript para validación de formularios -->
    <script src="../assets/js/validacion.js"></script>
</body>

</html>