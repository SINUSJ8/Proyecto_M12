<?php
require_once __DIR__ . '/../includes/general.php';

$title = "Contacto";

// Incluir el header según el rol del usuario
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'miembro') {
    include_once __DIR__ . '/../miembros/miembro_header.php';
} else {
    include_once __DIR__ . '/../includes/header.php';
}

// Inicializar variables para mantener los datos ingresados por el usuario
$nombre = $email = $telefono = $descripcion = "";

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = obtenerConexion(); // Conexión a la base de datos

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $descripcion = trim($_POST['descripcion']);

    // Validar entrada
    if (empty($nombre) || empty($email) || empty($descripcion)) {
        header("Location: contacto.php?error=Por+favor,+rellena+todos+los+campos.&nombre=$nombre&email=$email&telefono=$telefono&descripcion=$descripcion");
        exit();
    }

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contacto.php?error=El+email+no+es+válido.&nombre=$nombre&email=$email&telefono=$telefono&descripcion=$descripcion");
        exit();
    }

    // Validar teléfono (opcional, pero si está presente debe tener 9 dígitos numéricos)
    if (!empty($telefono) && !preg_match('/^\d{9}$/', $telefono)) {
        header("Location: contacto.php?error=El+teléfono+debe+tener+9+dígitos.&nombre=$nombre&email=$email&telefono=$telefono&descripcion=$descripcion");
        exit();
    }

    // Crear el mensaje completo
    $mensaje = "Nuevo mensaje de contacto de $nombre ($email).";
    if (!empty($telefono)) {
        $mensaje .= " Teléfono: $telefono.";
    }
    $mensaje .= " Mensaje: " . $descripcion;

    // Enviar notificación a los administradores
    enviarNotificacionAdmin($conn, $mensaje);

    // Redirigir con mensaje de éxito
    header("Location: contacto.php?success=Mensaje+enviado+correctamente");
    exit();
}

// Función para enviar notificaciones a los administradores
function enviarNotificacionAdmin($conn, $mensaje)
{
    $sql = "SELECT id_usuario FROM usuario WHERE rol = 'admin'";
    $result = $conn->query($sql);

    while ($admin = $result->fetch_assoc()) {
        $id_admin = $admin['id_usuario'];

        // Prepara la consulta
        $stmt = $conn->prepare("INSERT INTO notificacion (id_usuario, mensaje) VALUES (?, ?)");
        $stmt->bind_param("is", $id_admin, $mensaje);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<main class="form_container">
    <h1 class="section-title">Formulario de contacto</h1>
    <p class="intro-text">Envíanos tu consulta y te responderemos lo antes posible.</p>

    <!-- Mostrar mensajes de error -->
    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error" id="mensaje-flotante"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <!-- Mostrar mensaje de éxito -->
    <?php if (isset($_GET['success'])): ?>
        <p class="mensaje-confirmacion" id="mensaje-flotante"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>

    <form action="contacto.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required title="Introduce tu nombre completo"
            value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required title="Introduce tu dirección de correo electrónico"
            value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" title="Introduce tu número de teléfono (9 dígitos opcional)"
            value="<?php echo isset($_GET['telefono']) ? htmlspecialchars($_GET['telefono']) : ''; ?>">

        <div class="notificacion-mensaje">
            <textarea id="descripcion" name="descripcion" placeholder="Tu consulta" required
                title="Escribe tu mensaje o consulta"><?php echo isset($_GET['descripcion']) ? htmlspecialchars($_GET['descripcion']) : ''; ?></textarea>
        </div>

        <div class="checkbox-container">
            <input type="checkbox" id="condiciones" name="condiciones" required title="Debes aceptar los términos y condiciones para enviar el formulario">
            <label for="condiciones">Quiero recibir la Newsletter y acepto los términos y condiciones.</label>
        </div>

        <button type="submit" class="btn-general" title="Haz clic para enviar el formulario">Enviar</button>
    </form>

    <!-- Botón Volver -->
    <div class="button-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="button" title="Volver a la página de inicio">Volver al inicio</a>
    </div>

    <script src="../assets/js/validacion.js"></script>

    <!-- Script para ocultar mensajes de éxito o error después de 3 segundos -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                let mensaje = document.getElementById("mensaje-flotante");
                if (mensaje) {
                    mensaje.style.opacity = "0";
                    setTimeout(() => mensaje.style.display = "none", 500);
                }
            }, 3000);
        });
    </script>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>