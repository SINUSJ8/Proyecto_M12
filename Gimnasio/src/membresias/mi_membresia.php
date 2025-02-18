<?php
$title = "Información de Membresía";
include '../miembros/miembro_header.php';
require_once '../miembros/member_functions.php';
require_once '../Includes/general.php';

$conn = obtenerConexion();
$nombre = $_SESSION['nombre'];
$id_usuario = $_SESSION['id_usuario'];

// Obtener información del miembro
$miembro = informacionMembresia($id_usuario);

if (!$miembro) {
    echo "<p class='mensaje-error'>No se encontró información para este miembro.</p>";
    exit;
}

// Inicializar mensaje
$mensaje = "";

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $renovacion = isset($_POST['renovacion_automatica']) ? (int)$_POST['renovacion_automatica'] : null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;

    require_once '../miembros/member_functions.php';

    // Verificar si el usuario tiene membresía activa antes de actualizar la renovación automática
    $mensajes = [];

    if (!is_null($renovacion)) {
        $resultadoRenovacion = actualizarPreferenciasMembresia($id_usuario, $renovacion);
        if ($resultadoRenovacion) {
            $mensajes[] = $resultadoRenovacion;
        }
    }

    // Actualizar método de pago si se ha enviado
    if (!empty($metodo_pago)) {
        $resultadoPago = actualizarMetodoPagoGuardado($id_usuario, $metodo_pago);
        if ($resultadoPago) {
            $mensajes[] = $resultadoPago;
        }
    }

    // Si hay mensajes, redirigir con la notificación
    if (!empty($mensajes)) {
        $mensaje = implode(" | ", $mensajes);
        header("Location: mi_membresia.php?mensaje=" . urlencode($mensaje));
        exit();
    }
}

// Mostrar mensaje si está presente en la URL
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
}
?>



