<?php
require_once('../includes/general.php');

function obtenerClases($conn, $filtros = [], $tipo = 'actuales')
{
    $sql = "SELECT 
                c.id_clase, 
                c.nombre, 
                m.nombre AS especialidad, 
                u.nombre AS monitor, 
                mo.disponibilidad AS monitor_disponible, 
                c.fecha, 
                c.horario, 
                c.duracion, 
                c.capacidad_maxima
            FROM clase c
            LEFT JOIN monitor mo ON c.id_monitor = mo.id_monitor
            LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
            LEFT JOIN especialidad m ON c.id_especialidad = m.id_especialidad
            WHERE 1=1";

    $params = [];
    $types = "";

    // Filtrar por tipo de clase
    if ($tipo === 'actuales') {
        $sql .= " AND (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.horario >= CURTIME()))";
    } elseif ($tipo === 'anteriores') {
        $sql .= " AND (c.fecha < CURDATE() OR (c.fecha = CURDATE() AND c.horario < CURTIME()))";
    }

    // Aplicar filtros adicionales
    if (!empty($filtros['nombre_clase'])) {
        $sql .= " AND c.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_clase'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['nombre_monitor'])) {
        $sql .= " AND u.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_monitor'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['especialidad'])) {
        $sql .= " AND m.nombre LIKE ?";
        $params[] = '%' . $filtros['especialidad'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['fecha'])) {
        $sql .= " AND c.fecha = ?";
        $params[] = $filtros['fecha'];
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $clases = [];

    while ($row = $result->fetch_assoc()) {
        $row['monitor_disponible'] = $row['monitor_disponible'] ?? 'sin monitor'; // Ajustar si no hay monitor
        $clases[] = $row;
    }

    $stmt->close();
    return $clases;
}
function obtenerTotalClases($conn, $filtros = [], $tipo = 'actuales')
{
    $sql = "SELECT COUNT(*) AS total FROM clase c
            LEFT JOIN monitor mo ON c.id_monitor = mo.id_monitor
            LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
            LEFT JOIN especialidad m ON c.id_especialidad = m.id_especialidad
            WHERE 1=1";

    $params = [];
    $types = "";

    // Filtrar por tipo de clase
    if ($tipo === 'actuales') {
        $sql .= " AND (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.horario >= CURTIME()))";
    } elseif ($tipo === 'anteriores') {
        $sql .= " AND (c.fecha < CURDATE() OR (c.fecha = CURDATE() AND c.horario < CURTIME()))";
    }

    // Aplicar filtros adicionales
    if (!empty($filtros['nombre_clase'])) {
        $sql .= " AND c.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_clase'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['nombre_monitor'])) {
        $sql .= " AND u.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_monitor'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['especialidad'])) {
        $sql .= " AND m.nombre LIKE ?";
        $params[] = '%' . $filtros['especialidad'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['fecha'])) {
        $sql .= " AND c.fecha = ?";
        $params[] = $filtros['fecha'];
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    return $data['total'];
}

function obtenerClasesPaginadas($conn, $filtros = [], $tipo = 'actuales', $limit = 8, $offset = 0)
{
    $sql = "SELECT 
                c.id_clase, 
                c.nombre, 
                m.nombre AS especialidad, 
                u.nombre AS monitor, 
                mo.disponibilidad AS monitor_disponible, 
                c.fecha, 
                c.horario, 
                c.duracion, 
                c.capacidad_maxima
            FROM clase c
            LEFT JOIN monitor mo ON c.id_monitor = mo.id_monitor
            LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
            LEFT JOIN especialidad m ON c.id_especialidad = m.id_especialidad
            WHERE 1=1";

    $params = [];
    $types = "";

    // Filtrar por tipo de clase
    if ($tipo === 'actuales') {
        $sql .= " AND (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.horario >= CURTIME()))";
    } elseif ($tipo === 'anteriores') {
        $sql .= " AND (c.fecha < CURDATE() OR (c.fecha = CURDATE() AND c.horario < CURTIME()))";
    }

    // Aplicar filtros adicionales
    if (!empty($filtros['nombre_clase'])) {
        $sql .= " AND c.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_clase'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['nombre_monitor'])) {
        $sql .= " AND u.nombre LIKE ?";
        $params[] = '%' . $filtros['nombre_monitor'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['especialidad'])) {
        $sql .= " AND m.nombre LIKE ?";
        $params[] = '%' . $filtros['especialidad'] . '%';
        $types .= "s";
    }

    if (!empty($filtros['fecha'])) {
        $sql .= " AND c.fecha = ?";
        $params[] = $filtros['fecha'];
        $types .= "s";
    }

    // Añadir paginación
    $sql .= " ORDER BY c.fecha ASC, c.horario ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $clases = [];
    while ($row = $result->fetch_assoc()) {
        $row['monitor_disponible'] = $row['monitor_disponible'] ?? 'sin monitor'; // Ajustar si no hay monitor
        $clases[] = $row;
    }

    $stmt->close();
    return $clases;
}


function obtenerDetallesClase($conn, $id_clase)
{
    $sql = "
        SELECT c.nombre, c.fecha, c.horario, c.duracion, c.capacidad_maxima,
               m.nombre AS especialidad, u.nombre AS monitor
        FROM clase c
        LEFT JOIN monitor mo ON c.id_monitor = mo.id_monitor
        LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
        LEFT JOIN especialidad m ON c.id_especialidad = m.id_especialidad
        WHERE c.id_clase = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $clase = $result->fetch_assoc();
    $stmt->close();
    return $clase;
}



function crearClase($conn, $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad)
{
    $sql = "INSERT INTO clase (nombre, id_monitor, id_especialidad, fecha, horario, duracion, capacidad_maxima)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissii", $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad);
    $stmt->execute();
    $stmt->close();
}

function eliminarClase($conn, $id_clase)
{
    $sql = "DELETE FROM clase WHERE id_clase = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $stmt->close();
}

function actualizarClase($conn, $id_clase, $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad)
{
    $sql = "UPDATE clase
            SET nombre = ?, id_monitor = ?, id_especialidad = ?, fecha = ?, horario = ?, duracion = ?, capacidad_maxima = ?
            WHERE id_clase = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissiii", $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad, $id_clase);
    $stmt->execute();
    $stmt->close();
}
function obtenerMiembrosInscritos($conn, $id_clase)
{
    $sql = "
        SELECT u.id_usuario, u.email, u.nombre
        FROM asistencia a
        INNER JOIN miembro m ON a.id_miembro = m.id_miembro
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE a.id_clase = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $miembros = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $miembros;
}
function obtenerMonitorDeClase($conn, $id_clase)
{
    $sql = "
        SELECT u.id_usuario, u.email, u.nombre
        FROM clase c
        INNER JOIN monitor m ON c.id_monitor = m.id_monitor
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE c.id_clase = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $monitor = $result->fetch_assoc();
    $stmt->close();
    return $monitor;
}

function eliminarParticipanteDeClase($conn, $id_clase, $id_miembro, $id_monitor)
{
    // Verificar si el monitor tiene asignada la clase
    $sqlVerificarClase = "
        SELECT 1 
        FROM clase c
        INNER JOIN monitor m ON c.id_monitor = m.id_monitor
        WHERE c.id_clase = ? AND m.id_usuario = ?
    ";
    $stmt = $conn->prepare($sqlVerificarClase);
    $stmt->bind_param("ii", $id_clase, $id_monitor);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return ['success' => false, 'mensaje' => 'No tienes permiso para modificar esta clase.'];
    }

    // Eliminar al participante de la clase
    $sqlEliminarParticipante = "
        DELETE FROM asistencia 
        WHERE id_clase = ? AND id_miembro = ?
    ";
    $stmt = $conn->prepare($sqlEliminarParticipante);
    $stmt->bind_param("ii", $id_clase, $id_miembro);
    if ($stmt->execute()) {
        return ['success' => true, 'mensaje' => 'El participante ha sido eliminado de la clase.'];
    } else {
        return ['success' => false, 'mensaje' => 'Error al eliminar al participante.'];
    }
}
function obtenerParticipantesClase($conn, $id_clase)
{
    $sql = "
        SELECT u.nombre, u.email, m.id_miembro
        FROM asistencia a
        INNER JOIN miembro m ON a.id_miembro = m.id_miembro
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE a.id_clase = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $participantes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $participantes;
}
function obtenerClasesAsignadasMonitor($conn, $id_monitor)
{
    if (!$id_monitor) {
        throw new Exception("El monitor no tiene un ID válido.");
    }

    $sqlClases = "
        SELECT 
            c.id_clase, 
            c.nombre AS clase_nombre, 
            e.nombre AS especialidad, 
            c.fecha, 
            c.horario, 
            c.duracion
        FROM clase c
        INNER JOIN monitor m ON c.id_monitor = m.id_monitor
        INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        WHERE c.id_monitor = ?
        ORDER BY c.fecha, c.horario
    ";

    $stmt = $conn->prepare($sqlClases);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $id_monitor);
    $stmt->execute();
    $resultClases = $stmt->get_result();

    if ($resultClases->num_rows === 0) {
        return [];
    }


    $clases = $resultClases->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $clases;
}
function obtenerNumeroInscritos($conn, $id_clase)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM asistencia WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}
function obtenerCapacidadClase($conn, $id_clase)
{
    $stmt = $conn->prepare("SELECT capacidad_maxima FROM clase WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $capacidad = $result->fetch_assoc()['capacidad_maxima'] ?? 0;
    $stmt->close();
    return $capacidad;
}
