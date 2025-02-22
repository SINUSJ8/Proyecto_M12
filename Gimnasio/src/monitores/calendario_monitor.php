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
    $clases = []; // Si hay un error, aseguramos que sea un array vacío
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

        <!-- El calendario siempre se renderiza, incluso si está vacío -->
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const eventos = <?= json_encode($eventos); ?>; // Siempre será un array (puede estar vacío)

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'bootstrap',
                locale: 'es',
                firstDay: 1,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventos, // Se cargan los eventos (puede ser un array vacío)
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