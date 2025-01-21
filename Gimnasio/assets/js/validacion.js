// Función para validar el formulario de registro de usuario
function validarFormulario() {
    const formulario = document.querySelector('form[data-context]'); // Obtiene el formulario con data-context
    const contexto = formulario.getAttribute('data-context'); // "registro" o "edicion"

    const nombre = document.getElementById('nombre').value.trim();
    const email = document.getElementById('email').value.trim();
    const contrasenya = document.getElementById('contrasenya').value.trim();
    const confirmarContrasenya = document.getElementById('confirmar_contrasenya').value.trim();

    // Validación del campo de nombre: se asegura de que contenga al menos una letra
    const nombreRegex = /[a-zA-Z]/;
    if (!nombreRegex.test(nombre)) {
        mostrarMensajeError("Por favor, ingresa un nombre válido con al menos una letra.");
        return false;
    }

    // Validación del campo de email: verifica que no esté vacío
    if (email === "") {
        mostrarMensajeError("Por favor, ingresa tu correo electrónico.");
        return false;
    }

    // Validación del formato del email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        mostrarMensajeError("Por favor, ingresa un correo electrónico válido.");
        return false;
    }

    // Validación de la contraseña dependiendo del contexto
    if (contexto === "registro" || (contexto === "edicion" && contrasenya !== "")) {
        if (contrasenya.length < 6) {
            mostrarMensajeError("La contraseña debe tener al menos 6 caracteres.");
            return false;
        }

        // Validación de coincidencia de contraseñas: confirma que ambas contraseñas son iguales
        if (contrasenya !== confirmarContrasenya) {
            mostrarMensajeError("Las contraseñas no coinciden. Por favor, verifica.");
            return false;
        }
    }

    return true; // Si pasa todas las validaciones, el formulario se enviará
}

// Función para mostrar mensajes de error en el formulario
function mostrarMensajeError(mensaje) {
    const mensajeError = document.createElement('div');
    mensajeError.className = 'mensaje-error';
    mensajeError.textContent = mensaje;

    const formulario = document.querySelector('.form_container');
    formulario.insertBefore(mensajeError, formulario.firstChild);

    // Elimina el mensaje de error después de 5 segundos
    setTimeout(() => {
        mensajeError.remove();
    }, 5000);
}
// Función para mostrar y eliminar mensajes del servidor
function manejarMensajeServidor() {
    const mensaje = document.getElementById('mensaje-flotante');
    if (mensaje) {
        // Elimina el mensaje después de 5 segundos
        setTimeout(() => {
            mensaje.style.transition = 'opacity 0.5s ease';
            mensaje.style.opacity = '0';
            setTimeout(() => mensaje.remove(), 500); // Remueve el elemento tras la transición
        }, 5000);
    }
}

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    const mensaje = document.getElementById('mensaje-flotante');
    if (mensaje) {
        console.log("Mensaje encontrado:", mensaje.textContent); // Para verificar en la consola
        setTimeout(() => {
            mensaje.style.transition = 'opacity 0.5s ease';
            mensaje.style.opacity = '0';
            console.log("Ocultando mensaje..."); // Confirmación en consola
            setTimeout(() => {
                mensaje.remove();
                console.log("Mensaje eliminado."); // Confirmación en consola
            }, 500);
        }, 5000);
    } else {
        console.log("No se encontró el mensaje flotante.");
    }
});



// Función para validar el formulario de actualización de usuario en la página de perfil
function valFormUsuario() {
    const nombre = document.getElementById('nombre').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const password = document.getElementById('contrasenya').value.trim();
    const confirmarPassword = document.getElementById('confirmar_contrasenya').value.trim();

    // Limpia mensajes de error previos
    const mensajesExistentes = document.querySelectorAll('.mensaje-error');
    mensajesExistentes.forEach(mensaje => mensaje.remove());

    // Validación del nombre
    const nombreRegex = /[a-zA-Z]/;
    if (!nombreRegex.test(nombre)) {
        mostrarMensajeError("Por favor, ingresa un nombre válido con al menos una letra.");
        return false;
    }

    // Validación del teléfono
    const telefonoRegex = /^\d{9}$/;
    if (telefono && !telefonoRegex.test(telefono)) {
        mostrarMensajeError("El teléfono debe tener exactamente 9 dígitos.");
        return false;
    }

    // Validación de la contraseña
    if (password && password.length < 6) {
        mostrarMensajeError("La contraseña debe tener al menos 6 caracteres.");
        return false;
    }

    // Validación de coincidencia de contraseñas
    if (password && password !== confirmarPassword) {
        mostrarMensajeError("Las contraseñas no coinciden. Por favor, verifica.");
        return false;
    }

    // Si todo está correcto, permite el envío del formulario
    return true;
}

