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
        return null;
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
                $id_especialidad = $entrenamiento['id_entrenamiento'];
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
        return null;
    }
}

function informacionMembresia($id_usuario)
{
    $conexion = obtenerConexion();

    // Consulta para obtener la información del miembro y su último pago
    $sql = "
        SELECT 
            u.nombre AS nombre_usuario,
            u.email,
            m.fecha_registro,
            mem.tipo AS nombre_membresia,
            mem.estado AS estado_global, -- Agregamos el estado de la membresía
            mm.fecha_inicio,
            mm.fecha_fin,
            mm.estado,
            mm.renovacion_automatica,
            mm.monto_pagado AS monto_pago,
            (
                SELECT p.metodo_pago 
                FROM pago p 
                INNER JOIN miembro mi ON p.id_miembro = mi.id_miembro
                WHERE mi.id_usuario = u.id_usuario
                ORDER BY p.fecha_pago DESC, p.id_pago DESC
                LIMIT 1
            ) AS metodo_pago,
            (
                SELECT p.fecha_pago 
                FROM pago p 
                INNER JOIN miembro mi ON p.id_miembro = mi.id_miembro
                WHERE mi.id_usuario = u.id_usuario
                ORDER BY p.fecha_pago DESC, p.id_pago DESC
                LIMIT 1
            ) AS fecha_pago
        FROM usuario u
        LEFT JOIN miembro m ON u.id_usuario = m.id_usuario
        LEFT JOIN miembro_membresia mm ON m.id_miembro = mm.id_miembro AND mm.estado = 'activa'
        LEFT JOIN membresia mem ON mm.id_membresia = mem.id_membresia -- Se une con la tabla membresia
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


function obtenerTotalMiembros($conn, $busqueda = '')
{
    $sql = "SELECT COUNT(*) AS total 
            FROM miembro m
            JOIN usuario u ON m.id_usuario = u.id_usuario";

    // Agregar filtro de búsqueda si hay un término
    if (!empty($busqueda)) {
        $sql .= " WHERE u.nombre LIKE ? OR u.email LIKE ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    if (!empty($busqueda)) {
        $busqueda_param = '%' . $busqueda . '%';
        $stmt->bind_param("ss", $busqueda_param, $busqueda_param);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function obtenerMiembrosPaginados($conn, $limit, $offset, $busqueda = '')
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
            LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad";

    // Agregar filtro de búsqueda si hay un término
    if (!empty($busqueda)) {
        $sql .= " WHERE u.nombre LIKE ? OR u.email LIKE ?";
    }

    $sql .= " GROUP BY m.id_miembro
              ORDER BY u.nombre ASC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    if (!empty($busqueda)) {
        $busqueda_param = '%' . $busqueda . '%';
        echo "<script>console.log('Parámetros de búsqueda: $busqueda_param');</script>";
        $stmt->bind_param("ssii", $busqueda_param, $busqueda_param, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

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

    return $usuario ? [$usuario] : [];
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

function actualizarPreferenciasMembresia($id_usuario, $renovacion_automatica)
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

    // Actualizar solo la renovación automática
    if (!is_null($renovacion_automatica)) {
        $query_renovacion = "UPDATE miembro_membresia SET renovacion_automatica = ? WHERE id = ?";
        $stmt_renovacion = $conn->prepare($query_renovacion);
        $stmt_renovacion->bind_param("ii", $renovacion_automatica, $id_membresia_activa);
        $success = $stmt_renovacion->execute();
        $stmt_renovacion->close();
    } else {
        $success = false;
    }

    $conn->close();
    return $success ? "Renovación automática actualizada correctamente." : "Error al actualizar la renovación automática.";
}

function actualizarMetodoPagoGuardado($id_usuario, $metodo_pago)
{
    $conn = obtenerConexion();

    // Verificar si el usuario tiene un miembro asociado
    $stmt_miembro = $conn->prepare("SELECT id_miembro FROM miembro WHERE id_usuario = ?");
    $stmt_miembro->bind_param("i", $id_usuario);
    $stmt_miembro->execute();
    $result_miembro = $stmt_miembro->get_result();
    $miembro = $result_miembro->fetch_assoc();
    $stmt_miembro->close();

    if (!$miembro) {
        return "Error: No se encontró un miembro asociado a este usuario.";
    }

    $id_miembro = $miembro['id_miembro'];

    // Verificar si ya existe un método de pago guardado
    $stmt_check = $conn->prepare("SELECT id_metodo FROM metodo_pago_guardado WHERE id_miembro = ?");
    $stmt_check->bind_param("i", $id_miembro);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $existe = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($existe) {
        // Si ya existe, actualizar el método de pago
        $stmt = $conn->prepare("UPDATE metodo_pago_guardado SET metodo = ?, fecha_registro = NOW() WHERE id_miembro = ?");
        $stmt->bind_param("si", $metodo_pago, $id_miembro);
    } else {
        // Si no existe, insertar un nuevo método de pago
        $stmt = $conn->prepare("INSERT INTO metodo_pago_guardado (id_miembro, metodo) VALUES (?, ?)");
        $stmt->bind_param("is", $id_miembro, $metodo_pago);
    }

    if ($stmt->execute()) {
        $stmt->close();
        return "Método de pago actualizado correctamente.";
    } else {
        $stmt->close();
        return "Error al actualizar el método de pago.";
    }
}
