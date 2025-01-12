<?php
$title = "Cambiar Membresía";
include '../miembros/miembro_header.php';
require_once '../includes/general.php';

$conn = obtenerConexion();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener el ID de la membresía actual activa del miembro
$query_actual = "SELECT mm.id_membresia 
                 FROM miembro mi
                 JOIN miembro_membresia mm ON mi.id_miembro = mm.id_miembro
                 WHERE mi.id_usuario = ? AND mm.estado = 'activa'";
$stmt_actual = $conn->prepare($query_actual);
$stmt_actual->bind_param("i", $id_usuario);
$stmt_actual->execute();
$result_actual = $stmt_actual->get_result();
$membresia_actual = $result_actual->fetch_assoc()['id_membresia'] ?? null;
$stmt_actual->close();

// Consultar todas las membresías y sus entrenamientos asociados
$query = "
    SELECT m.id_membresia, m.tipo, m.precio, m.duracion, m.beneficios, e.nombre AS entrenamiento
    FROM membresia m
    LEFT JOIN membresia_entrenamiento me ON m.id_membresia = me.id_membresia
    LEFT JOIN especialidad e ON me.id_entrenamiento = e.id_especialidad
    ORDER BY m.id_membresia
";
$result = $conn->query($query);
$membresias = [];

// Organizar las membresías
while ($row = $result->fetch_assoc()) {
    $id_membresia = $row['id_membresia'];
    if (!isset($membresias[$id_membresia])) {
        $membresias[$id_membresia] = [
            'id_membresia' => $id_membresia,
            'tipo' => $row['tipo'],
            'precio' => $row['precio'],
            'duracion' => $row['duracion'],
            'beneficios' => $row['beneficios'],
            'entrenamientos' => []
        ];
    }
    if ($row['entrenamiento']) {
        $membresias[$id_membresia]['entrenamientos'][] = $row['entrenamiento'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Membresía</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <main>
        <h1 class="section-title">Cambiar Membresía</h1>
        <p class="intro-text">Selecciona una nueva membresía. La membresía activa no está disponible para ser seleccionada.</p>

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

                    <?php if ($membresia['id_membresia'] != $membresia_actual): ?>
                        <form action="../pagos/cambio_proceso_pago.php" method="POST">
                            <input type="hidden" name="id_membresia" value="<?php echo htmlspecialchars($id); ?>">
                            <label for="metodo_pago_<?php echo $id; ?>">Método de Pago:</label>
                            <select name="metodo_pago" id="metodo_pago_<?php echo $id; ?>" required>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="paypal">PayPal</option>
                                <option value="bizum">Bizum</option>
                            </select>
                            <button type="submit" class="btn-general">Elegir Membresía</button>
                        </form>
                    <?php else: ?>
                        <button class="btn-general" disabled>Ya tienes esta membresía</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>