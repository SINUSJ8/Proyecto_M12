function confirmarEliminacionEspecialidad(idEspecialidad) {
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

            // Asignar el ID al input hidden del formulario existente
            document.getElementById("id_especialidad").value = idEspecialidad;

            // Enviar el formulario
            document.getElementById("form-eliminar").submit();
        }
    });
}
function confirmarEliminacionUsuario(idUsuario) {
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

            // Asignar el ID al input hidden del formulario oculto
            document.getElementById("id_usuario").value = idUsuario;

            // Enviar el formulario
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
        const fechaInicio = new Date(fechaInicioInput.value);
        const fechaFin = new Date(fechaFinInput.value);

        if (fechaInicio > fechaFin) {
            alert("⚠️ La fecha de inicio no puede ser posterior a la fecha de fin.");
            return false; // Evita el envío del formulario
        }
        return true; // Permite el envío si las fechas son correctas
    }

    function actualizarEntrenamientos() {
        const entrenamientosCheckboxes = document.querySelectorAll('.entrenamientos-checkboxes input[type="checkbox"]');

        // Obtener entrenamientos asociados con la membresía seleccionada
        const entrenamientosSeleccionados = selectMembresia.options[selectMembresia.selectedIndex].dataset.entrenamientos.split(',');
        entrenamientosCheckboxes.forEach(checkbox => {
            checkbox.checked = entrenamientosSeleccionados.includes(checkbox.value);
        });

        // Actualizar la fecha de fin según la duración de la membresía
        const duracion = parseInt(selectMembresia.options[selectMembresia.selectedIndex].dataset.duracion, 10);
        const fechaInicio = new Date(fechaInicioInput.value);

        if (!isNaN(duracion) && duracion > 0) {
            fechaInicio.setMonth(fechaInicio.getMonth() + duracion);
            fechaFinInput.value = fechaInicio.toISOString().split('T')[0]; // Formatear como YYYY-MM-DD
        }
    }

    if (selectMembresia && form) {
        actualizarEntrenamientos();
        selectMembresia.addEventListener('change', actualizarEntrenamientos);

        // Validar fechas antes de enviar el formulario
        form.addEventListener('submit', function (event) {
            if (!validarFechas()) {
                event.preventDefault(); // Detener el envío si las fechas son incorrectas
            }
        });
    }
});



function ocultarMensaje() {
    setTimeout(function () {
        let mensaje = document.querySelector('.mensaje-confirmacion, .mensaje-error');
        if (mensaje) {
            mensaje.style.transition = "opacity 0.5s ease-out";
            mensaje.style.opacity = "0";
            setTimeout(() => mensaje.remove(), 500);
        }
    }, 3000);
}

// Ejecutar al cargar la página
document.addEventListener("DOMContentLoaded", ocultarMensaje);

function confirmarEdicion(event) {
    event.preventDefault();

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

    if (!validarFechas()) {
        return;
    }

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



