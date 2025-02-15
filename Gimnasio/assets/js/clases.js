function ordenarTabla(columna, idTabla = 'tabla-clases') {
    const tabla = document.getElementById(idTabla);
    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';
    const esNumerico = columna >= 5; // Duración y Capacidad son numéricos.
    const esFecha = columna === 3; // Si la columna es la de Fecha.

    // Limpiar clases de orden en todas las columnas
    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    filas.sort((a, b) => {
        const celdaA = a.cells[columna].innerText.trim();
        const celdaB = b.cells[columna].innerText.trim();

        if (esNumerico) {
            return tipoOrden === 'asc'
                ? parseFloat(celdaA) - parseFloat(celdaB)
                : parseFloat(celdaB) - parseFloat(celdaA);
        } else if (esFecha) {
            const fechaA = new Date(celdaA);
            const fechaB = new Date(celdaB);
            return tipoOrden === 'asc' ? fechaA - fechaB : fechaB - fechaA;
        } else {
            return tipoOrden === 'asc'
                ? celdaA.localeCompare(celdaB)
                : celdaB.localeCompare(celdaA);
        }
    });

    // Aplicar la nueva clase de orden
    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    // Reinsertar las filas ordenadas en la tabla
    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}
function ordenarTablaM(columna, idTabla = 'tabla-clases') {
    const tabla = document.getElementById(idTabla);
    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';
    const esNumerico = columna === 3 || columna >= 5; // Incluye experiencia, duración y capacidad.
    const esFecha = columna === 3; // Si la columna es de Fecha.

    // Limpiar clases de orden en todas las columnas
    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    filas.sort((a, b) => {
        let celdaA = a.cells[columna].innerText.trim();
        let celdaB = b.cells[columna].innerText.trim();

        if (esNumerico) {
            // Eliminar texto como " años" o caracteres no numéricos
            const valorA = parseFloat(celdaA.replace(/[^0-9.-]+/g, ''));
            const valorB = parseFloat(celdaB.replace(/[^0-9.-]+/g, ''));
            return tipoOrden === 'asc' ? valorA - valorB : valorB - valorA;
        } else if (esFecha) {
            const fechaA = new Date(celdaA);
            const fechaB = new Date(celdaB);
            return tipoOrden === 'asc' ? fechaA - fechaB : fechaB - fechaA;
        } else {
            return tipoOrden === 'asc'
                ? celdaA.localeCompare(celdaB)
                : celdaB.localeCompare(celdaA);
        }
    });

    // Aplicar la nueva clase de orden
    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    // Reinsertar las filas ordenadas en la tabla
    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}
function ordenarTablaMi(columna, idTabla = 'tabla-clases') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) {
        console.error(`Tabla con ID "${idTabla}" no encontrada.`);
        return;
    }

    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';
    const esNumerico = columna >= 5; // Duración y Capacidad son numéricos.
    const esFecha = columna === 2; // Si la columna es de Fecha.
    const esTexto = columna === 3; // Tipo de Membresía es texto.

    // Limpiar clases de orden en todas las columnas
    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    filas.sort((a, b) => {
        let celdaA = a.cells[columna].innerText.trim();
        let celdaB = b.cells[columna].innerText.trim();

        if (esNumerico) {
            return tipoOrden === 'asc'
                ? parseFloat(celdaA) - parseFloat(celdaB)
                : parseFloat(celdaB) - parseFloat(celdaA);
        } else if (esFecha) {
            const fechaA = new Date(celdaA);
            const fechaB = new Date(celdaB);
            return tipoOrden === 'asc' ? fechaA - fechaB : fechaB - fechaA;
        } else if (esTexto) {
            // Convertir a minúsculas para comparar de forma consistente
            celdaA = celdaA.toLowerCase();
            celdaB = celdaB.toLowerCase();
            return tipoOrden === 'asc'
                ? celdaA.localeCompare(celdaB)
                : celdaB.localeCompare(celdaA);
        } else {
            return tipoOrden === 'asc'
                ? celdaA.localeCompare(celdaB)
                : celdaB.localeCompare(celdaA);
        }
    });

    // Aplicar la nueva clase de orden
    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    // Reinsertar las filas ordenadas en la tabla
    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}
function ordenarTablaU(columna) {
    const tabla = document.getElementById('tabla-usuarios');
    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';
    const esFecha = columna === 4; // La columna de Fecha de Registro es 4

    // Limpiar clases de orden en todas las columnas
    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    filas.sort((a, b) => {
        let celdaA = a.cells[columna].innerText.trim();
        let celdaB = b.cells[columna].innerText.trim();

        if (esFecha) {
            const fechaA = new Date(celdaA);
            const fechaB = new Date(celdaB);
            return tipoOrden === 'asc' ? fechaA - fechaB : fechaB - fechaA;
        } else {
            return tipoOrden === 'asc'
                ? celdaA.localeCompare(celdaB)
                : celdaB.localeCompare(celdaA);
        }
    });

    // Aplicar la nueva clase de orden
    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    // Reinsertar las filas ordenadas en la tabla
    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}

