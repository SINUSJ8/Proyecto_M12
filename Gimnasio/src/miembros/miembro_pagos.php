<?php
// Incluir el header que ya gestiona la sesión del usuario
$title = "Historial de Pagos";
include_once '../miembros/miembro_header.php';

// Incluir conexión y funciones generales
require_once '../Includes/general.php';
$conn = obtenerConexion();

// Consulta para obtener solo los datos básicos de los pagos
$id_usuario = $_SESSION['id_usuario'];
$query = "
    SELECT 
        p.monto,
        p.fecha_pago,
        p.metodo_pago
    FROM pago p
    INNER JOIN miembro m ON p.id_miembro = m.id_miembro
    WHERE m.id_usuario = ?
    ORDER BY p.fecha_pago DESC, p.id_pago ASC;
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$historial_pagos = [];
while ($row = $result->fetch_assoc()) {
    $historial_pagos[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <main>
        <h1 class="section-title" title="Consulta los pagos realizados para tus membresías.">Historial de Pagos</h1>
        <p class="intro-text" title="Aquí encontrarás un resumen de los pagos realizados para tus membresías.">Aquí puedes consultar los pagos realizados para tus membresías.</p>

        <?php if (!empty($historial_pagos)): ?>
            <div class="form_container_large">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th title="Cantidad pagada en euros (€).">Monto (€)</th>
                            <th title="Fecha en la que se realizó el pago.">Fecha de Pago</th>
                            <th title="Método de pago utilizado.">Método de Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_pagos as $pago): ?>
                            <tr>
                                <td title="Monto pagado: <?php echo number_format($pago['monto'], 2); ?> €">
                                    <?php echo htmlspecialchars(number_format($pago['monto'], 2)); ?>
                                </td>
                                <td title="Pago realizado el: <?php echo date('d-m-Y', strtotime($pago['fecha_pago'])); ?>">
                                    <?php echo htmlspecialchars(date('d-m-Y', strtotime($pago['fecha_pago']))); ?>
                                </td>
                                <td title="Método de pago: <?php echo htmlspecialchars($pago['metodo_pago']); ?>">
                                    <?php echo htmlspecialchars($pago['metodo_pago']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="mensaje-info" title="No se han encontrado pagos en tu historial.">No se encontraron pagos en tu historial.</p>
        <?php endif; ?>

        <div class="button-container">
            <a href="../miembros/miembro.php" class="btn-general" title="Regresar a tu perfil de usuario.">Volver al Perfil</a>
        </div>
    </main>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>