<?php

require_once('../includes/general.php');


// Obtiene todos los miembros de la base de datos
function obtenerMiembros($conn, $busqueda = '', $orden_columna = 'nombre', $orden_direccion = 'ASC')
{
    // Validar columnas y dirección para evitar inyecciones SQL
    $columnas_validas = ['nombre', 'email', 'fecha_registro', 'tipo', 'precio', 'duracion'];
    $direccion_valida = ['ASC', 'DESC'];

    if (!in_array($orden_columna, $columnas_validas)) {
        $orden_columna = 'nombre';
    }
    if (!in_array($orden_direccion, $direccion_valida)) {
        $orden_direccion = 'ASC';
    }

    // Construir la consulta SQL para obtener los miembros con su membresía activa y entrenamientos
    $sql = "
        SELECT 
            u.id_usuario, 
            u.nombre, 
            u.email, 
            m.fecha_registro, 
            mb.tipo AS tipo, 
            mb.precio, 
            mb.duracion,
            GROUP_CONCAT(e.nombre SEPARATOR ', ') AS entrenamientos
        FROM usuario u
        INNER JOIN miembro m ON u.id_usuario = m.id_usuario
        LEFT JOIN (
            SELECT id_miembro, id_membresia
            FROM miembro_membresia
            WHERE estado = 'activa'
        ) mm ON m.id_miembro = mm.id_miembro
        LEFT JOIN membresia mb ON mm.id_membresia = mb.id_membresia
        LEFT JOIN miembro_entrenamiento me ON m.id_miembro = me.id_miembro
        LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad
    ";

    // Agregar filtro de búsqueda si se proporciona un término
    if ($busqueda) {
        $sql .= " WHERE u.nombre LIKE ? OR u.email LIKE ?";
    }

    // Agregar agrupación y ordenamiento
    $sql .= " GROUP BY u.id_usuario ORDER BY $orden_columna $orden_direccion";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    if ($busqueda) {
        $busqueda_param = '%' . $busqueda . '%';
        $stmt->bind_param("ss", $busqueda_param, $busqueda_param);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Devolver los resultados como un array asociativo
    $miembros = [];
    while ($row = $result->fetch_assoc()) {
        $miembros[] = $row;
    }

    $stmt->close();
    return $miembros;
}




function eliminarMiembro($conn, $id_usuario)
{
    // Iniciar una transacción para eliminar de múltiples tablas si es necesario
    $conn->begin_transaction();

    try {
        // Eliminar de la tabla miembro
        $stmt = $conn->prepare("DELETE FROM miembro WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // También eliminar el registro de usuario si se desea eliminar completamente
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Confirmar la transacción
        $conn->commit();
        return ["success" => true, "message" => "Miembro eliminado correctamente."];
    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        $conn->rollback();
        return ["success" => false, "message" => "Error al eliminar el miembro: " . $e->getMessage()];
    }
}
function obtenerMiembroPorID($conn, $id_usuario)
{
    // Consultar datos básicos del miembro, incluyendo la membresía
    $sql = "SELECT u.id_usuario, u.nombre, u.email, u.rol, m.fecha_registro, m.id_membresia, m.id_miembro, mb.tipo AS tipo_membresia
            FROM usuario u
            INNER JOIN miembro m ON u.id_usuario = m.id_usuario
            LEFT JOIN membresia mb ON m.id_membresia = mb.id_membresia
            WHERE u.id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $miembro = $result->fetch_assoc();
    $stmt->close();

    if (!$miembro) {
        return null; // Miembro no encontrado
    }

    // Obtener los entrenamientos asociados
    $sql = "SELECT e.id_especialidad
            FROM miembro_entrenamiento me
            INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad
            WHERE me.id_miembro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $miembro['id_miembro']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Guardar los IDs de entrenamientos en un array
    $entrenamientos = [];
    while ($row = $result->fetch_assoc()) {
        $entrenamientos[] = $row['id_especialidad'];
    }
    $stmt->close();

    $miembro['entrenamientos'] = $entrenamientos;

    return $miembro;
}



function actualizarMiembro($conn, $id_usuario, $nombre, $email, $fecha_registro, $id_membresia)
{
    $conn->begin_transaction();

    try {
        // Actualizar los datos del usuario
        $sqlUsuario = "UPDATE usuario SET nombre = ?, email = ? WHERE id_usuario = ?";
        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->bind_param("ssi", $nombre, $email, $id_usuario);
        $stmtUsuario->execute();

        // Obtener el id_miembro relacionado con el id_usuario
        $sqlMiembro = "SELECT id_miembro FROM miembro WHERE id_usuario = ?";
        $stmtMiembro = $conn->prepare($sqlMiembro);
        $stmtMiembro->bind_param("i", $id_usuario);
        $stmtMiembro->execute();
        $resultadoMiembro = $stmtMiembro->get_result();

        if ($resultadoMiembro->num_rows === 0) {
            throw new Exception("Miembro no encontrado para este usuario.");
        }

        $id_miembro = $resultadoMiembro->fetch_assoc()['id_miembro'];

        // Actualizar la membresía en la tabla miembro
        $sqlActualizarMiembro = "UPDATE miembro SET fecha_registro = ?, id_membresia = ? WHERE id_miembro = ?";
        $stmtActualizarMiembro = $conn->prepare($sqlActualizarMiembro);
        $stmtActualizarMiembro->bind_param("sii", $fecha_registro, $id_membresia, $id_miembro);
        $stmtActualizarMiembro->execute();

        // Cambiar el estado de la membresía actual a "no activa"
        $queryActualizarEstado = "UPDATE miembro_membresia 
                                  SET estado = 'no activa' 
                                  WHERE id_miembro = ? AND estado = 'activa'";
        $stmtActualizarEstado = $conn->prepare($queryActualizarEstado);
        $stmtActualizarEstado->bind_param("i", $id_miembro);
        if (!$stmtActualizarEstado->execute()) {
            throw new Exception("Error al desactivar la membresía actual: " . $stmtActualizarEstado->error);
        }
        $stmtActualizarEstado->close();

        // Registrar el cambio en la tabla miembro_membresia
        $fecha_actual = date('Y-m-d');
        $sqlPrecioMembresia = "SELECT precio, duracion FROM membresia WHERE id_membresia = ?";
        $stmtPrecioMembresia = $conn->prepare($sqlPrecioMembresia);
        $stmtPrecioMembresia->bind_param("i", $id_membresia);
        $stmtPrecioMembresia->execute();
        $resultadoPrecio = $stmtPrecioMembresia->get_result();

        if ($resultadoPrecio->num_rows > 0) {
            $membresia = $resultadoPrecio->fetch_assoc();
            $monto_pagado = $membresia['precio'];
            $duracion_meses = $membresia['duracion'];
            $fecha_fin = date('Y-m-d', strtotime("+$duracion_meses months", strtotime($fecha_actual)));

            $sqlInsertarMembresia = "
                INSERT INTO miembro_membresia (id_miembro, id_membresia, monto_pagado, fecha_inicio, fecha_fin, estado, renovacion_automatica) 
                VALUES (?, ?, ?, ?, ?, 'activa', FALSE)
            ";
            $stmtInsertarMembresia = $conn->prepare($sqlInsertarMembresia);
            $stmtInsertarMembresia->bind_param("iisss", $id_miembro, $id_membresia, $monto_pagado, $fecha_actual, $fecha_fin);
            $stmtInsertarMembresia->execute();
        } else {
            throw new Exception("Membresía no encontrada.");
        }

        // Eliminar los entrenamientos anteriores del miembro
        $sqlBorrarEntrenamientos = "DELETE FROM miembro_entrenamiento WHERE id_miembro = ?";
        $stmtBorrarEntrenamientos = $conn->prepare($sqlBorrarEntrenamientos);
        $stmtBorrarEntrenamientos->bind_param("i", $id_miembro);
        $stmtBorrarEntrenamientos->execute();

        // Obtener los nuevos entrenamientos de la membresía seleccionada
        if (!is_null($id_membresia)) {
            $sqlEntrenamientosMembresia = "
                SELECT id_entrenamiento 
                FROM membresia_entrenamiento 
                WHERE id_membresia = ?
            ";
            $stmtEntrenamientosMembresia = $conn->prepare($sqlEntrenamientosMembresia);
            $stmtEntrenamientosMembresia->bind_param("i", $id_membresia);
            $stmtEntrenamientosMembresia->execute();
            $resultadoEntrenamientos = $stmtEntrenamientosMembresia->get_result();

            $sqlInsertarEntrenamiento = "
                INSERT INTO miembro_entrenamiento (id_miembro, id_especialidad) 
                VALUES (?, ?)
            ";
            $stmtInsertarEntrenamiento = $conn->prepare($sqlInsertarEntrenamiento);

            while ($entrenamiento = $resultadoEntrenamientos->fetch_assoc()) {
                $id_especialidad = $entrenamiento['id_entrenamiento']; // Asigna id_entrenamiento a id_especialidad
                $stmtInsertarEntrenamiento->bind_param("ii", $id_miembro, $id_especialidad);
                $stmtInsertarEntrenamiento->execute();
            }
        }

        // Confirmar la transacción
        $conn->commit();

        return ['success' => true];
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}





function obtenerEntrenamientos($conn)
{
    $sql = "SELECT id_especialidad, nombre FROM especialidad";
    $result = $conn->query($sql);

    $entrenamientos = [];
    while ($row = $result->fetch_assoc()) {
        $entrenamientos[] = $row;
    }

    return $entrenamientos;
}
function actualizarEntrenamientosMiembro($conn, $id_miembro, $entrenamientos)
{
    // Inicializar $count para evitar advertencias en el editor
    $count = 0;
    // Primero, validar que el id_miembro existe en la tabla miembro
    $stmt = $conn->prepare("SELECT COUNT(*) FROM miembro WHERE id_miembro = ?");
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        throw new Exception("El miembro con ID $id_miembro no existe.");
    }

    // Eliminar entrenamientos actuales
    $stmt = $conn->prepare("DELETE FROM miembro_entrenamiento WHERE id_miembro = ?");
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $stmt->close();

    // Insertar los nuevos entrenamientos
    $stmt = $conn->prepare("INSERT INTO miembro_entrenamiento (id_miembro, id_especialidad) VALUES (?, ?)");
    foreach ($entrenamientos as $id_especialidad) {
        $stmt->bind_param("ii", $id_miembro, $id_especialidad);
        $stmt->execute();
    }
    $stmt->close();
}

function obtenerMembresias($conn)
{
    $sql = "
        SELECT 
            m.id_membresia, 
            m.tipo, 
            m.precio, 
            m.duracion, 
            m.beneficios,
            GROUP_CONCAT(me.id_entrenamiento) AS entrenamientos_ids
        FROM membresia m
        LEFT JOIN membresia_entrenamiento me ON m.id_membresia = me.id_membresia
        GROUP BY m.id_membresia
    ";

    $result = $conn->query($sql);

    $membresias = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir los IDs de entrenamientos en un array
        $row['entrenamientos_ids'] = $row['entrenamientos_ids'] ? explode(',', $row['entrenamientos_ids']) : [];
        $membresias[] = $row;
    }

    return $membresias;
}

function obtenerIdMiembroPorUsuario($conn, $id_usuario)
{
    $sql = "SELECT id_miembro FROM miembro WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $miembro = $result->fetch_assoc();
    $stmt->close();
    return isset($miembro['id_miembro']) ? $miembro['id_miembro'] : null;
}
function obtenerFechasMembresiaActiva($conn, $id_miembro)
{
    $query = "SELECT fecha_inicio, fecha_fin 
              FROM miembro_membresia 
              WHERE id_miembro = ? AND estado = 'activa' 
              ORDER BY fecha_inicio DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_miembro);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}
