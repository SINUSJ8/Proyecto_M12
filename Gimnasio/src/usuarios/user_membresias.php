<?php

// Obtener la URL de referencia de donde se accede aquí
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// Incluir archivos según la referencia
if (strpos($referer, 'index.php') !== false) {
    include_once '../Includes/header.php';
} else {
    include_once '../usuarios/user_header.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once('../usuarios/user_functions.php');
}

// Incluir conexión y funciones generales
require_once '../Includes/general.php';
$conn = obtenerConexion();

// Verificar sesión si no viene de index.php
if (strpos($referer, 'index.php') === false && !isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

// Consulta para obtener las membresías con sus entrenamientos asociados
$query = "
    SELECT m.id_membresia, m.tipo, m.precio, m.duracion, m.beneficios, e.nombre AS entrenamiento
    FROM membresia m
    LEFT JOIN membresia_entrenamiento me ON m.id_membresia = me.id_membresia
    LEFT JOIN especialidad e ON me.id_entrenamiento = e.id_especialidad
    ORDER BY m.id_membresia
";

$result = $conn->query($query);
$membresias = [];

// Organizar los entrenamientos por membresía en un arreglo
while ($row = $result->fetch_assoc()) {
    $membresia_id = $row['id_membresia'];
    if (!isset($membresias[$membresia_id])) {
        $membresias[$membresia_id] = [
            'id_membresia' => $membresia_id, // Asegurarse de incluir el ID aquí
            'tipo' => $row['tipo'],
            'precio' => $row['precio'],
            'duracion' => $row['duracion'],
            'beneficios' => $row['beneficios'],
            'entrenamientos' => []
        ];
    }
    if ($row['entrenamiento']) {
        $membresias[$membresia_id]['entrenamientos'][] = $row['entrenamiento'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elige tu Membresía</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <main class="form_container">
        <h1 class="section-title">Elige tu Membresía</h1>
        <p class="intro-text">Selecciona la membresía que mejor se adapte a tus objetivos y empieza a disfrutar de los beneficios.</p>

        <div class="membresia-container">
            <?php foreach ($membresias as $id => $membresia): ?>
                <div class="membresia-card">
                    <h2><?php echo htmlspecialchars($membresia['tipo']); ?></h2>
                    <p><strong>Precio:</strong> <?php echo htmlspecialchars($membresia['precio']); ?> €</p>
                    <p><strong>Duración:</strong> <?php echo htmlspecialchars($membresia['duracion']); ?> mes(es)</p>
                    <p><strong>Beneficios:</strong> <?php echo htmlspecialchars($membresia['beneficios']); ?></p>

                    <h3>Entrenamientos Incluidos:</h3>
                    <ul>
                        <?php if (!empty($membresia['entrenamientos'])): ?>
                            <?php foreach ($membresia['entrenamientos'] as $entrenamiento): ?>
                                <li><?php echo htmlspecialchars($entrenamiento); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No incluye entrenamientos específicos.</li>
                        <?php endif; ?>
                    </ul>

                    <?php if (strpos($referer, 'index.php') === false): ?>
                        <form onsubmit="return mostrarConfirmacion(event, <?php echo htmlspecialchars(json_encode($membresia)); ?>)">
                            <label for="metodo_pago_<?php echo $id; ?>">Método de Pago:</label>
                            <select name="metodo_pago" id="metodo_pago_<?php echo $id; ?>" required>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="Paypal">Paypal</option>
                                <option value="Bizum">Bizum</option>
                            </select>
                            <button type="submit" class="btn-general">Elegir Membresía</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (strpos($referer, 'index.php') !== false): ?>
            <div class="button-container">
                <a href="../../index.php" class="button">Volver a la Página Principal</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal de confirmación -->
    <div id="modal-confirmacion" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Confirmar Membresía</h2>
            <p id="modal-detalles"></p>
            <form id="form-proceso-pago" action="../pagos/proceso_pago.php" method="POST">
                <input type="hidden" name="id_membresia" id="id_membresia_modal">
                <input type="hidden" name="metodo_pago" id="metodo_pago_modal">
                <button type="submit" class="btn-general">Pagar</button>
                <button type="button" class="btn-general cancel-button" onclick="cerrarModal()">Cancelar</button>
            </form>
        </div>
    </div>
    <script src="../../assets/js/validacion.js"></script>
</body>

</html>