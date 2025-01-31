<?php
session_start();
require_once('../usuarios/user_functions.php');
include '../usuarios/user_header.php';

$conn = obtenerConexion();

// Verificar si el usuario ha iniciado sesión, de lo contrario redirigir al inicio
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos actuales del usuario
$datos_usuario = obtenerDatosUsuario($conn, $id_usuario);

// Procesar la actualización de datos cuando el formulario se envía (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_telefono = trim($_POST['telefono']);
    $nueva_contrasenya = trim($_POST['contrasenya']);
    $confirmar_contrasenya = trim($_POST['confirmar_contrasenya']);

    // Validaciones del lado del servidor
    if (!preg_match('/[a-zA-Z]/', $nuevo_nombre)) {
        $_SESSION['error'] = "El nombre debe contener al menos una letra.";
        header("Location: usuario.php");
        exit();
    }

    if (!empty($nuevo_telefono) && !preg_match('/^\d{9}$/', $nuevo_telefono)) {
        $_SESSION['error'] = "El teléfono debe contener exactamente 9 dígitos numéricos.";
        header("Location: usuario.php");
        exit();
    }

    if (!empty($nueva_contrasenya)) {
        if (strlen($nueva_contrasenya) < 6) {
            $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
            header("Location: usuario.php");
            exit();
        }

        if ($nueva_contrasenya !== $confirmar_contrasenya) {
            $_SESSION['error'] = "Las contraseñas no coinciden.";
            header("Location: usuario.php");
            exit();
        }
    }

    // Llamada a actualizarDatosUsuario con la página actual como parámetro de redirección
    actualizarDatosUsuario($conn, $id_usuario, $nuevo_nombre, $nuevo_telefono, $nueva_contrasenya ?: null, "usuario.php?mensaje=Datos+actualizados+correctamente");
}

$conn->close();
?>

<main class="form_container">
    <h2 class="section-title">Perfil del Usuario</h2>

    <!-- Mensajes del servidor -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div id="mensaje-flotante" class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div id="mensaje-flotante" class="mensaje-error">
            <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario de perfil del usuario -->
    <form action="usuario.php" method="POST" onsubmit="return valFormUsuario();">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datos_usuario['nombre']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($datos_usuario['email']); ?>" disabled>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($datos_usuario['telefono']); ?>" maxlength="9" pattern="\d{9}" title="Debe contener exactamente 9 dígitos numéricos" autocomplete="off">

        <label for="contrasenya">Contraseña (dejar en blanco para no cambiarla):</label>
        <input type="password" id="contrasenya" name="contrasenya" autocomplete="new-password">

        <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
        <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" autocomplete="new-password">

        <div class="button-container">
            <button type="submit" class="btn-general">Actualizar Datos</button>
        </div>
    </form>
</main>

<?php include '../includes/footer.php'; ?>