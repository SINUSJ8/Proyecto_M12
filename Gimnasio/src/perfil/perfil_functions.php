<?php
function actualizarPerfil($conn, $id_usuario, $nombre, $email, $telefono, $id_membresia)
{
    try {
        $sql = "UPDATE miembro
                INNER JOIN usuario ON miembro.id_usuario = usuario.id_usuario
                SET usuario.nombre = ?, usuario.email = ?, usuario.telefono = ?, miembro.id_membresia = ?
                WHERE usuario.id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssii', $nombre, $email, $telefono, $id_membresia, $id_usuario);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'No se realizaron cambios.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
function obtenerMiembroPorIDPerfil($conn, $id_usuario)
{
    // Consultar datos básicos del miembro, incluyendo la membresía y el teléfono
    $sql = "SELECT u.id_usuario, u.nombre, u.email, u.rol, u.telefono, m.fecha_registro, m.id_membresia, m.id_miembro, mb.tipo AS tipo_membresia
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
