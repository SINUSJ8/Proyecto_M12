function confirmarEliminacion(idEspecialidad) {
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
