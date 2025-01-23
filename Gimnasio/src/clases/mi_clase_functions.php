<?php

require_once '../miembros/member_functions.php';

/**
 * Obtener el ID del miembro.
 */
function obtenerIdMiembro($conn, $id_usuario)
{
    $sql = "SELECT id_miembro FROM miembro WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc()['id_miembro'];
    } else {
        throw new Exception("No se encontró información del miembro.");
    }
}

/**
 * Apuntarse a una clase.
 */
function apuntarseClase($conn, $id_clase, $id_miembro)
{
    // Verificar si el miembro ya está inscrito en la clase
    $sqlVerificar = "SELECT * FROM asistencia WHERE id_clase = ? AND id_miembro = ?";
    $stmtVerificar = $conn->prepare($sqlVerificar);
    $stmtVerificar->bind_param("ii", $id_clase, $id_miembro);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();

    if ($resultadoVerificar->num_rows > 0) {
        return "ya_inscrito";
    }

    // Insertar el registro en la tabla asistencia
    $fecha_actual = date('Y-m-d');
    $sqlInsertar = "
        INSERT INTO asistencia (id_clase, id_miembro, fecha, asistencia)
        VALUES (?, ?, ?, 'presente')
    ";
    $stmtInsertar = $conn->prepare($sqlInsertar);
    $stmtInsertar->bind_param("iis", $id_clase, $id_miembro, $fecha_actual);
    $stmtInsertar->execute();

    if ($stmtInsertar->affected_rows > 0) {
        return "apuntado";
    } else {
        throw new Exception("Error al apuntarse a la clase.");
    }
}

/**
 * Borrarse de una clase.
 */
function borrarseClase($conn, $id_clase, $id_miembro)
{
    $sqlBorrar = "DELETE FROM asistencia WHERE id_clase = ? AND id_miembro = ?";
    $stmtBorrar = $conn->prepare($sqlBorrar);
    $stmtBorrar->bind_param("ii", $id_clase, $id_miembro);
    $stmtBorrar->execute();

    if ($stmtBorrar->affected_rows > 0) {
        return "borrado";
    } else {
        return "no_borrado";
    }
}
function yaInscritoEnClase($conn, $id_clase, $id_miembro)
{
    $stmt = $conn->prepare("SELECT 1 FROM asistencia WHERE id_clase = ? AND id_miembro = ?");
    $stmt->bind_param('ii', $id_clase, $id_miembro);
    $stmt->execute();
    $stmt->store_result();
    $resultado = $stmt->num_rows > 0;
    $stmt->close();
    return $resultado;
}
function claseEstaCompleta($conn, $id_clase)
{
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM asistencia WHERE id_clase = ?) AS inscritos, 
            c.capacidad_maxima 
        FROM clase c 
        WHERE c.id_clase = ?
    ");
    $stmt->bind_param('ii', $id_clase, $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return intval($data['inscritos']) >= intval($data['capacidad_maxima']);
}

function obtenerClasesInscritas($conn, $id_miembro)
{
    $sql = "
        SELECT c.id_clase, c.nombre, c.fecha, c.horario
        FROM asistencia a
        INNER JOIN clase c ON a.id_clase = c.id_clase
        WHERE a.id_miembro = ?
          AND (c.fecha > CURRENT_DATE() OR (c.fecha = CURRENT_DATE() AND c.horario >= CURRENT_TIME()))
        ORDER BY c.fecha, c.horario
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = [];

    while ($row = $result->fetch_assoc()) {
        $clases[] = $row;
    }

    $stmt->close();
    return $clases;
}

/**
 * Obtiene las especialidades de un miembro.
 * 
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $id_miembro ID del miembro.
 * @return array Lista de IDs de especialidades del miembro.
 */
function obtenerEspecialidadesMiembro($conn, $id_miembro)
{
    $sql = "
        SELECT e.id_especialidad 
        FROM miembro_entrenamiento me
        INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad 
        WHERE me.id_miembro = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $result = $stmt->get_result();
    $especialidades = [];

    while ($row = $result->fetch_assoc()) {
        $especialidades[] = $row['id_especialidad'];
    }

    $stmt->close();
    return $especialidades;
}
function obtenerClasesDisponibles($conn, $especialidades, $id_miembro)
{
    $especialidadesStr = implode(',', array_map('intval', $especialidades)); // Asegurar valores enteros

    $sql = "
        SELECT 
            c.id_clase,
            c.nombre,
            c.fecha,
            c.horario,
            c.duracion,
            c.capacidad_maxima,
            e.nombre AS especialidad,
            u.nombre AS monitor, -- Obtener el nombre del monitor desde la tabla usuario
            (SELECT COUNT(*) FROM asistencia a WHERE a.id_clase = c.id_clase) AS inscritos,
            EXISTS (
                SELECT 1 
                FROM asistencia a 
                WHERE a.id_clase = c.id_clase AND a.id_miembro = ?
            ) AS inscrito,
            CASE
                WHEN (SELECT COUNT(*) FROM asistencia a WHERE a.id_clase = c.id_clase) >= c.capacidad_maxima THEN 1
                ELSE 0
            END AS completa
        FROM clase c
        INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        LEFT JOIN monitor m ON c.id_monitor = m.id_monitor -- Vincular monitores con las clases
        LEFT JOIN usuario u ON m.id_usuario = u.id_usuario -- Obtener el nombre del monitor desde usuario
        WHERE c.id_especialidad IN ($especialidadesStr)
          AND (c.fecha > CURRENT_DATE() OR (c.fecha = CURRENT_DATE() AND c.horario >= CURRENT_TIME()))
        ORDER BY c.fecha, c.horario
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = [];

    while ($row = $result->fetch_assoc()) {
        $clases[] = $row;
    }

    $stmt->close();
    return $clases;
}
function obtenerClasesCalendario($conn, $id_miembro)
{
    $sql = "
        SELECT 
            c.id_clase,
            c.nombre,
            c.fecha,
            c.horario,
            c.duracion,
            c.capacidad_maxima,
            e.nombre AS especialidad,
            u.nombre AS monitor,
            (SELECT COUNT(*) FROM asistencia a WHERE a.id_clase = c.id_clase) AS inscritos,
            EXISTS (
                SELECT 1 
                FROM asistencia a 
                WHERE a.id_clase = c.id_clase AND a.id_miembro = ?
            ) AS inscrito
        FROM clase c
        INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
        LEFT JOIN monitor m ON c.id_monitor = m.id_monitor
        LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE c.fecha >= CURRENT_DATE()
        ORDER BY c.fecha, c.horario
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = [];

    while ($row = $result->fetch_assoc()) {
        $clases[] = $row;
    }

    $stmt->close();
    return $clases;
}
