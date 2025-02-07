<?php
require_once('../admin/admin_functions.php');
require_once('../clases/class_functions.php'); // Aquí están las funciones necesarias
verificarAdmin();

$conn = obtenerConexion();

// Manejar las acciones de añadir, editar y eliminar especialidades
$mensaje = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['nueva_especialidad'])) {
        // Añadir nueva especialidad
        $nombre_especialidad = trim($_POST['nueva_especialidad']);
        $mensaje = agregarEspecialidad($conn, $nombre_especialidad);
    } elseif (isset($_POST['editar_especialidad'])) {
        // Editar especialidad existente
        $id_especialidad = $_POST['id_especialidad'];
        $nombre_especialidad = trim($_POST['nombre_especialidad']);
        $mensaje = editarEspecialidadConNotificaciones($conn, $id_especialidad, $nombre_especialidad);
    } elseif (isset($_POST['eliminar_especialidad'])) {
        // Eliminar especialidad con notificaciones
        $id_especialidad = $_POST['id_especialidad'];
        $mensaje = eliminarEspecialidadConNotificaciones($conn, $id_especialidad);
    }
}

// Obtener todas las especialidades para mostrar en la página
$especialidades = [];
$result = $conn->query("SELECT * FROM especialidad ORDER BY nombre ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $especialidades[] = $row;
    }
}



$title = "Administración de Especialidades";
include '../admin/admin_header.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Administración de Especialidades</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <main>
        <h2 class="section-title">Administración de Especialidades</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo strpos($mensaje, 'Error') === false ? 'mensaje-confirmacion' : 'mensaje-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="especialidades.php" class="form-container">
            <h3>Añadir Nueva Especialidad</h3>
            <label for="nueva_especialidad">Nombre de la Especialidad:</label>
            <input type="text" id="nueva_especialidad" name="nueva_especialidad" class="input-general" required>
            <button type="submit" class="btn-general">Añadir Especialidad</button>
        </form>

        <h3>Especialidades Disponibles</h3>
        <div class="especialidades-container">
            <?php foreach ($especialidades as $especialidad): ?>
                <div class="especialidad-card">
                    <form method="POST" action="especialidades.php" class="especialidad-form">
                        <input type="hidden" name="id_especialidad" value="<?php echo $especialidad['id_especialidad']; ?>">
                        <input type="text" name="nombre_especialidad" value="<?php echo htmlspecialchars($especialidad['nombre']); ?>" class="input-general" required>
                        <div class="button-group">
                            <button type="submit" name="editar_especialidad" class="btn-general edit-button">Editar</button>
                            <button type="submit" name="eliminar_especialidad" class="delete-button" onclick="return confirm('¿Estás seguro de que deseas eliminar esta especialidad?')">Eliminar</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Esperar 5 segundos y ocultar el mensaje
        setTimeout(function() {
            let mensaje = document.querySelector('.mensaje-confirmacion, .mensaje-error');
            if (mensaje) {
                mensaje.style.transition = "opacity 0.5s ease-out";
                mensaje.style.opacity = "0";
                setTimeout(() => mensaje.remove(), 500); // Eliminar el mensaje después de la animación
            }
        }, 5000);
    </script>

</body>
<?php include '../includes/footer.php'; ?>

</html>