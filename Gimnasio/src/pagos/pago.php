<?php
$title = "Acceso Pago de membresías";
include '../miembros/miembro_header.php';
require_once '../miembros/member_functions.php';

$nombre = $_SESSION['nombre'];
$id_usuario = $_SESSION['id_usuario'];

// Llama a la función para obtener la información del miembro
$miembro = informacionMembresia($id_usuario);

if (!$miembro) {
    echo "No se encontró información para este miembro.";
    exit;
} else{$_SESSION['mensaje'] = "Pago realizado con éxito.";}

    
?>


<main class="form_container">
    <h1>Pago de la membresía siguiente realizado correctamente, <?php echo htmlspecialchars($nombre); ?>!</h1>
    <h3>Información de tu Membresía</h3>

    <!-- Tabla de información usando solo las clases aplicables -->
    <table>
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
            <td><?php echo htmlspecialchars($miembro['monto_pago']); ?></td>
        </tr>
        <tr>
            <th>Fecha de Pago:</th>
            <td><?php echo htmlspecialchars($miembro['fecha_pago']); ?></td>
        </tr>
        <tr>
            <th>Método de Pago:</th>
            <td><?php echo htmlspecialchars($miembro['metodo_pago']); ?>
        </td>
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
        <tr>
            <div class="button-container">
                <!-- Renovar membresía -->
                <form action="../membresias/mi_membresia.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id_usuario" value="27">
                    <button type="submit" class="btn-general" name="renovar" onclick="return confirm('¿Estás seguro de que deseas renovar la membresía? Esta acción no se puede deshacer.')" title="renovar membresía">Renovar membresía</button>
                </form>
                <!-- Acción de solicitar nueva membresía -->
                <form action="../usuarios/usuario.php" method="GET" style="display:inline;">
                    <input type="hidden" name="id_usuario" value="27">
                    <button type="submit" class="btn-general" name="nueva_membresia" title="Solicitar nueva membresia">Solicitar otra membresia</button>
                </form>
            </div>
        </tr>
    </table>
</main>

<?php include '../includes/footer.php'; ?>