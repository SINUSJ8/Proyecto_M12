function confirmarEliminacionEspecialidad(idEspecialidad) {
    // Muestra una alerta de confirmación antes de eliminar una especialidad
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            console.log("ID Especialidad a eliminar:", idEspecialidad);

            // Asigna el ID de la especialidad al campo oculto del formulario
            document.getElementById("id_especialidad").value = idEspecialidad;

            // Envía el formulario para eliminar la especialidad
            document.getElementById("form-eliminar").submit();
        }
    });
}

function confirmarEliminacionUsuario(idUsuario) {
    // Muestra una alerta de confirmación antes de eliminar un usuario
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            console.log("Usuario a eliminar:", idUsuario);

            // Asigna el ID del usuario al campo oculto del formulario
            document.getElementById("id_usuario").value = idUsuario;

            // Envía el formulario de eliminación
            document.getElementById("form-eliminar").submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const selectMembresia = document.getElementById('tipo_membresia');
    const form = document.querySelector('.form_general');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    function validarFechas() {
        // Verifica que la fecha de inicio no sea posterior a la fecha de fin
        const fechaInicio = new Date(fechaInicioInput.value);
        const fechaFin = new Date(fechaFinInput.value);

        if (fechaInicio > fechaFin) {
            Swal.fire({
                title: "⚠️ Error en Fechas",
                text: "La fecha de inicio no puede ser posterior a la fecha de fin.",
                icon: "warning",
                confirmButtonText: "Aceptar"
            });
            return false;
        }
        return true;
    }

    function actualizarEntrenamientos() {
        // Marca los entrenamientos según la membresía seleccionada
        const entrenamientosCheckboxes = document.querySelectorAll('.entrenamientos-checkboxes input[type="checkbox"]');

        // Obtiene los entrenamientos asociados a la membresía seleccionada
        const entrenamientosSeleccionados = selectMembresia.options[selectMembresia.selectedIndex].dataset.entrenamientos.split(',');
        entrenamientosCheckboxes.forEach(checkbox => {
            checkbox.checked = entrenamientosSeleccionados.includes(checkbox.value);
        });

        // Ajusta la fecha de fin en función de la duración de la membresía
        const duracion = parseInt(selectMembresia.options[selectMembresia.selectedIndex].dataset.duracion, 10);
        const fechaInicio = new Date(fechaInicioInput.value);

        if (!isNaN(duracion) && duracion > 0) {
            fechaInicio.setMonth(fechaInicio.getMonth() + duracion);
            fechaFinInput.value = fechaInicio.toISOString().split('T')[0];
        }
    }

    if (selectMembresia && form) {
        actualizarEntrenamientos();
        selectMembresia.addEventListener('change', actualizarEntrenamientos);

        // Verifica las fechas antes de enviar el formulario
        form.addEventListener('submit', function (event) {
            if (!validarFechas()) {
                event.preventDefault();
            }
        });
    }
});

function ocultarMensaje() {
    // Oculta automáticamente los mensajes de confirmación o error después de 3 segundos
    setTimeout(function () {
        let mensaje = document.querySelector('.mensaje-confirmacion, .mensaje-error');
        if (mensaje) {
            mensaje.style.transition = "opacity 0.5s ease-out";
            mensaje.style.opacity = "0";
            setTimeout(() => mensaje.remove(), 500);
        }
    }, 3000);
}

// Ejecuta la función para ocultar mensajes cuando la página carga
document.addEventListener("DOMContentLoaded", ocultarMensaje);

function confirmarEdicion(event) {
    event.preventDefault();

    // Muestra una alerta de confirmación antes de actualizar los datos del monitor
    Swal.fire({
        title: "Confirmar Edición",
        html: "<b>¿Estás seguro de que quieres actualizar los datos del monitor?</b>",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#007BFF",
        cancelButtonColor: "#6c757d",
        confirmButtonText: '<i class="fas fa-check"></i> Sí, actualizar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: "custom-popup",
            title: "custom-title"
        }
    }).then((result) => {
        if (result.isConfirmed) {
            event.target.submit();
        }
    });
}

function validarFechas() {
    // Verifica que la fecha de inicio no sea posterior a la fecha de fin
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    const fechaInicio = new Date(fechaInicioInput.value);
    const fechaFin = new Date(fechaFinInput.value);

    if (fechaInicio > fechaFin) {
        Swal.fire({
            title: "⚠️ Fecha Inválida",
            text: "La fecha de inicio no puede ser posterior a la fecha de fin.",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
        return false;
    }
    return true;
}

function confirmarEdicionMiembro(event) {
    event.preventDefault();

    // Valida las fechas antes de confirmar la edición
    if (!validarFechas()) {
        return;
    }

    // Muestra una alerta de confirmación antes de actualizar los datos del miembro
    Swal.fire({
        title: "Confirmar Edición",
        html: "<b>¿Estás seguro de que quieres actualizar los datos del miembro?</b>",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#007BFF",
        cancelButtonColor: "#6c757d",
        confirmButtonText: '<i class="fas fa-check"></i> Sí, actualizar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: "custom-popup",
            title: "custom-title"
        }
    }).then((result) => {
        if (result.isConfirmed) {
            event.target.submit();
        }
    });
}