function ordenarTablaMe(columna, idTabla = 'tabla-membresias') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) {
        console.error(`Tabla con ID "${idTabla}" no encontrada.`);
        return;
    }

    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';

    // Limpiar clases de orden en todas las columnas
    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    // Lógica para ordenar las filas según el tipo de columna
    filas.sort((a, b) => {
        let celdaA = a.cells[columna].innerText.trim();
        let celdaB = b.cells[columna].innerText.trim();

        // Verificar si la columna es de tipo fecha
        if (columna === 6 || columna === 7) {  // Fecha de Inicio y Fecha de Fin
            const fechaA = new Date(celdaA);
            const fechaB = new Date(celdaB);
            return tipoOrden === 'asc' ? fechaA - fechaB : fechaB - fechaA;
        }

        // Si la columna es numérica (Precio y Duración)
        if (columna === 4 || columna === 5) {  // Precio y Duración
            return tipoOrden === 'asc'
                ? parseFloat(celdaA) - parseFloat(celdaB)
                : parseFloat(celdaB) - parseFloat(celdaA);
        }

        // Si la columna es de texto (Nombre, Email, Teléfono, Tipo de Membresía)
        return tipoOrden === 'asc'
            ? celdaA.localeCompare(celdaB)
            : celdaB.localeCompare(celdaA);
    });

    // Aplicar la nueva clase de orden (ascendente o descendente)
    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    // Reinsertar las filas ordenadas en la tabla
    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}


function limpiarFormulario() {
    const form = document.querySelector('.search-form');
    form.reset();
    window.location.href = 'clases.php';
}
function ordenarTablaC(columna, idTabla = 'tabla-clases') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) return;

    const filas = Array.from(tabla.tBodies[0].rows);
    const th = tabla.tHead.rows[0].cells[columna];
    const tipoOrden = th.classList.contains('sorted-asc') ? 'desc' : 'asc';

    Array.from(tabla.tHead.rows[0].cells).forEach(cell => {
        cell.classList.remove('sorted-asc', 'sorted-desc');
    });

    filas.sort((a, b) => {
        let celdaA = a.cells[columna]?.innerText.trim();
        let celdaB = b.cells[columna]?.innerText.trim();

        const esNumerico = !isNaN(celdaA) && !isNaN(celdaB);
        const esFecha = !isNaN(Date.parse(celdaA)) && !isNaN(Date.parse(celdaB));

        if (esNumerico) {
            return tipoOrden === 'asc' ?
                parseFloat(celdaA) - parseFloat(celdaB) :
                parseFloat(celdaB) - parseFloat(celdaA);
        } else if (esFecha) {
            return tipoOrden === 'asc' ?
                new Date(celdaA) - new Date(celdaB) :
                new Date(celdaB) - new Date(celdaA);
        } else {
            return tipoOrden === 'asc' ?
                celdaA.localeCompare(celdaB) :
                celdaB.localeCompare(celdaA);
        }
    });

    th.classList.add(tipoOrden === 'asc' ? 'sorted-asc' : 'sorted-desc');

    filas.forEach(fila => tabla.tBodies[0].appendChild(fila));
}

// Script para monitores.php (botón eliminar) ->Espera a que el DOM esté completamente cargado antes de ejecutar el script.
document.addEventListener("DOMContentLoaded", function () {

    // Selecciona todos los botones de eliminación con la clase "eliminar-btn".
    document.querySelectorAll(".eliminar-btn").forEach(button => {

        // Agrega un evento de clic a cada botón de eliminación.
        button.addEventListener("click", function (event) {

            // Encuentra el formulario más cercano al botón que fue presionado.
            let form = this.closest("form");
            if (!form) {
                return; // Si no encuentra un formulario, termina la función.
            }

            // Muestra un cuadro de confirmación con SweetAlert2.
            Swal.fire({
                title: "¿Estás seguro?", // Título de la alerta.
                text: "No podrás revertir esta acción.", // Mensaje de advertencia.
                icon: "warning", // Icono de advertencia.
                showCancelButton: true, // Agrega un botón para cancelar la acción.
                confirmButtonColor: "#d33", // Color del botón de confirmación.
                cancelButtonColor: "#3085d6", // Color del botón de cancelación.
                confirmButtonText: "Sí, eliminar", // Texto del botón de confirmación.
                cancelButtonText: "Cancelar" // Texto del botón de cancelación.
            }).then((result) => {
                // Si el usuario confirma la eliminación.
                if (result.isConfirmed) {

                    // Crear un input oculto para asegurarse de que se envía el valor correcto.
                    let input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "eliminar_usuario"; // Nombre del campo que se enviará en el formulario.
                    input.value = "1"; // Valor que se enviará al servidor.
                    form.appendChild(input); // Agrega el input oculto al formulario.

                    form.submit(); // Envía el formulario manualmente.
                }
            });
        });
    });
});
