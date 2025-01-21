document.querySelector('form').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevenir envío por defecto

    const nombre = document.getElementById('nombre').value.trim();
    const idMonitor = document.getElementById('id_monitor').value;
    const idEspecialidad = document.getElementById('id_especialidad').value;
    const fecha = document.getElementById('fecha').value;
    const horario = document.getElementById('horario').value;
    const duracion = parseInt(document.getElementById('duracion').value, 10);
    const capacidad = parseInt(document.getElementById('capacidad').value, 10);

    if (!nombre || !idMonitor || !idEspecialidad || !fecha || !horario || isNaN(duracion) || duracion <= 0 || isNaN(capacidad) || capacidad <= 0) {
        mostrarMensajeError("Todos los campos son obligatorios y deben contener valores válidos.");
        return false;
    }

    const ahora = new Date();
    const fechaSeleccionada = new Date(`${fecha}T${horario}`);
    if (fechaSeleccionada < ahora) {
        mostrarMensajeError("La fecha y hora deben ser futuras.");
        return false;
    }

    // Si todo está correcto, envía el formulario
    this.submit();
});