function obtenerInformacionMiembro($id_usuario)
{
    $conexion = obtenerConexion();

    // Consulta para obtener los datos básicos del miembro
    $sql = "
        SELECT 
            u.nombre AS nombre_usuario,
            u.email,
            u.telefono,
            u.fecha_creacion,
            m.fecha_registro,
            mm.fecha_inicio,
            mm.fecha_fin,
            mem.tipo AS tipo_membresia
        FROM usuario u
        LEFT JOIN miembro m ON u.id_usuario = m.id_usuario
        LEFT JOIN miembro_membresia mm ON m.id_miembro = mm.id_miembro AND mm.estado = 'activa'
        LEFT JOIN membresia mem ON mm.id_membresia = mem.id_membresia
        WHERE u.id_usuario = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    } else {
        return null; // Retornar null si no se encuentra información
    }
}

function informacionMembresia($id_usuario)
{
    $conexion = obtenerConexion();

    // Consulta principal para obtener datos del miembro y membresía activa
    $sql = "
        SELECT 
            u.nombre AS nombre_usuario,
            u.email,
            m.fecha_registro,
            mem.tipo AS nombre_membresia,
            mm.fecha_inicio,
            mm.fecha_fin,
            mm.estado,
            mm.renovacion_automatica,
            mm.monto_pagado AS monto_pago,
            (
                SELECT p.metodo_pago 
                FROM pago p 
                WHERE p.id_miembro = m.id_miembro 
                ORDER BY p.fecha_pago DESC 
                LIMIT 1
            ) AS metodo_pago,
            (
                SELECT p.fecha_pago 
                FROM pago p 
                WHERE p.id_miembro = m.id_miembro 
                ORDER BY p.fecha_pago DESC 
                LIMIT 1
            ) AS fecha_pago
        FROM usuario u
        LEFT JOIN miembro m ON u.id_usuario = m.id_usuario
        LEFT JOIN miembro_membresia mm ON m.id_miembro = mm.id_miembro AND mm.estado = 'activa'
        LEFT JOIN membresia mem ON mm.id_membresia = mem.id_membresia
        WHERE u.id_usuario = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $datosMiembro = $resultado->fetch_assoc();

    // Obtener las especialidades asignadas al miembro
    $sqlEspecialidades = "
        SELECT e.nombre AS especialidad
        FROM miembro_entrenamiento me
        INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad
        WHERE me.id_miembro = (
            SELECT id_miembro FROM miembro WHERE id_usuario = ?
        )
    ";

    $stmtEspecialidades = $conexion->prepare($sqlEspecialidades);
    $stmtEspecialidades->bind_param("i", $id_usuario);
    $stmtEspecialidades->execute();
    $resultadoEspecialidades = $stmtEspecialidades->get_result();

    $especialidades = [];
    while ($fila = $resultadoEspecialidades->fetch_assoc()) {
        $especialidades[] = $fila['especialidad'];
    }

    $datosMiembro['especialidades'] = $especialidades;

    $stmtEspecialidades->close();
    $stmt->close();
    $conexion->close();

    return $datosMiembro;
}
function obtenerTotalMiembros($conn)
{
    $sql = "SELECT COUNT(*) AS total FROM miembro";
    $result = $conn->query($sql);
    return $result->fetch_assoc()['total'];
}
function obtenerMiembrosPaginados($conn, $limit, $offset)
{
    $sql = "SELECT 
                u.nombre, 
                u.email, 
                m.fecha_registro, 
                mb.tipo AS tipo_membresia, 
                GROUP_CONCAT(DISTINCT e.nombre SEPARATOR ', ') AS entrenamientos,
                m.id_usuario,
                mm.fecha_inicio AS membresia_inicio,
                mm.fecha_fin AS membresia_fin
            FROM miembro m
            JOIN usuario u ON m.id_usuario = u.id_usuario
            LEFT JOIN miembro_membresia mm ON mm.id_miembro = m.id_miembro AND mm.estado = 'activa'
            LEFT JOIN membresia mb ON mm.id_membresia = mb.id_membresia
            LEFT JOIN miembro_entrenamiento me ON me.id_miembro = m.id_miembro
            LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad
            GROUP BY m.id_miembro
            ORDER BY u.nombre ASC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}




