<?php

// Obtiene todos los monitores de la base de datos
function obtenerMonitores($conn, $busqueda = '', $orden_columna = 'nombre', $orden_direccion = 'ASC', $especialidad_id = null, $disponibilidad = null)
{
    // Construcción inicial de la consulta SQL
    $sql = "SELECT u.id_usuario, u.nombre, u.email, m.experiencia, m.disponibilidad, 
                   GROUP_CONCAT(e.nombre SEPARATOR ', ') AS especialidades
            FROM usuario u
            INNER JOIN monitor m ON u.id_usuario = m.id_usuario
            LEFT JOIN monitor_especialidad me ON m.id_monitor = me.id_monitor
            LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad";

    // Agregar filtros de búsqueda y disponibilidad
    $conditions = [];
    $params = [];
    $types = '';

    if ($busqueda) {
        $conditions[] = "(u.nombre LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $busqueda . '%';
        $params[] = '%' . $busqueda . '%';
        $types .= 'ss';
    }
    if ($especialidad_id) {
        $conditions[] = "me.id_especialidad = ?";
        $params[] = $especialidad_id;
        $types .= 'i';
    }
    if (!empty($disponibilidad)) { // Solo agregar condición si disponibilidad tiene un valor
        $conditions[] = "m.disponibilidad = ?";
        $params[] = $disponibilidad;
        $types .= 's';
    }

    if ($conditions) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // Agregar agrupación y ordenamiento
    $sql .= " GROUP BY u.id_usuario ORDER BY $orden_columna $orden_direccion";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Devolver los resultados como un array asociativo
    $monitores = [];
    while ($row = $result->fetch_assoc()) {
        $monitores[] = $row;
    }

    $stmt->close();
    return $monitores;
}

