<?php
session_start();
include '../includes/general.php';

$conn = obtenerConexion();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_membresia = $_POST['id_membresia'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;

    // Validar que los datos sean correctos
    if (!$id_membresia || !$metodo_pago) {
        header("Location: ../miembros/miembro.php?error=Datos+de+membresía+incompletos.");
        exit();
    }

    // Obtener el id_miembro correspondiente al usuario
    $query_miembro = "SELECT id_miembro FROM miembro WHERE id_usuario = ?";
    $stmt_miembro = $conn->prepare($query_miembro);
    $stmt_miembro->bind_param("i", $id_usuario);
    $stmt_miembro->execute();
    $result_miembro = $stmt_miembro->get_result();

    if ($result_miembro->num_rows === 0) {
        header("Location: ../miembros/miembro.php?error=No+se+encontró+el+usuario.");
        exit();
    }

    $row_miembro = $result_miembro->fetch_assoc();
    $id_miembro = $row_miembro['id_miembro'];
    $stmt_miembro->close();

    // Desactivar la membresía anterior si está activa
    $query_desactivar = "UPDATE miembro_membresia SET estado = 'expirada' WHERE id_miembro = ? AND estado = 'activa'";
    $stmt_desactivar = $conn->prepare($query_desactivar);
    $stmt_desactivar->bind_param("i", $id_miembro);
    $stmt_desactivar->execute();
    $stmt_desactivar->close();

    // Eliminar los entrenamientos asociados al miembro
    $query_eliminar_entrenamientos = "DELETE FROM miembro_entrenamiento WHERE id_miembro = ?";
    $stmt_eliminar = $conn->prepare($query_eliminar_entrenamientos);
    $stmt_eliminar->bind_param("i", $id_miembro);
    $stmt_eliminar->execute();
    $stmt_eliminar->close();

    // Obtener los detalles de la nueva membresía
    $query_detalle = "SELECT precio, duracion FROM membresia WHERE id_membresia = ?";
    $stmt_detalle = $conn->prepare($query_detalle);
    $stmt_detalle->bind_param("i", $id_membresia);
    $stmt_detalle->execute();
    $result_detalle = $stmt_detalle->get_result();

    if ($row = $result_detalle->fetch_assoc()) {
        $monto_pagado = $row['precio'];
        $duracion_meses = $row['duracion'];
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d', strtotime("+$duracion_meses months"));

        // Registrar el pago
        $query_pago = "INSERT INTO pago (id_miembro, monto, fecha_pago, metodo_pago) VALUES (?, ?, NOW(), ?)";
        $stmt_pago = $conn->prepare($query_pago);
        $stmt_pago->bind_param("ids", $id_miembro, $monto_pagado, $metodo_pago);
        $stmt_pago->execute();
        $stmt_pago->close();

        // Registrar la nueva membresía
        $query_membresia = "INSERT INTO miembro_membresia (id_miembro, id_membresia, monto_pagado, fecha_inicio, fecha_fin, estado, renovacion_automatica) VALUES (?, ?, ?, ?, ?, 'activa', 0)";
        $stmt_membresia = $conn->prepare($query_membresia);
        $stmt_membresia->bind_param("iisss", $id_miembro, $id_membresia, $monto_pagado, $fecha_inicio, $fecha_fin);
        $stmt_membresia->execute();
        $stmt_membresia->close();

        // Insertar los nuevos entrenamientos
        $query_entrenamientos = "SELECT id_entrenamiento FROM membresia_entrenamiento WHERE id_membresia = ?";
        $stmt_entrenamientos = $conn->prepare($query_entrenamientos);
        $stmt_entrenamientos->bind_param("i", $id_membresia);
        $stmt_entrenamientos->execute();
        $result_entrenamientos = $stmt_entrenamientos->get_result();

        while ($entrenamiento = $result_entrenamientos->fetch_assoc()) {
            $id_especialidad = $entrenamiento['id_entrenamiento'];
            $query_insert_entrenamiento = "INSERT INTO miembro_entrenamiento (id_miembro, id_especialidad) VALUES (?, ?)";
            $stmt_insert_entrenamiento = $conn->prepare($query_insert_entrenamiento);
            $stmt_insert_entrenamiento->bind_param("ii", $id_miembro, $id_especialidad);
            $stmt_insert_entrenamiento->execute();
            $stmt_insert_entrenamiento->close();
        }

        $stmt_entrenamientos->close();
    } else {
        header("Location: ../membresias/mi_membresia.php?error=Membresía+no+encontrada.");
        exit();
    }

    $stmt_detalle->close();
    $conn->close();

    // Redirigir con mensaje de éxito
    header("Location: ../membresias/mi_membresia.php?mensaje=Membresía+cambiada+correctamente.");
    exit();
}
