<?php
require_once '../includes/general.php';
$title = "Membresías";
include_once __DIR__ . '/../includes/header.php';

$conn = obtenerConexion();

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

while ($row = $result->fetch_assoc()) {
    $membresia_id = $row['id_membresia'];
    if (!isset($membresias[$membresia_id])) {
        $membresias[$membresia_id] = [
            'id_membresia' => $membresia_id,
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
    <title>Explora Nuestras Membresías</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<body>
    <main class="form_container">
        <h1 class="section-title">Explora Nuestras Membresías</h1>
        <p class="intro-text">Consulta nuestras opciones y beneficios. Regístrate para seleccionar una membresía.</p>

        <div class="membresia-container">
            <?php foreach ($membresias as $membresia): ?>
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
                </div>
            <?php endforeach; ?>
        </div>

        <div class="button-container">
            <a href="../../index.php" class="button">Volver a la Página Principal</a>
        </div>
    </main>
</body>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</html>