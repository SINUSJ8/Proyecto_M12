<?php
session_start();
require_once('../usuarios/user_functions.php');
include '../usuarios/user_header.php';

$conn = obtenerConexion();

// Verificar si el usuario ha iniciado sesión, de lo contrario redirigir al inicio
if (!isset($_SESSION['id_usuario'])) {
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
    <h1>Elige tu Membresía</h1>
    <div class="membresia-container">
        <?php foreach ($membresias as $id => $membresia): ?>
            <div class="membresia-card">
                <h2><?php echo htmlspecialchars($membresia['tipo']); ?></h2>
                <p>Precio: <?php echo htmlspecialchars($membresia['precio']); ?> €</p>
                <p>Duración: <?php echo htmlspecialchars($membresia['duracion']); ?> mes(es)</p>
                <p>Beneficios: <?php echo htmlspecialchars($membresia['beneficios']); ?></p>

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

                <form action="../pagos/proceso_pago.php" method="POST">
                    <input type="hidden" name="id_membresia" value="<?php echo $id; ?>">
                    <label for="metodo_pago">Método de Pago:</label>
                    <select name="metodo_pago" id="metodo_pago" required>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="Paypal">Paypal</option>
                        <option value="Bizum">Bizum</option>
                    </select>
                    <button type="submit">Elegir Membresía</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>