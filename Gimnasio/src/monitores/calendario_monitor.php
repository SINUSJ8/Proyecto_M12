<?php
$title = "Mi Calendario";
require_once '../includes/general.php';
include '../monitores/monitores_header.php';
require_once '../monitores/monitor_functions.php';
require_once '../clases/class_functions.php';
$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

try {
    $id_monitor = obtenerIdMonitor($conn, $id_usuario);
    $especialidades = obtenerEspecialidades($conn, $id_monitor);
    $clases = obtenerClasesAsignadasMonitor($conn, $id_monitor);
} catch (Exception $e) {
    echo "<p class='mensaje-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Convertir las clases en formato JSON para FullCalendar
$eventos = [];
foreach ($clases as $clase) {
    $eventos[] = [
        'title' => $clase['clase_nombre'] . ' (' . $clase['especialidad'] . ')',
        'start' => $clase['fecha'] . 'T' . $clase['horario'],
        'end' => $clase['fecha'] . 'T' . date('H:i:s', strtotime($clase['horario'] . ' +' . $clase['duracion'] . ' minutes')),
        'textColor' => '#ffffff',
        'extendedProps' => [
            'id_clase' => $clase['id_clase'],

        ],
    ];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center"><?= $title; ?></h1>
        <p class="text-center text-muted">Consulta tus clases en el calendario.</p>

        <!-- Contenedor del calendario -->
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventos = <?= json_encode($eventos); ?>;

            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'bootstrap',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventos,
                eventClick: function(info) {
                    const evento = info.event.extendedProps;
                    alert(
                        'Clase programada: ' + info.event.title +
                        '\nHorario: ' + info.event.start.toISOString().slice(11, 16) + ' - ' +
                        info.event.end.toISOString().slice(11, 16)
                    );
                },
            });

            calendar.render();
        });
    </script>
</body>
<?php include '../includes/footer.php'; ?>

</html>