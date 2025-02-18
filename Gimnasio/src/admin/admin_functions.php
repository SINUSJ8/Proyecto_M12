<?php

require_once('../includes/general.php');

// Función para obtener el conteo de miembros
function obtenerConteoMiembros($conn)
{
    $query = $conn->query("SELECT COUNT(*) AS total FROM miembro");
    return $query ? $query->fetch_assoc()['total'] : 0;
}

// Función para obtener el conteo de clases
function obtenerConteoClases($conn)
{
    $query = $conn->query("SELECT COUNT(*) AS total FROM clase");
    return $query ? $query->fetch_assoc()['total'] : 0;
}

// Función para obtener el conteo de monitores
function obtenerConteoMonitores($conn)
{
    $query = $conn->query("SELECT COUNT(*) AS total FROM monitor");
    return $query ? $query->fetch_assoc()['total'] : 0;
}




function agregarEspecialidad($conn, $nombre_especialidad)
{
    if (empty($nombre_especialidad)) {
        return "Por favor, introduce un nombre de especialidad.";
    }

    // Normalizar el nombre a minúsculas para la comparación
    $nombre_normalizado = strtolower($nombre_especialidad);

    // Verificar si la especialidad ya existe (ignorando mayúsculas y minúsculas)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM especialidad WHERE LOWER(nombre) = ?");
    $stmt->bind_param("s", $nombre_normalizado);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        return "Ya existe una especialidad con ese nombre.";
    }

    // Insertar la nueva especialidad si no existe duplicado
    $stmt = $conn->prepare("INSERT INTO especialidad (nombre) VALUES (?)");
    $stmt->bind_param("s", $nombre_especialidad);
    if ($stmt->execute()) {
        $stmt->close();
        return "Especialidad añadida exitosamente.";
    } else {
        $error = "Error al añadir la especialidad: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}

function agregarMembresia($conn, $tipo, $precio, $duracion, $beneficios, $entrenamientos = [])
{
    if (empty($tipo) || $precio <= 0 || $duracion <= 0) {
        return "Todos los campos son obligatorios y deben tener valores válidos.";
    }

    // Normalizar el nombre a minúsculas para comparación
    $tipo_normalizado = strtolower($tipo);

    // Verificar si ya existe una membresía con el mismo nombre (ignorando mayúsculas y minúsculas)
    $stmt = $conn->prepare("SELECT id_membresia FROM membresia WHERE LOWER(tipo) = ?");
    $stmt->bind_param("s", $tipo_normalizado);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();

    if (!empty($existing_id)) {
        return "Ya existe una membresía con ese nombre.";
    }

    // Insertar la nueva membresía
    $stmt = $conn->prepare("INSERT INTO membresia (tipo, precio, duracion, beneficios) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return "Error al preparar la consulta: " . $conn->error;
    }

    $stmt->bind_param("sdis", $tipo, $precio, $duracion, $beneficios);
    if ($stmt->execute()) {
        $id_membresia = $conn->insert_id;
        $stmt->close();

        // Si hay entrenamientos asociados, los insertamos en la tabla membresia_entrenamiento
        if (!empty($entrenamientos)) {
            $stmt = $conn->prepare("INSERT INTO membresia_entrenamiento (id_membresia, id_entrenamiento) VALUES (?, ?)");
            if (!$stmt) {
                return "Error al preparar la consulta de entrenamientos: " . $conn->error;
            }

            foreach ($entrenamientos as $id_entrenamiento) {
                $stmt->bind_param("ii", $id_membresia, $id_entrenamiento);
                if (!$stmt->execute()) {
                    return "Error al insertar entrenamiento: " . $stmt->error;
                }
            }
            $stmt->close();
        }

        return "Membresía añadida exitosamente.";
    } else {
        $error = "Error al añadir la membresía: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}



function editarMembresia($conn, $id_membresia, $tipo, $precio, $duracion, $beneficios, $estado, $entrenamientos = [])
{
    if (empty($tipo) || $precio <= 0 || $duracion <= 0) {
        return "Todos los campos son obligatorios y deben tener valores válidos.";
    }

    // Si la membresía se va a descontinuar, desactivar la renovación automática de los miembros
    if ($estado === "descontinuada") {
        // Desactivar renovación automática
        $stmt = $conn->prepare("UPDATE miembro_membresia SET renovacion_automatica = 0 WHERE id_membresia = ? AND renovacion_automatica = 1");
        $stmt->bind_param("i", $id_membresia);
        if (!$stmt->execute()) {
            return "Error al desactivar la renovación automática: " . $stmt->error;
        }
        $stmt->close();

        // Obtener IDs de miembros afectados
        $stmt = $conn->prepare("SELECT mm.id_miembro, u.id_usuario, u.nombre FROM miembro_membresia mm
                                INNER JOIN miembro m ON mm.id_miembro = m.id_miembro
                                INNER JOIN usuario u ON m.id_usuario = u.id_usuario
                                WHERE mm.id_membresia = ? AND mm.renovacion_automatica = 0");
        $stmt->bind_param("i", $id_membresia);
        $stmt->execute();
        $result = $stmt->get_result();
        $miembros_afectados = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Enviar notificación a cada miembro afectado
        foreach ($miembros_afectados as $miembro) {
            $mensaje = "Estimado/a {$miembro['nombre']}, tu membresía '{$tipo}' ha sido descontinuada y la renovación automática ha sido desactivada. 
                        Por favor, revisa nuestras opciones para elegir una nueva membresía.";
            enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
        }
    }

    // Normalizar el nombre a minúsculas para comparación
    $tipo_normalizado = strtolower($tipo);

    // Verificar si ya existe otra membresía con el mismo nombre
    $stmt = $conn->prepare("SELECT id_membresia FROM membresia WHERE LOWER(tipo) = ? AND id_membresia != ?");
    $stmt->bind_param("si", $tipo_normalizado, $id_membresia);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();

    if (!empty($existing_id)) {
        return "Ya existe otra membresía con ese nombre.";
    }

    // Actualizar membresía
    $stmt = $conn->prepare("UPDATE membresia SET tipo = ?, precio = ?, duracion = ?, beneficios = ?, estado = ? WHERE id_membresia = ?");
    if (!$stmt) {
        return "Error al preparar la consulta: " . $conn->error;
    }

    $stmt->bind_param("sdissi", $tipo, $precio, $duracion, $beneficios, $estado, $id_membresia);

    if ($stmt->execute()) {
        $stmt->close();

        // Eliminar entrenamientos antiguos y agregar los nuevos
        $stmt = $conn->prepare("DELETE FROM membresia_entrenamiento WHERE id_membresia = ?");
        $stmt->bind_param("i", $id_membresia);
        $stmt->execute();
        $stmt->close();

        if (!empty($entrenamientos)) {
            $stmt = $conn->prepare("INSERT INTO membresia_entrenamiento (id_membresia, id_entrenamiento) VALUES (?, ?)");
            if (!$stmt) {
                return "Error al preparar la consulta de entrenamientos: " . $conn->error;
            }

            foreach ($entrenamientos as $id_entrenamiento) {
                $stmt->bind_param("ii", $id_membresia, $id_entrenamiento);
                if (!$stmt->execute()) {
                    return "Error al insertar entrenamiento: " . $stmt->error;
                }
            }
            $stmt->close();
        }

        return "Membresía actualizada exitosamente.";
    } else {
        $error = "Error al actualizar la membresía: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}




function eliminarMembresia($conn, $id_membresia)
{
    // Verificar si hay miembros con esta membresía activa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM miembro WHERE id_membresia = ?");
    $stmt->bind_param("i", $id_membresia);
    $stmt->execute();
    $stmt->bind_result($cantidad_miembros);
    $stmt->fetch();
    $stmt->close();

    // Si hay miembros activos, no permitir la eliminación
    if ($cantidad_miembros > 0) {
        return "No se puede eliminar la membresía porque hay miembros activos con esta membresía.";
    }

    // Verificar si la membresía está en el historial de membresías activas
    $stmt = $conn->prepare("SELECT COUNT(*) FROM miembro_membresia WHERE id_membresia = ? AND estado = 'activa'");
    $stmt->bind_param("i", $id_membresia);
    $stmt->execute();
    $stmt->bind_result($cantidad_historial);
    $stmt->fetch();
    $stmt->close();

    if ($cantidad_historial > 0) {
        return "No se puede eliminar la membresía porque hay registros en el historial con estado activo.";
    }

    // Si no hay miembros con esta membresía, proceder con la eliminación
    $stmt = $conn->prepare("DELETE FROM membresia WHERE id_membresia = ?");
    $stmt->bind_param("i", $id_membresia);

    if ($stmt->execute()) {
        $stmt->close();
        return "Membresía eliminada exitosamente.";
    } else {
        $error = "Error al eliminar la membresía: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}

function eliminarMiembroMembresia($conn, $id_miembro_membresia)
{
    $stmt = $conn->prepare("DELETE FROM miembro_membresia WHERE id = ?");
    $stmt->bind_param("i", $id_miembro_membresia);
    if ($stmt->execute()) {
        $stmt->close();
        return "Registro de membresía del miembro eliminado exitosamente.";
    } else {
        $error = "Error al eliminar el registro: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}

function activarMembresia($conn, $idMembresia)
{
    // Verificar si la membresía existe y obtener la fecha de expiración y el miembro asociado
    $sqlFecha = "
        SELECT mm.fecha_fin, mm.id_miembro, m.id_membresia 
        FROM miembro_membresia mm
        INNER JOIN membresia m ON mm.id_membresia = m.id_membresia
        WHERE mm.id = ?
    ";
    $stmtFecha = $conn->prepare($sqlFecha);
    $stmtFecha->bind_param("i", $idMembresia);
    $stmtFecha->execute();
    $stmtFecha->bind_result($fechaFin, $idMiembro, $idMembresiaReal);
    $stmtFecha->fetch();
    $stmtFecha->close();

    if (!$idMembresiaReal) {
        return false; // No se encontró la membresía
    }

    if (!$fechaFin || strtotime($fechaFin) <= strtotime(date("Y-m-d"))) {
        return false; // La membresía ha expirado
    }

    // Desactivar otras membresías activas del mismo miembro
    $sqlDesactivar = "UPDATE miembro_membresia SET estado = 'expirada' WHERE id_miembro = ? AND estado = 'activa'";
    $stmtDesactivar = $conn->prepare($sqlDesactivar);
    $stmtDesactivar->bind_param("i", $idMiembro);
    $stmtDesactivar->execute();
    $stmtDesactivar->close();

    // Activar la membresía seleccionada
    $sqlActivar = "UPDATE miembro_membresia SET estado = 'activa' WHERE id = ?";
    $stmtActivar = $conn->prepare($sqlActivar);
    $stmtActivar->bind_param("i", $idMembresia);
    $resultado = $stmtActivar->execute();
    $stmtActivar->close();

    if ($resultado) {
        // **1 Eliminar los entrenamientos previos del miembro**
        $sqlEliminarEntrenamientos = "DELETE FROM miembro_entrenamiento WHERE id_miembro = ?";
        $stmtEliminar = $conn->prepare($sqlEliminarEntrenamientos);
        $stmtEliminar->bind_param("i", $idMiembro);
        $stmtEliminar->execute();
        $stmtEliminar->close();

        // ** Insertar los entrenamientos de la nueva membresía**
        $sqlEntrenamientos = "
            INSERT INTO miembro_entrenamiento (id_miembro, id_especialidad)
            SELECT ?, me.id_entrenamiento
            FROM membresia_entrenamiento me
            WHERE me.id_membresia = ?
            ON DUPLICATE KEY UPDATE id_especialidad = id_especialidad;
        ";
        $stmtEntrenamientos = $conn->prepare($sqlEntrenamientos);
        $stmtEntrenamientos->bind_param("ii", $idMiembro, $idMembresiaReal);
        $stmtEntrenamientos->execute();
        $stmtEntrenamientos->close();
    }

    return $resultado ? "Membresía activada exitosamente." : "Error al activar la membresía.";
}



function desactivarMembresia($conn, $idMembresia)
{
    // Obtener el id_miembro y verificar si la membresía existe
    $sqlMiembro = "SELECT id_miembro FROM miembro_membresia WHERE id = ?";
    $stmtMiembro = $conn->prepare($sqlMiembro);
    $stmtMiembro->bind_param("i", $idMembresia);
    $stmtMiembro->execute();
    $stmtMiembro->bind_result($idMiembro);
    $stmtMiembro->fetch();
    $stmtMiembro->close();

    if (!$idMiembro) {
        return "Error: La membresía no existe o ya fue eliminada.";
    }

    // Desactivar la membresía
    $sql = "UPDATE miembro_membresia SET estado = 'expirada' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $idMembresia);
    $resultado = $stmt->execute();
    $stmt->close();

    if ($resultado) {
        // Eliminar los entrenamientos asociados al miembro
        $sqlEliminarEntrenamientos = "DELETE FROM miembro_entrenamiento WHERE id_miembro = ?";
        $stmtEliminarEntrenamientos = $conn->prepare($sqlEliminarEntrenamientos);
        $stmtEliminarEntrenamientos->bind_param("i", $idMiembro);
        $stmtEliminarEntrenamientos->execute();
        $stmtEliminarEntrenamientos->close();
    }

    return $resultado ? "Membresía desactivada exitosamente." : "Error al desactivar la membresía.";
}



function asignarMembresiaAlMiembro($conn, $id_miembro, $id_membresia)
{
    // Obtener información de la membresía para calcular la fecha de expiración y monto pagado
    $stmt = $conn->prepare("SELECT precio, duracion FROM membresia WHERE id_membresia = ?");
    $stmt->bind_param("i", $id_membresia);
    $stmt->execute();
    $stmt->bind_result($precio, $duracion);

    if ($stmt->fetch()) {
        // Calcular fechas
        $fecha_inicio = date("Y-m-d");
        $fecha_fin = date("Y-m-d", strtotime("+$duracion months"));

        // Insertar el registro en la tabla miembro_membresía
        $stmt->close();

        $insert_stmt = $conn->prepare("INSERT INTO miembro_membresía (id_miembro, id_membresia, monto_pagado, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, ?, 'activa')");
        $insert_stmt->bind_param("iisss", $id_miembro, $id_membresia, $precio, $fecha_inicio, $fecha_fin);

        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            return "Membresía asignada exitosamente.";
        } else {
            $error = "Error al asignar la membresía: " . $insert_stmt->error;
            $insert_stmt->close();
            return $error;
        }
    } else {
        $stmt->close();
        return "La membresía especificada no existe.";
    }
}
function buscarUsuariosPorTermino($conn, $termino, $limite = 10)
{
    $sql = "SELECT id_usuario, nombre, email FROM usuario WHERE nombre LIKE ? OR email LIKE ? LIMIT ?";
    $stmt = $conn->prepare($sql);
    $likeTermino = '%' . $termino . '%';
    $stmt->bind_param("ssi", $likeTermino, $likeTermino, $limite);
    $stmt->execute();
    $result = $stmt->get_result();

    $usuarios = [];
    while ($usuario = $result->fetch_assoc()) {
        $usuarios[] = $usuario;
    }

    $stmt->close();
    return $usuarios;
}
function obtenerAltasRecientes($conn, $limite)
{
    $sql = "
        SELECT usuario.nombre, miembro.fecha_registro 
        FROM miembro 
        INNER JOIN usuario ON miembro.id_usuario = usuario.id_usuario 
        ORDER BY miembro.fecha_registro DESC 
        LIMIT ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerAltasDelMes($conn)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM miembro WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE)");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

function obtenerClasesMasPopulares($conn, $limite)
{
    $stmt = $conn->prepare("SELECT clase.nombre, COUNT(asistencia.id_clase) as inscripciones FROM clase LEFT JOIN asistencia ON clase.id_clase = asistencia.id_clase GROUP BY clase.id_clase ORDER BY inscripciones DESC LIMIT ?");
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return !empty($resultado) ? $resultado : []; // Retorna un array vacío si no hay datos
}

function obtenerClaseMaxMiembros($conn)
{
    $stmt = $conn->prepare("SELECT clase.nombre, COUNT(asistencia.id_clase) as miembros FROM clase LEFT JOIN asistencia ON clase.id_clase = asistencia.id_clase GROUP BY clase.id_clase ORDER BY miembros DESC LIMIT 1");
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    return !empty($resultado) ? $resultado : ["nombre" => "No hay clases registradas", "miembros" => 0]; // Retorna valores predeterminados
}


function obtenerIngresosTotales($conn)
{
    $stmt = $conn->prepare("SELECT SUM(monto_pagado) as total FROM miembro_membresia");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

function obtenerIngresosDelMes($conn)
{
    $stmt = $conn->prepare("SELECT SUM(monto_pagado) as total FROM miembro_membresia WHERE MONTH(fecha_inicio) = MONTH(CURRENT_DATE) AND YEAR(fecha_inicio) = YEAR(CURRENT_DATE)");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

function obtenerMonitoresActivos($conn)
{
    $stmt = $conn->prepare("SELECT nombre, disponibilidad FROM monitor INNER JOIN usuario ON monitor.id_usuario = usuario.id_usuario WHERE disponibilidad = 'disponible'");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function obtenerMiembrosConEspecialidad($conn, $id_especialidad)
{
    $sql = "
        SELECT u.id_usuario, u.nombre, e.nombre AS especialidad
        FROM miembro_entrenamiento me
        INNER JOIN miembro m ON me.id_miembro = m.id_miembro
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad
        WHERE me.id_especialidad = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();

    $miembros = [];
    while ($row = $result->fetch_assoc()) {
        $miembros[] = $row;  // Guardamos en un array incluyendo 'especialidad'
    }

    $stmt->close();
    return $miembros;  // Siempre devolvemos un array
}


function obtenerMonitoresConEspecialidad($conn, $id_especialidad)
{
    $sql = "
        SELECT u.id_usuario, u.nombre, e.nombre AS especialidad
        FROM monitor_especialidad me
        INNER JOIN monitor m ON me.id_monitor = m.id_monitor
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad
        WHERE me.id_especialidad = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();

    $monitores = [];
    while ($row = $result->fetch_assoc()) {
        $monitores[] = $row;  // Guardamos en un array incluyendo 'especialidad'
    }

    $stmt->close();
    return $monitores;  // Siempre devolvemos un array
}

function editarEspecialidadConNotificaciones($conn, $id_especialidad, $nuevo_nombre)
{
    if (empty($nuevo_nombre)) {
        return "Por favor, introduce un nombre de especialidad.";
    }

    // Normalizar el nombre a minúsculas para la comparación
    $nombre_normalizado = strtolower($nuevo_nombre);

    // Verificar si ya existe otra especialidad con el mismo nombre (ignorando mayúsculas y minúsculas)
    $stmt = $conn->prepare("SELECT id_especialidad FROM especialidad WHERE LOWER(nombre) = ? AND id_especialidad != ?");
    $stmt->bind_param("si", $nombre_normalizado, $id_especialidad);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();

    if (!empty($existing_id)) {
        return "Ya existe otra especialidad con ese nombre.";
    }

    // Obtener las clases asociadas a la especialidad
    $sql = "SELECT id_clase, nombre FROM clase WHERE id_especialidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Obtener los miembros con esta especialidad
    $miembros = obtenerMiembrosConEspecialidad($conn, $id_especialidad);
    // Obtener los monitores con esta especialidad
    $monitores = obtenerMonitoresConEspecialidad($conn, $id_especialidad);

    // Notificar a los miembros y monitores sobre el cambio
    foreach ($miembros as $miembro) {
        enviarNotificacion($conn, $miembro['id_usuario'], "La especialidad '{$miembro['especialidad']}' ha cambiado de nombre a '{$nuevo_nombre}'");
    }
    foreach ($monitores as $monitor) {
        enviarNotificacion($conn, $monitor['id_usuario'], "La especialidad '{$monitor['especialidad']}' que impartes ha cambiado de nombre a '{$nuevo_nombre}'");
    }

    // Notificar a los inscritos en clases relacionadas
    foreach ($clases as $clase) {
        $id_clase = $clase['id_clase'];
        $nombre_clase = $clase['nombre'];
        $inscritos = obtenerMiembrosInscritos($conn, $id_clase);
        foreach ($inscritos as $inscrito) {
            enviarNotificacion($conn, $inscrito['id_usuario'], "La especialidad de la clase '{$nombre_clase}' ha cambiado de nombre a '{$nuevo_nombre}'");
        }
    }

    // Intentar actualizar la especialidad
    try {
        $stmt = $conn->prepare("UPDATE especialidad SET nombre = ? WHERE id_especialidad = ?");
        $stmt->bind_param("si", $nuevo_nombre, $id_especialidad);
        if ($stmt->execute()) {
            $stmt->close();
            return "Especialidad actualizada correctamente.";
        } else {
            $stmt->close();
            return "Error al actualizar la especialidad.";
        }
    } catch (mysqli_sql_exception $e) {
        return "Error al actualizar la especialidad: " . $e->getMessage();
    }
}


function eliminarEspecialidadConNotificaciones($conn, $id_especialidad)
{
    // Obtener clases asociadas
    $sql = "SELECT id_clase, nombre FROM clase WHERE id_especialidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Obtener los miembros con esta especialidad
    $miembros = obtenerMiembrosConEspecialidad($conn, $id_especialidad);
    // Obtener los monitores con esta especialidad
    $monitores = obtenerMonitoresConEspecialidad($conn, $id_especialidad);

    // Notificar a los afectados
    foreach ($miembros as $miembro) {
        enviarNotificacion($conn, $miembro['id_usuario'], "La especialidad '{$miembro['especialidad']}' ha sido eliminada");
    }
    foreach ($monitores as $monitor) {
        enviarNotificacion($conn, $monitor['id_usuario'], "La especialidad '{$monitor['especialidad']}' que impartías ha sido eliminada y todas sus clases");
    }

    // Notificar y eliminar clases asociadas
    foreach ($clases as $clase) {
        $id_clase = $clase['id_clase'];
        $nombre_clase = $clase['nombre'];
        $inscritos = obtenerMiembrosInscritos($conn, $id_clase);
        foreach ($inscritos as $inscrito) {
            enviarNotificacion($conn, $inscrito['id_usuario'], "La clase '{$nombre_clase}' ha sido eliminada debido a la eliminación de su especialidad");
        }
        $stmt = $conn->prepare("DELETE FROM clase WHERE id_clase = ?");
        $stmt->bind_param("i", $id_clase);
        $stmt->execute();
        $stmt->close();
    }

    // Eliminar la especialidad de miembros y monitores
    $stmt = $conn->prepare("DELETE FROM miembro_entrenamiento WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM monitor_especialidad WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $stmt->close();

    // Eliminar la especialidad
    $stmt = $conn->prepare("DELETE FROM especialidad WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    if ($stmt->execute()) {
        $stmt->close();
        return "Especialidad eliminada correctamente.";
    } else {
        $stmt->close();
        return "Error al eliminar la especialidad.";
    }
}