function obtenerDetalleCompletoMiembro($conn, $id_usuario)
{
    $miembro = obtenerMiembroPorID($conn, $id_usuario);
    if ($miembro) {
        $miembro['id_miembro'] = obtenerIdMiembroPorUsuario($conn, $id_usuario);

        // Obtener la membresía activa más reciente
        $sql = "SELECT id_membresia, fecha_inicio, fecha_fin 
                FROM miembro_membresia 
                WHERE id_miembro = ? AND estado = 'activa' 
                ORDER BY fecha_inicio DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $miembro['id_miembro']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $miembro['id_membresia'] = $row['id_membresia'];
            $miembro['fechas_membresia'] = ['inicio' => $row['fecha_inicio'], 'fin' => $row['fecha_fin']];
        } else {
            $miembro['id_membresia'] = null;
            $miembro['fechas_membresia'] = null;
        }
    }
    return $miembro;
}
function obtenerNombresEntrenamientos($conn, $ids_entrenamientos)
{
    if (empty($ids_entrenamientos)) {
        return [];
    }

    // Convertir los IDs a una lista separada por comas para la consulta
    $placeholders = implode(',', array_fill(0, count($ids_entrenamientos), '?'));
    $sql = "SELECT nombre FROM especialidad WHERE id_especialidad IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids_entrenamientos)), ...$ids_entrenamientos);
    $stmt->execute();
    $result = $stmt->get_result();

    $nombres = [];
    while ($row = $result->fetch_assoc()) {
        $nombres[] = $row['nombre'];
    }

    $stmt->close();
    return $nombres;
}

