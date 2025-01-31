<?php
$title = "Información de Membresía";
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

// Mensaje de confirmación
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $renovacion = isset($_POST['renovacion_automatica']) ? (int)$_POST['renovacion_automatica'] : null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;

    $resultado = actualizarPreferenciasMembresia($id_usuario, $renovacion, $metodo_pago);

    if ($resultado === "Preferencias actualizadas exitosamente.") {
        header("Location: mi_membresia.php?mensaje=" . urlencode($resultado));
        exit();
    } else {
        $mensaje = $resultado; // Mostrar mensaje de error directamente
    }
}


// Mostrar mensaje si está presente en la URL
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
}

?>

<main class="form_container">
    <h1 class="section-title">Información de Membresía</h1>
    <h2>Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>

    <?php if ($mensaje): ?>
        <p class="mensaje-confirmacion"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h3>Detalles de tu Membresía</h3>
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

    <h3>Editar Preferencias</h3>
    <form method="POST" class="form-edit">
        <label for="renovacion_automatica">Renovación Automática:</label>
        <select name="renovacion_automatica" id="renovacion_automatica">
            <option value="1" <?php echo $miembro['renovacion_automatica'] ? 'selected' : ''; ?>>Sí</option>
            <option value="0" <?php echo !$miembro['renovacion_automatica'] ? 'selected' : ''; ?>>No</option>
        </select>

        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" id="metodo_pago">
            <option value="tarjeta" <?php echo $miembro['metodo_pago'] === 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
            <option value="google_pay" <?php echo $miembro['metodo_pago'] === 'google_pay' ? 'selected' : ''; ?>>Google pay</option>
            <option value="transferencia" <?php echo $miembro['metodo_pago'] === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
            <option value="paypal" <?php echo $miembro['metodo_pago'] === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
            <option value="bizum" <?php echo $miembro['metodo_pago'] === 'bizum' ? 'selected' : ''; ?>>Bizum</option>
        </select>

        <button type="submit" class="btn-general">Guardar Cambios</button>
    </form>
    <div class="button-container">
        <a href="cambiar_membresia.php" class="btn-general">Cambiar Membresía</a>
    </div>

</main>

<?php include '../includes/footer.php'; ?>