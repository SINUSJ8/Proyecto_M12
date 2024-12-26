<?php

// Obtener la URL de referencia de donde se accede aquí
$referer = $_SERVER['HTTP_REFERER'];

// Verificar la URL de referencia y decidir qué header incluir, 
//así como conectar con base de datos y obtener datos de membresía del usuario
if (strpos($referer, 'index.php') !== false) {
    include '../Includes/header.php';
} else {
    include '../usuarios/user_header.php';
    session_start();
    require_once('../usuarios/user_functions.php');
}
    include '../Includes/general.php';
    $conn = obtenerConexion();

    // Verificar nuevamente si viene de usuarios y si el usuario ha iniciado sesión, de lo contrario redirigir al inicio
    if (strpos($referer, 'index.php') == false) {
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
            exit();
    }
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
                <?php
                /*Comprobamos que se viene desde usuario para permitir que pueda elegir pagar. 
                Si accede desde la pagina principal únicamente puede visualizar las mambresías 
                pero no los métodos de pago*/
                if (strpos($referer, 'index.php') == false) {
                    ?>
                    <form action="../pagos/proceso_pago.php" method="POST">
                        <input type="hidden" name="id_membresia" value="<?php echo $id; ?>">
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
                    <?php } ?>
                
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    // Cuando se acede desde index, se habilita botón Volver a pagina principal
    if (strpos($referer, 'index.php') !== false) {
    
        echo '<div class="button-container">
            <a href="../../index.php" class="button">Volver a la Página Principal</a>
        </div>';
    }        
    ?>
    </main>

</body>

</html>