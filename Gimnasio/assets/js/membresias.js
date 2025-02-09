document.addEventListener("DOMContentLoaded", function () {
    console.log(" Script cargado correctamente");

    document.querySelectorAll(".estado-button").forEach(button => {
        button.addEventListener("click", function () {
            let idMembresia = this.getAttribute("data-id");
            let accion = this.getAttribute("data-accion");
            let busqueda = this.getAttribute("data-busqueda");
            let filaActual = this.closest("tr");
            let idUsuario = filaActual.getAttribute("data-usuario");

            console.log("🔹 Botón presionado:", { idMembresia, accion, idUsuario });

            let mensajeAccion = accion === "activar" ? "activar" : "desactivar";
            let mensajeConfirmacion = accion === "activar"
                ? "Esta membresía será activada y cualquier otra activa de este usuario se desactivará."
                : "Esta membresía será desactivada.";

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
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id_membresia=${idMembresia}&accion=${accion}&busqueda=${encodeURIComponent(busqueda)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log("🔹 Respuesta del servidor:", data);

                            if (data.status === "success") {
                                let estadoCelda = filaActual.querySelector("td:nth-child(9)"); // Columna de estado

                                //  Actualizar el estado en la tabla
                                if (estadoCelda) {
                                    estadoCelda.textContent = data.nuevo_estado;
                                }

                                // Si activamos una membresía, buscar la anterior activa y cambiar su estado a "expirada"
                                if (accion === "activar") {
                                    let filaAnterior = document.querySelector(`tr[data-usuario='${idUsuario}'] .estado-button[data-accion='desactivar']`);
                                    if (filaAnterior && filaAnterior !== this) {
                                        console.log("🔄 Cambiando estado de la membresía anterior a 'expirada':", filaAnterior);

                                        // Actualizar la celda de estado
                                        let estadoCeldaAnterior = filaAnterior.closest("tr").querySelector("td:nth-child(9)");
                                        if (estadoCeldaAnterior) {
                                            estadoCeldaAnterior.textContent = "expirada";
                                        }

                                        // Cambiar el botón de la membresía anterior
                                        filaAnterior.setAttribute("data-accion", "activar");
                                        filaAnterior.classList.remove("btn-warning");
                                        filaAnterior.classList.add("btn-success");
                                        filaAnterior.textContent = "Activar";
                                    }
                                }

                                //  Cambiar el botón actual al estado correcto
                                if (accion === "activar") {
                                    console.log(" Actualizando botón actual a 'Desactivar'");
                                    this.setAttribute("data-accion", "desactivar");
                                    this.classList.remove("btn-success");
                                    this.classList.add("btn-warning");
                                    this.textContent = "Desactivar";
                                } else {
                                    console.log(" Actualizando botón actual a 'Activar'");
                                    this.setAttribute("data-accion", "activar");
                                    this.classList.remove("btn-warning");
                                    this.classList.add("btn-success");
                                    this.textContent = "Activar";
                                }

                                Swal.fire("Éxito", data.message, "success");
                            } else {
                                Swal.fire("Error", data.message, "error");
                            }
                        })
                        .catch(error => {
                            console.error(" Error en fetch:", error);
                            Swal.fire("Error", "No se pudo " + mensajeAccion + " la membresía.", "error");
                        });
                }
            });
        });
    });

    //  Manejar eliminación de membresía con AJAX usando delegación de eventos
    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("delete-button")) {
            let idMembresia = e.target.getAttribute("data-id");

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
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id_membresia=${idMembresia}&accion=eliminar`
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
                            console.error(" Error en fetch:", error);
                            Swal.fire("Error", "No se pudo eliminar la membresía.", "error");
                        });
                }
            });
        }
    });
});

