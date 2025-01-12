<?php
// Incluir el header que ya gestiona la sesión del usuario
include_once '../miembros/miembro_header.php';

// Incluir conexión y funciones generales
require_once '../Includes/general.php';
$conn = obtenerConexion();

// Consulta para obtener el historial de pagos del usuario actual
$id_usuario = $_SESSION['id_usuario'];
$query = "
    SELECT
        p.id_pago,
        p.monto,
        p.fecha_pago,
        p.metodo_pago,
        m.tipo AS tipo_membresia,
        m.duracion AS duracion_membresia,
        m.precio AS precio_membresia
    FROM pago p
    INNER JOIN miembro mi ON p.id_miembro = mi.id_miembro
    INNER JOIN membresia m ON mi.id_membresia = m.id_membresia
    WHERE mi.id_usuario = ?
    ORDER BY p.fecha_pago DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_usuario);
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
        <h1 class="section-title">Historial de Pagos</h1>
        <p class="intro-text">Aquí puedes consultar los pagos realizados para tus membresías.</p>

        <?php if (!empty($historial_pagos)): ?>
            <div class="form_container_large">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID Pago</th>
                            <th>Monto (€)</th>
                            <th>Fecha de Pago</th>
                            <th>Método de Pago</th>
                            <th>Tipo de Membresía</th>
                            <th>Duración (meses)</th>
                            <th>Precio (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_pagos as $pago): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pago['id_pago']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($pago['monto'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($pago['fecha_pago']))); ?></td>
                                <td><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                                <td><?php echo htmlspecialchars($pago['tipo_membresia']); ?></td>
                                <td><?php echo htmlspecialchars($pago['duracion_membresia']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($pago['precio_membresia'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="mensaje-info">No se encontraron pagos en tu historial.</p>
        <?php endif; ?>

        <div class="button-container">
            <a href="../miembros/miembro.php" class="btn-general">Volver al Perfil</a>
        </div>
    </main>
</body>

</html>