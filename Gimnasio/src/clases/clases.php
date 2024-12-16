<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();

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
$clases_json = json_encode($clases);


$title = "Listado de Clases";
include '../admin/admin_header.php';
?>
<?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clase_eliminada'): ?>
    <p class="success-message">La clase se ha eliminado correctamente.</p>
<?php endif; ?>


<head>
    <link rel="stylesheet" href="../../assets/css/estilos_clases.css">
</head>

<body>
    <main>
        <h2>Clases Existentes</h2>
        <!-- Formulario de búsqueda -->

        <form method="GET" action="clases.php" class="search-form">
            <input type="text" name="nombre_clase" placeholder="Nombre de la clase" value="<?= htmlspecialchars(isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="nombre_monitor" placeholder="Nombre del monitor" value="<?= htmlspecialchars(isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="especialidad" placeholder="Especialidad" value="<?= htmlspecialchars(isset($_GET['especialidad']) ? $_GET['especialidad'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="date" name="fecha" value="<?= htmlspecialchars(isset($_GET['fecha']) ? $_GET['fecha'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <button type="button" class="reset-button" onclick="limpiarFormulario()">Limpiar</button>
        </form>

        <!-- Botón para crear clase -->
        <div class="button-container">
            <a href="crear_clase.php" class="button">Crear Clase</a>
        </div>

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
        <link rel="stylesheet" href="../../assets/css/estilos_clases.css">
        <section class="form_container">
            <div class="infoCalendario">
                <div class="semanaPrev" id="semanaPrevia">&#9664;</div>Semana 
                <div class="semana" id="semana"></div> del mes de 
                <div class="mes" id="mes"> </div>
                <div class="anyo" id="anyo"> </div>
                <div class="semanaPos" id="semanaPosterior">&#9654;</div>
            </div>
            <br>
            <div class = "calendario">
                <div class = "calendarioHoras" id="horas"></div>
                <div class="calendarioContenido" id="calendarioContenido">
                </div>
            </div>

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
    <script src="../../assets/js/clases.js"></script>
    <script src="../../assets/js/calendario.js"></script>

    <script type="text/javascript">
        let clases = <?= $clases_json; ?>; 
    </script>

    </main>
</body>