function actualizarMembresia($conn, $id_miembro, $id_membresia_nueva, $fecha_inicio_nueva = null, $fecha_fin_nueva = null)
{
    $stmt = $conn->prepare("SELECT precio, duracion FROM membresia WHERE id_membresia = ?");
    $stmt->bind_param("i", $id_membresia_nueva);
    $stmt->execute();
    $stmt->bind_result($precio, $duracion);

    if (!$stmt->fetch()) {
        $stmt->close();
        return ["success" => false, "message" => "Membresía no encontrada."];
    }
    $stmt->close();

    $fecha_inicio = isset($fecha_inicio_nueva) ? $fecha_inicio_nueva : date("Y-m-d");
    $fecha_fin = isset($fecha_fin_nueva) ? $fecha_fin_nueva : date("Y-m-d", strtotime("+$duracion months"));

    // Cambiar el estado de las membresías anteriores a "no activa"
    $queryActualizarEstado = "UPDATE miembro_membresia 
                              SET estado = 'no activa' 
                              WHERE id_miembro = ? AND estado = 'activa'";
    $stmtActualizarEstado = $conn->prepare($queryActualizarEstado);
    $stmtActualizarEstado->bind_param("i", $id_miembro);
    if (!$stmtActualizarEstado->execute()) {
        return ["success" => false, "message" => "Error al desactivar la membresía actual: " . $stmtActualizarEstado->error];
    }
    $stmtActualizarEstado->close();

    // Registrar la nueva membresía activa
    $query = "INSERT INTO miembro_membresia (id_miembro, id_membresia, monto_pagado, fecha_inicio, fecha_fin, estado)
              VALUES (?, ?, ?, ?, ?, 'activa')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $id_miembro, $id_membresia_nueva, $precio, $fecha_inicio, $fecha_fin);

    if (!$stmt->execute()) {
        return ["success" => false, "message" => "Error al registrar la membresía: " . $stmt->error];
    }

    return ["success" => true, "message" => "Membresía actualizada correctamente."];
}