// Función para eliminar un monitor de la base de datos
function eliminarMonitor($conn, $id_usuario)
{
    $conn->begin_transaction();
    $respuesta = ["success" => false, "message" => ""];

    try {
        // Eliminar de la tabla monitor
        $stmt = $conn->prepare("DELETE FROM monitor WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Eliminar el registro de usuario
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Devolver mensaje de éxito
        $respuesta["success"] = true;
        $respuesta["message"] = "Monitor eliminado correctamente.";
    } catch (Exception $e) {
        $conn->rollback();

        // Devolver mensaje de error
        $respuesta["success"] = false;
        $respuesta["message"] = "Error al eliminar el monitor: " . $e->getMessage();
    }

    return $respuesta; // Devolver el resultado
}



function obtenerMonitorPorID($conn, $id_usuario)
{
    // Consulta para obtener los datos básicos del monitor
    $sql = "SELECT m.id_monitor, u.id_usuario, u.nombre, u.email, m.experiencia, m.disponibilidad
            FROM monitor m
            INNER JOIN usuario u ON m.id_usuario = u.id_usuario
            WHERE u.id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $monitor = $result->fetch_assoc();
    $stmt->close();

    // Verificar si el monitor existe
    if (!$monitor) {
        return null; // Monitor no encontrado
    }

    // Asegurarse de que 'especialidad' esté definido
    $monitor['especialidad'] = isset($monitor['especialidad']) ? $monitor['especialidad'] : '';

    // Inicializar especialidades como un array vacío
    $monitor['especialidades'] = [];

    // Consulta para obtener las especialidades asociadas al monitor
    $sql = "SELECT e.id_especialidad, e.nombre 
            FROM monitor_especialidad me
            INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad
            WHERE me.id_monitor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $monitor['id_monitor']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Almacenar las especialidades en el array
    while ($row = $result->fetch_assoc()) {
        $monitor['especialidades'][] = [
            'id_especialidad' => $row['id_especialidad'],
            'nombre' => $row['nombre']
        ];
    }
    $stmt->close();

    return $monitor;
}

function actualizarMonitor($conn, $id_usuario, $nombre, $email, $especialidad, $experiencia, $disponibilidad)
{
    try {
        // ✅ Verificar si el usuario YA es un monitor para evitar INSERT accidental
        $stmt = $conn->prepare("SELECT id_monitor FROM monitor WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($id_monitor);
        $stmt->fetch();
        $stmt->close();

        if (!$id_monitor) {
            return ["success" => false, "message" => "Error: Este usuario no es un monitor registrado."];
        }

        // ✅ Si ya existe, actualizamos sus datos
        $sql = "UPDATE usuario u
                INNER JOIN monitor m ON u.id_usuario = m.id_usuario
                SET u.nombre = ?, u.email = ?, m.especialidad = ?, m.experiencia = ?, m.disponibilidad = ?
                WHERE u.id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisi", $nombre, $email, $especialidad, $experiencia, $disponibilidad, $id_usuario);
        $stmt->execute();
        $stmt->close();

        return ["success" => true, "message" => "Monitor actualizado correctamente"];
    } catch (Exception $e) {
        return ["success" => false, "message" => "Error al actualizar el monitor: " . $e->getMessage()];
    }
}


function actualizarEntrenamientosMonitor($conn, $id_monitor, $entrenamientos)
{
    // Primero, verificar que el monitor existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM monitor WHERE id_monitor = ?");
    $stmt->bind_param("i", $id_monitor);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        throw new Exception("El monitor con ID $id_monitor no existe.");
    }

    // Eliminar los entrenamientos actuales del monitor
    $stmt = $conn->prepare("DELETE FROM monitor_especialidad WHERE id_monitor = ?");
    $stmt->bind_param("i", $id_monitor);
    $stmt->execute();
    $stmt->close();

    // Insertar los nuevos entrenamientos
    $stmt = $conn->prepare("INSERT INTO monitor_especialidad (id_monitor, id_especialidad) VALUES (?, ?)");
    foreach ($entrenamientos as $id_especialidad) {
        $stmt->bind_param("ii", $id_monitor, $id_especialidad);
        $stmt->execute();
    }
    $stmt->close();
}
function obtenerEspecialidades($conn)
{
    $sql = "SELECT id_especialidad, nombre FROM especialidad";
    $result = $conn->query($sql);

    $especialidades = [];
    while ($row = $result->fetch_assoc()) {
        $especialidades[] = [
            'id_especialidad' => $row['id_especialidad'],
            'nombre' => $row['nombre']
        ];
    }

    return $especialidades;
}

function obtenerEntrenamientos($conn)
{
    $sql = "SELECT id_especialidad, nombre FROM especialidad";
    $result = $conn->query($sql);

    $entrenamientos = [];
    while ($row = $result->fetch_assoc()) {
        $entrenamientos[] = [
            'id_especialidad' => $row['id_especialidad'],
            'nombre' => $row['nombre']
        ];
    }

    return $entrenamientos;
}
function obtenerIdMonitor($conn, $id_usuario)
{
    if (!$conn || !$id_usuario) {
        return null;
    }

    try {
        $sql = "SELECT id_monitor FROM monitor WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id_monitor'];
        } else {
            // Registrar un error si no se encuentra el monitor
            error_log("No se encontró un monitor asociado con id_usuario: $id_usuario");
            return null;
        }
    } catch (Exception $e) {
        error_log("Error en obtenerIdMonitor: " . $e->getMessage());
        return null;
    }
}
function obtenerNombreEspecialidad($conn, $id_especialidad)
{
    $sql = "SELECT nombre FROM especialidad WHERE id_especialidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $especialidad = $result->fetch_assoc();

    return $especialidad ? $especialidad['nombre'] : "Especialidad desconocida";
}
function tieneClasesFuturas($conn, $id_monitor, $id_especialidad)
{
    $sql = "SELECT COUNT(*) as total FROM clase 
            WHERE id_monitor = ? AND id_especialidad = ? AND fecha >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_monitor, $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] > 0;
}
