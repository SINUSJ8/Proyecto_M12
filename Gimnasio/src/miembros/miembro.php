<?php
$title = "Perfil del Miembro";
include '../miembros/miembro_header.php';
require_once '../includes/general.php'; // Conexión a la base de datos
require_once '../miembros/member_functions.php';

$conn = obtenerConexion(); // Asegurar la conexión

$nombre = $_SESSION['nombre'];
$id_usuario = $_SESSION['id_usuario'];

// Verificar que $id_usuario sea válido
if (!isset($id_usuario) || !is_numeric($id_usuario) || $id_usuario <= 0) {
    echo "<p class='mensaje-error'>Error: No se pudo obtener la información del usuario.</p>";
    exit;
}

// Obtener información del miembro
$miembro = obtenerInformacionMiembro($id_usuario);

if (!$miembro) {
    echo "<p class='mensaje-error'>No se encontró información para este miembro.</p>";
    exit;
}

// Obtener el método de pago guardado
$metodo_pago_guardado = "No registrado";

$stmt = $conn->prepare("
    SELECT metodo 
    FROM metodo_pago_guardado 
    WHERE id_miembro = (SELECT id_miembro FROM miembro WHERE id_usuario = ?)
    LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $metodo_pago_guardado = $row['metodo'];
}
$stmt->close();
?>

<main class="form_container">
    <h1>Información del Miembro</h1>
    <h2 class="section-title">Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>

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
                <th>Teléfono:</th>
                <td><?php echo htmlspecialchars($miembro['telefono'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Fecha de Creación:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_creacion']); ?></td>
            </tr>
            <tr>
                <th>Fecha de Registro como Miembro:</th>
                <td><?php echo htmlspecialchars($miembro['fecha_registro']); ?></td>
            </tr>
            <tr>
                <th>Método de Pago Guardado:</th>
                <td><?php echo htmlspecialchars($metodo_pago_guardado); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="button-container">
        <a href="../perfil/editar_perfil.php" class="btn-general">Editar Perfil</a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>