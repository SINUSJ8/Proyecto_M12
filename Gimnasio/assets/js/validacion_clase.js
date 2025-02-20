// Agrega un evento al formulario con id 'form_clase' para manejar su envío
document.getElementById('form_clase').addEventListener('submit', function (e) {
    e.preventDefault(); // Evita el envío del formulario por defecto

    // Obtiene los valores de los campos del formulario y los procesa
    const nombre = document.getElementById('nombre').value.trim();
    const idMonitor = document.getElementById('id_monitor').value;
    const idEspecialidad = document.getElementById('id_especialidad').value;
    const fecha = document.getElementById('fecha').value;
    const horario = document.getElementById('horario').value;
    const duracion = parseInt(document.getElementById('duracion').value, 10);
    const capacidad = parseInt(document.getElementById('capacidad').value, 10);

    // Validación: verifica que todos los campos estén completos y contengan valores válidos
    if (!nombre || !idMonitor || !idEspecialidad || !fecha || !horario || isNaN(duracion) || duracion <= 0 || isNaN(capacidad) || capacidad <= 0) {
        mostrarMensajeError("Todos los campos son obligatorios y deben contener valores válidos.");
        return false; // Detiene el envío del formulario si hay errores
    }

    // Obtiene la fecha y hora actuales
    const ahora = new Date();
    // Crea un objeto de fecha combinando la fecha y la hora seleccionadas
    const fechaSeleccionada = new Date(`${fecha}T${horario}`);

    // Validación: la fecha y hora seleccionadas deben ser futuras
    if (fechaSeleccionada < ahora) {
        mostrarMensajeError("La fecha y hora deben ser futuras.");
        return false; // Detiene el envío si la fecha es inválida
    }

    // Si todas las validaciones pasan, se envía el formulario
    this.submit();
});
