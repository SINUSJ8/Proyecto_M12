<?php
$title = "Acceso Miembros";
include '../miembros/miembro_header.php';
require_once '../miembros/member_functions.php';

$nombre = $_SESSION['nombre'];
$id_usuario = $_SESSION['id_usuario'];

// Llama a la función para obtener la información del miembro
$miembro = informacionMembresia($id_usuario);

if (!$miembro) {
    echo "<p class='mensaje-error'>No se encontró información para este miembro.</p>";
    exit;
}
?>

<main class="form_container">
    <h1 class="section-title">Información de Membresía</h1>
    <h2>Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>
    <h3>Detalles de tu Membresía</h3>

    <!-- Tabla de información -->
    <table class="styled-table">
        <tbody>
            <tr>
                <th>Nombre de Usuario:</th>
                <td><?php echo htmlspecialchars($miembro['nombre_usuario']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($miembro['email']); ?></td>
            </tr>
            <tr>
                <th>Fecha de Registro:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_registro']); ?></td>
            </tr>
            <tr>
                <th>Nombre de la Membresía:</th>
                <td><?php echo htmlspecialchars($miembro['nombre_membresia']); ?></td>
            </tr>
            <tr>
                <th>Fecha de Inicio de Membresía:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_inicio']); ?></td>
            </tr>
            <tr>
                <th>Fecha de Fin de Membresía:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_fin']); ?></td>
            </tr>
            <tr>
                <th>Estado de la Membresía:</th>
                <td><?php echo htmlspecialchars($miembro['estado']); ?></td>
            </tr>
            <tr>
                <th>Renovación Automática:</th>
                <td><?php echo $miembro['renovacion_automatica'] ? 'Sí' : 'No'; ?></td>
            </tr>
            <tr>
                <th>Monto del Pago:</th>
                <td><?php echo htmlspecialchars($miembro['monto_pago']); ?> €</td>
            </tr>
            <tr>
                <th>Fecha de Pago:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_pago']); ?></td>
            </tr>
            <tr>
                <th>Método de Pago:</th>
                <td><?php echo htmlspecialchars($miembro['metodo_pago']); ?></td>
            </tr>
            <tr>
                <th>Entrenamientos/Especialidades:</th>
                <td>
                    <?php
                    if (!empty($miembro['especialidades'])) {
                        echo htmlspecialchars(implode(", ", $miembro['especialidades']));
                    } else {
                        echo "No tiene entrenamientos asignados.";
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Botones de acción -->
    <div class="button-container">
        <!-- Proceder al pago -->
        <form action="../pagos/pago.php" method="POST" style="display:inline;">
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
            <button type="submit" class="btn-general btn-danger" name="pagar" onclick="return confirm('¿Estás seguro de que deseas realizar el pago? Esta acción no se puede deshacer.')" title="Realizar pago">Pagar</button>
        </form>

        <!-- Acción de editar membresía -->
        <form action="crear_membresia.php" method="GET" style="display:inline;">
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
            <button type="submit" class="btn-general edit-button" name="editar_perfil" title="Modificar la membresía de este usuario">Modificar Membresía</button>
        </form>

        <!-- Acción de eliminar membresía -->
        <form action="crear_membresia.php" method="GET" style="display:inline;">
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
            <button type="submit" class="btn-general delete-button" name="eliminar_membresía" title="Eliminar membresía de este usuario">Eliminar Membresía</button>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>