function validarFormularioEdicion(tipoFormulario) {
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const telefono = document.getElementById('telefono') ? document.getElementById('telefono').value : null;
    const experiencia = document.getElementById('experiencia') ? document.getElementById('experiencia').value : null;
    const disponibilidad = document.getElementById('disponibilidad') ? document.getElementById('disponibilidad').value : null;

    // Validación del nombre
    const nombreRegex = /[a-zA-Z]/;
    if (!nombreRegex.test(nombre)) {
        alert("Por favor, ingresa un nombre válido con al menos una letra.");
        return false;
    }

    // Validación del correo electrónico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert("Por favor, ingresa un correo electrónico válido.");
        return false;
    }

    // Validación del teléfono si está presente
    if (telefono) {
        const telefonoRegex = /^\d{9}$/;
        if (!telefonoRegex.test(telefono)) {
            alert("El teléfono debe tener exactamente 9 dígitos.");
            return false;
        }
    }

    // Validaciones específicas para monitores
    if (tipoFormulario === 'monitor') {
        if (experiencia === null || isNaN(experiencia) || experiencia < 0) {
            alert("Por favor, ingresa un número válido para la experiencia (años). Puede ser cero o mayor.");
            return false;
        }
        if (!disponibilidad) {
            alert("Por favor, selecciona una disponibilidad.");
            return false;
        }
    }

    // Validaciones específicas para otros formularios
    // Otras validaciones específicas para otros tipos de formularios se pueden añadir.

    // Confirmación final para asegurarse
    const mensajeConfirmacion = "Estás a punto de actualizar los datos del " + tipoFormulario + ".\n\n" +
        " ¿Deseas continuar con la actualización de los datos?";
    return confirm(mensajeConfirmacion);
}

function actualizarFechasMembresia() {
    const selectMembresia = document.getElementById('tipo_membresia');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    // Verificar que los elementos existan en el DOM
    if (!selectMembresia || !fechaInicioInput || !fechaFinInput) {
        console.error("No se encontraron los elementos necesarios.");
        return;
    }

    // Obtener la duración de la membresía seleccionada
    const duracionMeses = parseInt(selectMembresia.options[selectMembresia.selectedIndex].getAttribute('data-duracion'), 10);
    if (isNaN(duracionMeses)) {
        console.error("Duración de la membresía seleccionada no es válida.");
        return;
    }

    // Calcular la fecha de inicio (hoy)
    const fechaInicio = new Date();

    // Calcular la fecha de fin
    const fechaFin = new Date(fechaInicio);
    fechaFin.setMonth(fechaFin.getMonth() + duracionMeses);

    // Formatear las fechas en formato "YYYY-MM-DD"
    const fechaInicioFormateada = fechaInicio.toISOString().split('T')[0];
    const fechaFinFormateada = fechaFin.toISOString().split('T')[0];

    // Actualizar los campos de fecha
    fechaInicioInput.value = fechaInicioFormateada;
    fechaFinInput.value = fechaFinFormateada;
}
function actualizarEntrenamientos() {
    const selectMembresia = document.getElementById('tipo_membresia');
    const entrenamientosCheckboxes = document.querySelectorAll('.entrenamientos-checkboxes input[type="checkbox"]');

    // Obtener los IDs de entrenamientos de la membresía seleccionada
    const entrenamientosIds = selectMembresia.options[selectMembresia.selectedIndex].getAttribute('data-entrenamientos');

    // Desmarcar todos los checkboxes por defecto
    entrenamientosCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });

    if (entrenamientosIds) {
        const entrenamientosArray = entrenamientosIds.split(',').map(id => id.trim());

        // Marcar los checkboxes que coincidan con los IDs de la membresía seleccionada
        entrenamientosCheckboxes.forEach(checkbox => {
            if (entrenamientosArray.includes(checkbox.value)) {
                checkbox.checked = true;
            }
        });
    }
}

