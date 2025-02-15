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
    } elseif (isset($_POST['editar_membresia'])) {  // <- Aquí se cerró el `if` anterior correctamente
        $id_membresia = $_POST['id_membresia'] ?? 0;
        $tipo = trim($_POST['tipo'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $duracion = intval($_POST['duracion'] ?? 0);
        $beneficios = trim($_POST['beneficios'] ?? '');
        $estado = $_POST['estado'] ?? 'disponible'; // Capturar el estado

        if (!empty($tipo)) {
            $mensaje = editarMembresia($conn, $id_membresia, $tipo, $precio, $duracion, $beneficios, $estado, $entrenamientos_seleccionados);
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
    <main class="form_container form_container_large">
        <h2>Administración de Membresías</h2>

        <!-- Mensaje de confirmación o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="<?= strpos($mensaje, 'Error') === false ? 'mensaje-confirmacion' : 'mensaje-error'; ?>">
                <?= htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para añadir una nueva membresía -->
        <form method="POST" action="crear_membresia.php" class="membresia-form">
            <h3>Añadir Nueva Membresía</h3>

            <div class="membresia-form-item">
                <label for="tipo">Tipo:</label>
                <input type="text" id="tipo" name="tipo" required title="Introduce el tipo de membresía">
            </div>

            <div class="membresia-form-item">
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" required title="Especifica el precio de la membresía">
            </div>

            <div class="membresia-form-item">
                <label for="duracion">Duración (meses):</label>
                <input type="number" id="duracion" name="duracion" required title="Indica la duración en meses">
            </div>

            <div class="membresia-form-item">
                <label for="beneficios">Beneficios:</label>
                <textarea id="beneficios" name="beneficios" rows="5" title="Describe los beneficios de la membresía"></textarea>
            </div>

            <!-- Checkboxes para asignar entrenamientos -->
            <div class="membresia-form-item">
                <label>Entrenamientos Disponibles:</label>
                <div class="checkbox-group">
                    <?php foreach ($entrenamientos as $entrenamiento): ?>
                        <label title="Selecciona si esta membresía incluye este entrenamiento">
                            <input type="checkbox" name="entrenamientos[]" value="<?= $entrenamiento['id_especialidad']; ?>">
                            <?= htmlspecialchars($entrenamiento['nombre']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="button-container">
                <button type="submit" name="nueva_membresia" class="btn-general" title="Añadir una nueva membresía al sistema">
                    Añadir Membresía
                </button>
            </div>
        </form>

        <!-- Listado de membresías con opciones de edición y eliminación -->
        <h3>Membresías Disponibles</h3>
        <ul class="membresias-lista">
            <?php foreach ($membresias as $membresia): ?>
                <li class="membresia-item">
                    <form method="POST" action="crear_membresia.php" class="membresia-form">
                        <input type="hidden" name="id_membresia" value="<?= $membresia['id_membresia']; ?>">

                        <!-- Sección de tipo, precio, duración y estado -->
                        <div class="membresia-section sombreado">
                            <label>Tipo:</label>
                            <input type="text" name="tipo" value="<?= htmlspecialchars($membresia['tipo']); ?>" required title="Edita el tipo de membresía">

                            <label>Precio:</label>
                            <input type="number" name="precio" value="<?= htmlspecialchars($membresia['precio']); ?>" step="0.01" required title="Edita el precio de la membresía">

                            <label>Duración (meses):</label>
                            <input type="number" name="duracion" value="<?= htmlspecialchars($membresia['duracion']); ?>" required title="Edita la duración en meses">

                            <label>Estado:</label>
                            <select name="estado" title="Selecciona si la membresía está disponible o descontinuada">
                                <option value="disponible" <?= ($membresia['estado'] == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                                <option value="descontinuada" <?= ($membresia['estado'] == 'descontinuada') ? 'selected' : ''; ?>>Descontinuada</option>
                            </select>
                        </div>

                        <!-- Beneficios -->
                        <div class="membresia-section sombreado">
                            <label>Beneficios:</label>
                            <textarea name="beneficios" rows="5" title="Edita los beneficios de la membresía"><?= htmlspecialchars($membresia['beneficios']); ?></textarea>
                        </div>

                        <!-- Entrenamientos -->
                        <div class="membresia-section sombreado">
                            <label>Entrenamientos:</label>
                            <ul class="entrenamientos-lista">
                                <?php foreach ($entrenamientos as $entrenamiento): ?>
                                    <li>
                                        <label title="Marca esta opción si la membresía incluye este entrenamiento">
                                            <?= htmlspecialchars($entrenamiento['nombre']); ?>
                                            <input type="checkbox" name="entrenamientos[]" value="<?= $entrenamiento['id_especialidad']; ?>"
                                                <?= in_array($entrenamiento['id_especialidad'], $membresia['entrenamientos']) ? 'checked' : ''; ?>>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Botones -->
                        <div class="membresia-botones">
                            <button type="submit" name="editar_membresia" class="btn-general" title="Guardar cambios en esta membresía">
                                Confirmar cambios
                            </button>

                            <button type="button"
                                class="delete-button <?= ($membresia['id_membresia'] == 1) ? 'btn-disabled' : ''; ?>"
                                data-id="<?= $membresia['id_membresia']; ?>"
                                <?= ($membresia['id_membresia'] == 1) ? 'disabled title="Esta membresía no se puede eliminar."' : 'title="Eliminar esta membresía"'; ?>>
                                Eliminar
                            </button>

                        </div>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>


</body>
<?php include '../includes/footer.php'; ?>
<script src="../../assets/js/alertas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-button").forEach(button => {
            button.addEventListener("click", function() {
                let idMembresia = this.getAttribute("data-id");

                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "Esta acción eliminará la membresía de forma permanente.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear formulario dinámico para enviar la eliminación
                        let form = document.createElement("form");
                        form.method = "POST";
                        form.action = "crear_membresia.php";

                        let inputId = document.createElement("input");
                        inputId.type = "hidden";
                        inputId.name = "id_membresia";
                        inputId.value = idMembresia;

                        let inputSubmit = document.createElement("input");
                        inputSubmit.type = "hidden";
                        inputSubmit.name = "eliminar_membresia";
                        inputSubmit.value = "1";

                        form.appendChild(inputId);
                        form.appendChild(inputSubmit);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>

</html>