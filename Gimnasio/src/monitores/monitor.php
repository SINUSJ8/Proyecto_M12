<?php
session_start();
require_once '../clases/class_functions.php';

// Verifica que el usuario ha iniciado sesión y tiene el rol de "monitor"
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 'monitor') {
    header("Location: index.php?error=Acceso+denegado");
    exit();
}

$nombre = $_SESSION['nombre'];

$conn = obtenerConexion();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_clase'])) {
    $id_clase = intval($_POST['id_clase']);

    // Llama a la función para eliminar la clase
    eliminarClase($conn, $id_clase);

    // Redirige de vuelta a la página de clases con un mensaje
    header('Location: clases.php?mensaje=clase_eliminada');
    exit;
}


$filtros = [
    'nombre_clase' => isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '',
    'nombre_monitor' => isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '',
    'especialidad' => isset($_GET['especialidad']) ? $_GET['especialidad'] : '',
    'fecha' => isset($_GET['fecha']) ? $_GET['fecha'] : '',
];


$clases = obtenerClases($conn, $filtros);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido Monitor</title>
    <link rel="stylesheet" href="../../assets/css/estilos_clases.css">
</head>

<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>
    <p>Accede a todas tus actividades y servicios como monitor.</p>
    
    <form method="GET" action="clases.php" class="search-form">
            <input type="text" name="nombre_clase" placeholder="Nombre de la clase" value="<?= htmlspecialchars(isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="nombre_monitor" value="<?php echo htmlspecialchars($nombre); ?>" readonly>
            <input type="text" name="especialidad" placeholder="Especialidad" value="<?= htmlspecialchars(isset($_GET['especialidad']) ? $_GET['especialidad'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="date" name="fecha" value="<?= htmlspecialchars(isset($_GET['fecha']) ? $_GET['fecha'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <button type="button" class="reset-button" onclick="limpiarFormulario()">Limpiar</button>
        </form>

        <!-- Tabla para mostrar clases -->
        <section class="form_container">
            <table id="tabla-clases" class="styled-table">
                <thead>
                    <tr>
                        <th onclick="ordenarTabla(0)" class="sortable">Nombre</th>
                        <th onclick="ordenarTabla(1)" class="sortable">Especialidad</th>
                        <th onclick="ordenarTabla(2)" class="sortable">Monitor</th>
                        <th onclick="ordenarTabla(3)" class="sortable">Fecha</th>
                        <th onclick="ordenarTabla(4)" class="sortable">Horario</th>
                        <th onclick="ordenarTabla(5)" class="sortable">Duración</th>
                        <th onclick="ordenarTabla(6)" class="sortable">Capacidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clases as $clase): ?>
                        <tr>
                            <td><?= htmlspecialchars($clase['nombre']); ?></td>
                            <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                            <td><?= htmlspecialchars($clase['monitor']); ?></td>
                            <td><?= htmlspecialchars($clase['fecha']); ?></td>
                            <td><?= htmlspecialchars($clase['horario']); ?></td>
                            <td><?= htmlspecialchars($clase['duracion']); ?> min</td>
                            <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                            <td>
                                <form method="POST" action="clases.php" onsubmit="return confirmarEliminacion();">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <button type="submit" class="delete-button">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </section>

        <h1>Calendario semanal de clases</h1>
        <link rel="stylesheet" href="../assets/css/estilos_clases.css">
            <section class="form_container">
            
                <div class="infoCalendario">
                    <div class="semanaPrev" id="semanaPrevia">&#9664;</div>
                    <div class="semana" id="semana"></div>
                    <div class="mes" id="mes"></div>
                    <div class="anyo" id="anyo"></div>
                    <div class="semanaPos" id="semanaPosterior">&#9654;</div>
                </div>
                <div class="calendarioSemana">
                    <div class="calendarioDia calendarioItem">Lunes</div>
                    <div class="calendarioDia calendarioItem">Martes</div>
                    <div class="calendarioDia calendarioItem">Miércoles</div>
                    <div class="calendarioDia calendarioItem">Jueves</div>
                    <div class="calendarioDia calendarioItem">Viernes</div>
                    <div class="calendarioDia calendarioItem">Sábado</div>
                    <div class="calendarioDia calendarioItem">Domingo</div>
                </div>
                <div class="calendarioFechas" id="fechas"></div>

            
            </section>


    
    <?php include '../includes/footer.php'; ?>

    <!-- Incluir el archivo de JavaScript externo -->
    <script src="../assets/js/clases.js"></script>
    <script src="../assets/js/calendario.js"></script>
    
</body>

</html>