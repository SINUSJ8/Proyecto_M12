<?php
$title = "Mi Calendario";
include '../miembros/miembro_header.php';

require_once '../clases/mi_clase_functions.php';

$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

try {
    // Obtener el ID del miembro y sus clases
    $id_miembro = obtenerIdMiembro($conn, $id_usuario);
    $clases = obtenerClasesCalendario($conn, $id_miembro);
} catch (Exception $e) {
    echo "<p class='mensaje-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Convertir las clases en formato JSON para FullCalendar
$eventos = [];
foreach ($clases as $clase) {
    $eventos[] = [
        'title' => $clase['nombre'] . ' (' . $clase['especialidad'] . ')',
        'start' => $clase['fecha'] . 'T' . $clase['horario'],
        'end' => $clase['fecha'] . 'T' . date('H:i:s', strtotime($clase['horario'] . ' +' . $clase['duracion'] . ' minutes')),
        'color' => $clase['inscrito'] ? '#28a745' : '#a0ace5',
        'textColor' => '#ffffff',
        'extendedProps' => [
            'id_clase' => $clase['id_clase'],
            'inscrito' => $clase['inscrito'],
            'monitor' => $clase['monitor'],
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
            const eventos = <?= json_encode($eventos); ?>; // Pasar eventos desde PHP a JavaScript

            const calendarEl = document.getElementById('calendar');
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
                events: eventos,
                eventClick: function(info) {
                    const evento = info.event.extendedProps;
                    if (evento.inscrito) {
                        alert('Ya estás inscrito en esta clase: ' + info.event.title);
                    } else {
                        alert('Clase disponible: ' + info.event.title + '\nMonitor: ' + evento.monitor);
                    }
                },
            });

            calendar.render();
        });
    </script>
</body>
<?php include '../includes/footer.php'; ?>

</html>