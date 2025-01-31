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

// Función para obtener las notificaciones
function obtenerNotificaciones($conn, $id_usuario, $limit = 5, $soloNoLeidas = true)
{
    $sql = "SELECT * FROM notificacion WHERE id_usuario = ?";
    if ($soloNoLeidas) {
        $sql .= " AND leida = 0";
    }
    $sql .= " ORDER BY fecha DESC LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notificaciones;
}



function agregarEspecialidad($conn, $nombre_especialidad)
{
    if (empty($nombre_especialidad)) {
        return "Por favor, introduce un nombre de especialidad.";
    }
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

function editarEspecialidad($conn, $id_especialidad, $nombre_especialidad)
{
    if (empty($nombre_especialidad)) {
        return "Por favor, introduce un nombre de especialidad.";
    }
    $stmt = $conn->prepare("UPDATE especialidad SET nombre = ? WHERE id_especialidad = ?");
    $stmt->bind_param("si", $nombre_especialidad, $id_especialidad);
    if ($stmt->execute()) {
        $stmt->close();
        return "Especialidad actualizada exitosamente.";
    } else {
        $error = "Error al actualizar la especialidad: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}

function eliminarEspecialidad($conn, $id_especialidad)
{
    $stmt = $conn->prepare("DELETE FROM especialidad WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    if ($stmt->execute()) {
        $stmt->close();
        return "Especialidad eliminada exitosamente.";
    } else {
        $error = "Error al eliminar la especialidad: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}


function agregarMembresia($conn, $tipo, $precio, $duracion, $beneficios, $entrenamientos = [])
{
    if (empty($tipo) || $precio <= 0 || $duracion <= 0) {
        return "Todos los campos son obligatorios y deben tener valores válidos.";
    }

    $stmt = $conn->prepare("INSERT INTO membresia (tipo, precio, duracion, beneficios) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return "Error al preparar la consulta: " . $conn->error;
    }

    $stmt->bind_param("sdis", $tipo, $precio, $duracion, $beneficios);
    if ($stmt->execute()) {
        $id_membresia = $conn->insert_id;
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

        return "Membresía añadida exitosamente.";
    } else {
        $error = "Error al añadir la membresía: " . $stmt->error;
        $stmt->close();
        return $error;
    }
}



function editarMembresia($conn, $id_membresia, $tipo, $precio, $duracion, $beneficios, $estado, $entrenamientos = [])
{
    $stmt = $conn->prepare("UPDATE membresia SET tipo = ?, precio = ?, duracion = ?, beneficios = ?, estado = ? WHERE id_membresia = ?");
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
            foreach ($entrenamientos as $id_entrenamiento) {
                $stmt->bind_param("ii", $id_membresia, $id_entrenamiento);
                $stmt->execute();
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
    // Verificar si la fecha de expiración es posterior a la fecha actual
    $sqlFecha = "SELECT fecha_fin, id_miembro FROM miembro_membresia WHERE id = ?";
    $stmtFecha = $conn->prepare($sqlFecha);
    $stmtFecha->bind_param("i", $idMembresia);
    $stmtFecha->execute();
    $stmtFecha->bind_result($fechaFin, $idMiembro);
    $stmtFecha->fetch();
    $stmtFecha->close();

    if (!$fechaFin || strtotime($fechaFin) <= strtotime(date("Y-m-d"))) {
        // Fecha de expiración inválida o pasada
        return false;
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

    return $resultado;
}


function desactivarMembresia($conn, $idMembresia)
{
    $sql = "UPDATE miembro_membresia SET estado = 'expirada' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $idMembresia);
    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
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
// Función para manejar la eliminación con notificaciones
function eliminarEspecialidadConNotificaciones($conn, $id_especialidad)
{
    // Obtener las clases asociadas a la especialidad
    $sql = "SELECT id_clase, nombre FROM clase WHERE id_especialidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($clases)) {
        return "No se encontraron clases asociadas a la especialidad.";
    }

    // Enviar notificaciones a los miembros inscritos y al monitor de cada clase
    foreach ($clases as $clase) {
        $id_clase = $clase['id_clase'];
        $nombre_clase = $clase['nombre'];

        // Obtener miembros inscritos
        $miembros = obtenerMiembrosInscritos($conn, $id_clase);
        foreach ($miembros as $miembro) {
            $mensaje = "La especialidad y la clase '{$nombre_clase}' a la que estabas inscrito han sido eliminadas. Por favor, revisa las opciones disponibles.";
            enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
        }

        // Obtener el monitor de la clase
        $monitor = obtenerMonitorDeClase($conn, $id_clase);
        if ($monitor) {
            $mensaje = "La clase '{$nombre_clase}' que impartías ha sido eliminada debido a la eliminación de su especialidad asociada.";
            enviarNotificacion($conn, $monitor['id_usuario'], $mensaje);
        }
    }

    // Eliminar las clases asociadas
    $stmt = $conn->prepare("DELETE FROM clase WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $stmt->close();

    // Eliminar la especialidad
    $stmt = $conn->prepare("DELETE FROM especialidad WHERE id_especialidad = ?");
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $stmt->close();

    return "La especialidad y sus clases asociadas se han eliminado correctamente. Las notificaciones han sido enviadas.";
}
// Función para editar una especialidad y notificar a los usuarios afectados
function editarEspecialidadConNotificaciones($conn, $id_especialidad, $nuevo_nombre)
{
    // Obtener las clases asociadas a la especialidad
    $sql = "SELECT id_clase, nombre FROM clase WHERE id_especialidad = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_especialidad);
    $stmt->execute();
    $result = $stmt->get_result();
    $clases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($clases)) {
        return "No se encontraron clases asociadas a la especialidad.";
    }

    // Enviar notificaciones a los miembros y monitores de las clases asociadas
    foreach ($clases as $clase) {
        $id_clase = $clase['id_clase'];
        $nombre_clase = $clase['nombre'];

        // Obtener miembros inscritos
        $miembros = obtenerMiembrosInscritos($conn, $id_clase);
        foreach ($miembros as $miembro) {
            $mensaje = "La especialidad asociada a la clase '{$nombre_clase}' ha cambiado de nombre a '{$nuevo_nombre}'. Por favor, revisa la información actualizada.";
            enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
        }

        // Obtener el monitor de la clase
        $monitor = obtenerMonitorDeClase($conn, $id_clase);
        if ($monitor) {
            $mensaje = "La especialidad de la clase '{$nombre_clase}' que impartes ha cambiado de nombre a '{$nuevo_nombre}'. Por favor, revisa la información actualizada.";
            enviarNotificacion($conn, $monitor['id_usuario'], $mensaje);
        }
    }

    // Actualizar el nombre de la especialidad
    $stmt = $conn->prepare("UPDATE especialidad SET nombre = ? WHERE id_especialidad = ?");
    $stmt->bind_param("si", $nuevo_nombre, $id_especialidad);
    if ($stmt->execute()) {
        $stmt->close();
        return "La especialidad se ha actualizado correctamente. Las notificaciones han sido enviadas.";
    } else {
        $stmt->close();
        return "Error al actualizar la especialidad.";
    }
}