function toggleDestinatario() {
    const destinatario = document.getElementById('destinatario').value;
    document.getElementById('grupo_destinatario').style.display = destinatario === 'grupo' ? 'block' : 'none';
    document.getElementById('usuario_destinatario').style.display = destinatario === 'usuario' ? 'block' : 'none';
}
function buscarUsuario(termino) {
    const selectUsuario = document.getElementById('id_usuario');

    if (termino.trim() === '') {
        selectUsuario.innerHTML = '<option value="">-- Selecciona un usuario --</option>';
        return;
    }

    fetch(`notificaciones.php?ajax=true&q=${encodeURIComponent(termino)}`)
        .then(response => response.text())
        .then(html => {
            if (html.trim() === '') {
                selectUsuario.innerHTML = '<option value="">No se encontraron resultados</option>';
            } else {
                selectUsuario.innerHTML = '<option value="">-- Selecciona un usuario --</option>' + html;
            }
        })
        .catch(error => {
            console.error('Error al buscar usuarios:', error);
            selectUsuario.innerHTML = '<option value="">Error al buscar usuarios</option>';
        });
}
function mostrarConfirmacion(event, membresia) {
    event.preventDefault();

    console.log("Datos de membresía recibidos:", membresia);

    const formulario = event.target;
    const metodoPago = formulario.querySelector('select[name="metodo_pago"]').value;

    if (!membresia.id_membresia || !metodoPago) {
        alert('Error: Datos incompletos para procesar la membresía.');
        return false;
    }

    // Rellenar campos ocultos del modal
    document.getElementById('id_membresia_modal').value = membresia.id_membresia;
    document.getElementById('metodo_pago_modal').value = metodoPago;

    document.getElementById('modal-detalles').innerHTML = `
        <strong>Membresía:</strong> ${membresia.tipo}<br>
        <strong>Precio:</strong> ${membresia.precio} €<br>
        <strong>Duración:</strong> ${membresia.duracion} mes(es)<br>
        <strong>Método de Pago:</strong> ${metodoPago}
    `;

    document.getElementById('modal-confirmacion').style.display = 'flex';
}

function mostrarConfirmacionC(event, membresia, tipoAccion) {
    event.preventDefault();

    const formulario = event.target;
    const metodoPago = formulario.querySelector('select[name="metodo_pago"]').value;

    if (!membresia.id_membresia || !metodoPago) {
        alert('Error: Datos incompletos para procesar la membresía.');
        return false;
    }

    // Rellenar campos ocultos del modal
    document.getElementById('id_membresia_modal').value = membresia.id_membresia;
    document.getElementById('metodo_pago_modal').value = metodoPago;

    // Configurar el mensaje del modal según la acción
    const titulo = tipoAccion === 'cambio' ? 'Confirmar Cambio de Membresía' : 'Confirmar Membresía';
    const botonTexto = tipoAccion === 'cambio' ? 'Confirmar Cambio' : 'Pagar';

    document.querySelector('#modal-confirmacion h2').textContent = titulo;
    document.getElementById('modal-detalles').innerHTML = `
        <strong>Membresía:</strong> ${membresia.tipo}<br>
        <strong>Precio:</strong> ${membresia.precio} €<br>
        <strong>Duración:</strong> ${membresia.duracion} mes(es)<br>
        <strong>Método de Pago:</strong> ${metodoPago}<br><br>
        <em>Nota:</em> Al realizar este cambio, perderás los beneficios y ventajas de tu membresía anterior. 
        Sin embargo, seguirás teniendo acceso a las clases a las que ya estuvieras apuntado.
    `;
    document.querySelector('#form-cambio-pago button[type="submit"]').textContent = botonTexto;

    document.getElementById('modal-confirmacion').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-confirmacion').style.display = 'none';
}






