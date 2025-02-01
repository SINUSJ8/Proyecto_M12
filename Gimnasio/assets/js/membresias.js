document.addEventListener("DOMContentLoaded", function () {
    // ✅ Manejar activación/desactivación de membresía con AJAX
    document.querySelectorAll(".estado-button").forEach(button => {
        button.addEventListener("click", function () {
            let idMembresia = this.getAttribute("data-id");
            let accion = this.getAttribute("data-accion"); // 'activar' o 'desactivar'
            let busqueda = this.getAttribute("data-busqueda");
            let mensajeAccion = accion === "activar" ? "activar" : "desactivar";
            let mensajeConfirmacion = accion === "activar" ? "La membresía será activada." : "La membresía será desactivada.";

            Swal.fire({
                title: "¿Estás seguro?",
                text: mensajeConfirmacion,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, " + mensajeAccion,
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("membresia_acciones.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "id_membresia=" + idMembresia + "&accion=" + accion + "&busqueda=" + encodeURIComponent(busqueda)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                let boton = document.querySelector(`.estado-button[data-id='${idMembresia}']`);
                                if (boton) {
                                    if (accion === "activar") {
                                        boton.setAttribute("data-accion", "desactivar");
                                        boton.classList.remove("btn-success");
                                        boton.classList.add("btn-warning");
                                        boton.textContent = "Desactivar";
                                    } else {
                                        boton.setAttribute("data-accion", "activar");
                                        boton.classList.remove("btn-warning");
                                        boton.classList.add("btn-success");
                                        boton.textContent = "Activar";
                                    }
                                }
                                Swal.fire("Éxito", data.message, "success");
                            } else {
                                Swal.fire("Error", data.message, "error");
                            }
                        })
                        .catch(error => {
                            Swal.fire("Error", "No se pudo " + mensajeAccion + " la membresía.", "error");
                        });
                }
            });
        });
    });

    // ✅ Manejar eliminación de membresía con AJAX
    document.querySelectorAll(".delete-button").forEach(button => {
        button.addEventListener("click", function () {
            let idMembresia = this.getAttribute("data-id");

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
                    fetch("membresia_acciones.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "id_membresia=" + idMembresia + "&accion=eliminar"
                    })
                        .then(response => response.json())
                        .then(data => {
                            Swal.fire({
                                title: data.status === "success" ? "Éxito" : "Error",
                                text: data.message,
                                icon: data.status
                            }).then(() => {
                                if (data.status === "success") {
                                    location.reload();
                                }
                            });
                        })
                        .catch(error => {
                            Swal.fire("Error", "No se pudo eliminar la membresía.", "error");
                        });
                }
            });
        });
    });
});