<main class="form_container">
    <h1 class="section-title" title="Consulta y administra tu membresía.">Información de Membresía</h1>
    <h2 title="Tu nombre registrado en el sistema.">Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>

    <?php if (!empty($mensaje)): ?>
        <p class="<?php echo (strpos($mensaje, 'Error') !== false) ? 'mensaje-error' : 'mensaje-confirmacion'; ?>" title="Mensaje de estado de la membresía.">
            <?php echo htmlspecialchars($mensaje); ?>
        </p>
    <?php endif; ?>

    <h3 title="Información detallada sobre tu membresía activa.">Detalles de tu Membresía</h3>
    <table class="styled-table">
        <tbody>
            <tr>
                <th title="Nombre de usuario en el sistema.">Nombre de Usuario:</th>
                <td title="Tu nombre de usuario registrado."><?php echo htmlspecialchars($miembro['nombre_usuario']); ?></td>
            </tr>
            <tr>
                <th title="Correo electrónico registrado en el sistema.">Email:</th>
                <td title="Tu dirección de correo electrónico."><?php echo htmlspecialchars($miembro['email']); ?></td>
            </tr>
            <tr>
                <th title="Fecha en la que te registraste.">Fecha de Registro:</th>
                <td title="Registrado el <?php echo date('d/m/Y', strtotime($miembro['fecha_registro'])); ?>">
                    <?php echo date('d/m/Y', strtotime($miembro['fecha_registro'])); ?>
                </td>
            </tr>
            <tr>
                <th title="Tipo de membresía activa.">Nombre de la Membresía:</th>
                <td title="Membresía actual: <?php echo htmlspecialchars($miembro['nombre_membresia']); ?>">
                    <?php echo htmlspecialchars($miembro['nombre_membresia']); ?>
                </td>
            </tr>
            <tr>
                <th title="Fecha de inicio de la membresía.">Fecha de Inicio de Membresía:</th>
                <td title="Inició el <?php echo date('d/m/Y', strtotime($miembro['fecha_inicio'])); ?>">
                    <?php echo date('d/m/Y', strtotime($miembro['fecha_inicio'])); ?>
                </td>
            </tr>
            <tr>
                <th title="Fecha en la que finaliza la membresía.">Fecha de Fin de Membresía:</th>
                <td title="Finaliza el <?php echo date('d/m/Y', strtotime($miembro['fecha_fin'])); ?>">
                    <?php echo date('d/m/Y', strtotime($miembro['fecha_fin'])); ?>
                </td>
            </tr>
            <tr>
                <th title="Estado actual de la membresía.">Estado de la Membresía:</th>
                <td title="Estado: <?php echo htmlspecialchars($miembro['estado']); ?>">
                    <?php echo htmlspecialchars($miembro['estado']); ?>
                </td>
            </tr>
            <tr>
                <th title="Si la membresía se renueva automáticamente.">Renovación Automática:</th>
                <td title="Renovación: <?php echo $miembro['renovacion_automatica'] ? 'Sí' : 'No'; ?>">
                    <?php echo $miembro['renovacion_automatica'] ? 'Sí' : 'No'; ?>
                </td>
            </tr>
            <tr>
                <th title="Monto total del pago de la membresía.">Monto del Pago:</th>
                <td title="Pago realizado de <?php echo htmlspecialchars($miembro['monto_pago']); ?> €">
                    <?php echo htmlspecialchars($miembro['monto_pago']); ?> €
                </td>
            </tr>
            <tr>
                <th title="Fecha en la que se realizó el pago.">Fecha de Pago:</th>
                <td title="Pago efectuado el <?php echo date('d/m/Y', strtotime($miembro['fecha_pago'])); ?>">
                    <?php echo date('d/m/Y', strtotime($miembro['fecha_pago'])); ?>
                </td>
            </tr>
            <tr>
                <th title="Método de pago utilizado para la membresía.">Método de Pago:</th>
                <td title="Método: <?php echo htmlspecialchars($miembro['metodo_pago']); ?>">
                    <?php echo htmlspecialchars($miembro['metodo_pago']); ?>
                </td>
            </tr>
            <tr>
                <th title="Especialidades incluidas en la membresía.">Entrenamientos/Especialidades:</th>
                <td title="Especialidades asignadas a tu membresía.">
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

    <h3 title="Configurar opciones de membresía.">Editar Preferencias</h3>
    <form method="POST" class="form-edit">
        <label for="renovacion_automatica" title="Activar o desactivar la renovación automática de la membresía.">Renovación Automática:</label>

        <?php
        // Verificar si la membresía activa del usuario está expirada o si la membresía está descontinuada
        $estado_membresia = $miembro['estado'];
        $estado_membresia_global = $miembro['estado_global'];
        $deshabilitar_renovacion = ($estado_membresia == 'expirada' || $estado_membresia_global == 'descontinuada');
        ?>

        <?php if ($deshabilitar_renovacion): ?>
            <select disabled title="Esta opción está deshabilitada porque la membresía está descontinuada o expirada.">
                <option selected>Esta membresía ha sido descontinuada</option>
            </select>
            <input type="hidden" name="renovacion_automatica" value="0">
        <?php else: ?>
            <select name="renovacion_automatica" id="renovacion_automatica"
                title="Selecciona si deseas que la membresía se renueve automáticamente.">
                <option value="1" <?php echo $miembro['renovacion_automatica'] ? 'selected' : ''; ?>>Sí</option>
                <option value="0" <?php echo !$miembro['renovacion_automatica'] ? 'selected' : ''; ?>>No</option>
            </select>
        <?php endif; ?>

        <label for="metodo_pago" title="Elige un nuevo método de pago para futuras renovaciones.">Método de Pago:</label>
        <select name="metodo_pago" id="metodo_pago">
            <option value="" selected title="No cambiar el método de pago actual.">No cambiar</option>
            <option value="tarjeta" title="Pagar con tarjeta.">Tarjeta</option>
            <option value="google_pay" title="Pagar con Google Pay.">Google Pay</option>
            <option value="transferencia" title="Pagar por transferencia bancaria.">Transferencia</option>
            <option value="paypal" title="Pagar con PayPal.">PayPal</option>
            <option value="bizum" title="Pagar con Bizum.">Bizum</option>
        </select>

        <button type="submit" class="btn-general" title="Guardar los cambios de tu membresía.">Guardar Cambios</button>
    </form>


    <div class="button-container">
        <a href="cambiar_membresia.php" class="btn-general" title="Modificar o cambiar tu tipo de membresía.">Cambiar Membresía</a>
    </div>

</main>

<script src="../../assets/js/alertas.js"></script>
<?php include '../includes/footer.php'; ?>