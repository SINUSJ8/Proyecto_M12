<?php
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

// Manejar la inserción, edición o eliminación de especialidades
$mensaje = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['nueva_especialidad'])) {
        $nombre_especialidad = trim($_POST['nueva_especialidad']);
        $mensaje = agregarEspecialidad($conn, $nombre_especialidad);
    } elseif (isset($_POST['editar_especialidad'])) {
        $id_especialidad = $_POST['id_especialidad'];
        $nombre_especialidad = trim($_POST['nombre_especialidad']);
        $mensaje = editarEspecialidad($conn, $id_especialidad, $nombre_especialidad);
    } elseif (isset($_POST['eliminar_especialidad'])) {
        $id_especialidad = $_POST['id_especialidad'];
        $mensaje = eliminarEspecialidad($conn, $id_especialidad);
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

$title = "Administración";
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
    <main class="form_container">
        <h2 class="section-title">Administración de Especialidades</h2>

        <!-- Mensaje de confirmación o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo strpos($mensaje, 'Error') === false ? 'mensaje-confirmacion' : 'mensaje-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para añadir una nueva especialidad -->
        <form method="POST" action="configuracion.php">
            <h3>Añadir Nueva Especialidad</h3>
            <label for="nueva_especialidad">Nombre de la Especialidad:</label>
            <input type="text" id="nueva_especialidad" name="nueva_especialidad" class="input-general" required>
            <button type="submit" class="btn-general">Añadir Especialidad</button>
        </form>

        <!-- Listado de especialidades con opciones de edición y eliminación -->
        <h3>Especialidades Disponibles</h3>
        <ul class="especialidades-lista">
            <?php foreach ($especialidades as $especialidad): ?>
                <li class="especialidad-item">
                    <form method="POST" action="configuracion.php" class="especialidad-form">
                        <input type="hidden" name="id_especialidad" value="<?php echo $especialidad['id_especialidad']; ?>">
                        <input type="text" name="nombre_especialidad" value="<?php echo htmlspecialchars($especialidad['nombre']); ?>" class="input-general" required>
                        <button type="submit" name="editar_especialidad" class="btn-general edit-button">Editar</button>
                        <button type="submit" name="eliminar_especialidad" class="delete-button" onclick="return confirm('¿Estás seguro de que deseas eliminar esta especialidad?')">Eliminar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

    </main>
    <?php
    include '../includes/footer.php';
    $conn->close();
    ?>
</body>

</html>