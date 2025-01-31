<?php
require_once('../admin/admin_functions.php');
require_once('../miembros/member_functions.php');

verificarAdmin();
$conn = obtenerConexion();

// Manejar la inserción, edición o eliminación de membresías
$mensaje = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $entrenamientos_seleccionados = $_POST['entrenamientos'] ?? [];

    if (isset($_POST['nueva_membresia'])) {
        $tipo = trim($_POST['tipo'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $duracion = intval($_POST['duracion'] ?? 0);
        $beneficios = trim($_POST['beneficios'] ?? '');
        if (!empty($tipo)) {
            $mensaje = agregarMembresia($conn, $tipo, $precio, $duracion, $beneficios, $entrenamientos_seleccionados);
        } else {
            $mensaje = "Error: El campo 'tipo' es obligatorio.";
        }
    } elseif (isset($_POST['editar_membresia'])) {
        $id_membresia = $_POST['id_membresia'] ?? 0;
        $tipo = trim($_POST['tipo'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $duracion = intval($_POST['duracion'] ?? 0);
        $beneficios = trim($_POST['beneficios'] ?? '');
        if (!empty($tipo)) {
            $mensaje = editarMembresia($conn, $id_membresia, $tipo, $precio, $duracion, $beneficios, $entrenamientos_seleccionados);
        } else {
            $mensaje = "Error: El campo 'tipo' es obligatorio.";
        }
    } elseif (isset($_POST['eliminar_membresia'])) {
        $id_membresia = $_POST['id_membresia'] ?? 0;
        $mensaje = eliminarMembresia($conn, $id_membresia);
    }
}

// Obtener todas las membresías y entrenamientos para mostrar en la página
$membresias = [];
$result = $conn->query("SELECT * FROM membresia ORDER BY tipo ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['tipo'])) { // Verifica que el tipo no esté vacío
            $id_membresia = $row['id_membresia'];
            $row['entrenamientos'] = [];

            // Cargar entrenamientos asociados a esta membresía
            $stmt = $conn->prepare("SELECT id_entrenamiento FROM membresia_entrenamiento WHERE id_membresia = ?");
            $stmt->bind_param("i", $id_membresia);
            $stmt->execute();
            $entrenamientos_result = $stmt->get_result();

            while ($entrenamiento = $entrenamientos_result->fetch_assoc()) {
                $row['entrenamientos'][] = $entrenamiento['id_entrenamiento'];
            }
            $stmt->close();

            $membresias[] = $row;
        }
    }
}
$entrenamientos = obtenerEntrenamientos($conn); // Obtener entrenamientos disponibles
$title = "Crear Membresía";
include '../admin/admin_header.php';
?>

<!DOCTYPE html>
<html lang="es">

<body>
    <main>





        <h2>Membresías Disponibles</h2>
        <ul class="membresias-lista">
            <?php foreach ($membresias as $membresia): ?>
                <li class="membresia-item">
                    <div class="membresia-section sombreado">
                        <label>Tipo:</label>
                        <span class="membresia-tipo"><?php echo htmlspecialchars($membresia['tipo']); ?></span>
                    </div>

                    <div class="membresia-section sombreado">
                        <label>Precio:</label>
                        <span><?php echo htmlspecialchars($membresia['precio']); ?> €</span>
                    </div>

                    <div class="membresia-section sombreado">
                        <label>Duración (meses):</label>
                        <span><?php echo htmlspecialchars($membresia['duracion']); ?></span>
                    </div>

                    <div class="membresia-section sombreado">
                        <label>Estado:</label>
                        <span><?php echo htmlspecialchars($membresia['estado']); ?></span>
                    </div>

                    <div class="membresia-section sombreado">
                        <label>Beneficios:</label>
                        <p><?php echo nl2br(htmlspecialchars($membresia['beneficios'])); ?></p>
                    </div>

                    <div class="membresia-section sombreado">
                        <label>Entrenamientos:</label>
                        <ul class="entrenamientos-lista">
                            <?php foreach ($entrenamientos as $entrenamiento): ?>
                                <?php if (in_array($entrenamiento['id_especialidad'], $membresia['entrenamientos'])): ?>
                                    <li><?php echo htmlspecialchars($entrenamiento['nombre']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>

</html>