function obtenerUsuariosSinFiltro($conn)
{
    $sql = "SELECT id_usuario, nombre, email FROM usuario ORDER BY nombre ASC";
    $result = $conn->query($sql);

    $usuarios = [];
    if ($result) {
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
    }
    return $usuarios;
}
function obtenerUsuarioPorId($conn, $id_usuario)
{
    $sql = "SELECT id_usuario, nombre, email FROM usuario WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $usuario = [];
    if ($result) {
        $usuario = $result->fetch_assoc();
    }
    $stmt->close();

    return $usuario ? [$usuario] : []; // Devolver un array con un solo usuario
}
function obtenerUsuariosPorRol($conn, $rol)
{
    $sql = "SELECT id_usuario, nombre, email FROM usuario WHERE rol = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $result = $stmt->get_result();

    $usuarios = [];
    if ($result) {
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    return $usuarios;
}
function obtenerNotificacionesPorUsuario($conn, $id_usuario)
{
    $sql = "SELECT mensaje, fecha, leida 
            FROM notificacion 
            WHERE id_usuario = ? 
            ORDER BY fecha DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    if ($result) {
        $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    return $notificaciones;
}
function marcarNotificacionesComoLeidas($conn, $id_usuario)
{
    $sql = "UPDATE notificacion SET leida = 1 WHERE id_usuario = ? AND leida = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();
}
function actualizarPreferenciasMembresia($id_usuario, $renovacion_automatica, $metodo_pago)
{
    $conn = obtenerConexion();

    // Obtener el ID del miembro
    $query_miembro = "SELECT id_miembro FROM miembro WHERE id_usuario = ?";
    $stmt_miembro = $conn->prepare($query_miembro);
    $stmt_miembro->bind_param("i", $id_usuario);
    $stmt_miembro->execute();
    $result_miembro = $stmt_miembro->get_result();
    $miembro = $result_miembro->fetch_assoc();
    $id_miembro = $miembro['id_miembro'] ?? null;
    $stmt_miembro->close();

    if (!$id_miembro) {
        return "No se encontró información del miembro.";
    }

    // Obtener el ID de la membresía activa
    $query_membresia_activa = "SELECT id FROM miembro_membresia WHERE id_miembro = ? AND estado = 'activa'";
    $stmt_membresia_activa = $conn->prepare($query_membresia_activa);
    $stmt_membresia_activa->bind_param("i", $id_miembro);
    $stmt_membresia_activa->execute();
    $result_membresia_activa = $stmt_membresia_activa->get_result();
    $membresia_activa = $result_membresia_activa->fetch_assoc();
    $id_membresia_activa = $membresia_activa['id'] ?? null;
    $stmt_membresia_activa->close();

    if (!$id_membresia_activa) {
        return "No hay una membresía activa.";
    }

    $success = true;

    // Actualizar la renovación automática
    if ($renovacion_automatica !== null) {
        $query_renovacion = "UPDATE miembro_membresia SET renovacion_automatica = ? WHERE id = ?";
        $stmt_renovacion = $conn->prepare($query_renovacion);
        $stmt_renovacion->bind_param("ii", $renovacion_automatica, $id_membresia_activa);
        $success = $stmt_renovacion->execute() && $success;
        $stmt_renovacion->close();
    }

    // Actualizar el método de pago más reciente
    if ($metodo_pago !== null) {
        $query_pago_existente = "
            SELECT id_pago 
            FROM pago 
            WHERE id_miembro = ? 
            ORDER BY fecha_pago DESC 
            LIMIT 1
        ";
        $stmt_pago_existente = $conn->prepare($query_pago_existente);
        $stmt_pago_existente->bind_param("i", $id_miembro);
        $stmt_pago_existente->execute();
        $result_pago_existente = $stmt_pago_existente->get_result();
        $pago_existente = $result_pago_existente->fetch_assoc();
        $stmt_pago_existente->close();

        if ($pago_existente) {
            $query_actualizar_pago = "UPDATE pago SET metodo_pago = ? WHERE id_pago = ?";
            $stmt_actualizar_pago = $conn->prepare($query_actualizar_pago);
            $stmt_actualizar_pago->bind_param("si", $metodo_pago, $pago_existente['id_pago']);
            $success = $stmt_actualizar_pago->execute() && $success;
            $stmt_actualizar_pago->close();
        } else {
            $query_insertar_pago = "INSERT INTO pago (id_miembro, monto, fecha_pago, metodo_pago) VALUES (?, 0, NOW(), ?)";
            $stmt_insertar_pago = $conn->prepare($query_insertar_pago);
            $stmt_insertar_pago->bind_param("is", $id_miembro, $metodo_pago);
            $success = $stmt_insertar_pago->execute() && $success;
            $stmt_insertar_pago->close();
        }
    }

    $conn->close();
    return $success ? "Preferencias actualizadas exitosamente." : "Error al actualizar las preferencias.";
}
