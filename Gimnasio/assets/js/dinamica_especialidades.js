//Función para cargar los monitores con la especialidad seleccionada
function configurarMonitoresPorEspecialidad(especialidadSelectId, monitorSelectId) {
    const especialidadSelect = document.getElementById(especialidadSelectId);
    const monitorSelect = document.getElementById(monitorSelectId);

    const cargarMonitores = () => {
        const especialidadOption = especialidadSelect.options[especialidadSelect.selectedIndex];
        const monitoresData = especialidadOption ? especialidadOption.getAttribute('data-monitores') : null;

        monitorSelect.innerHTML = '<option value="" disabled>Seleccionando monitores...</option>';

        if (monitoresData) {
            const monitores = monitoresData.split(',');
            monitorSelect.innerHTML = '<option value="" disabled>Seleccionar monitor</option>';

            monitores.forEach(monitor => {
                const [id, nombre, disponibilidad] = monitor.split(':');
                const option = document.createElement('option');
                option.value = id;
                option.textContent = nombre;

                if (disponibilidad === 'disponible') {
                    monitorSelect.appendChild(option);
                }
            });

            monitorSelect.disabled = monitorSelect.options.length <= 1;
        } else {
            monitorSelect.innerHTML = '<option value="" disabled>No hay monitores disponibles</option>';
            monitorSelect.disabled = true;
        }
    };

    // Cargar monitores al cambiar la especialidad
    especialidadSelect.addEventListener('change', cargarMonitores);

    // Si la página carga con una especialidad seleccionada, mostrar los monitores disponibles
    if (especialidadSelect.value) {
        cargarMonitores();
    }
}
//función para validar la fecha y la hora de la clase
function configurarRestriccionesFechaHora(fechaId, horarioId) {
    const fechaInput = document.getElementById(fechaId);
    const horarioInput = document.getElementById(horarioId);

    if (!fechaInput || !horarioInput) return;

    // Configurar la fecha mínima en el campo de fecha
    const hoy = new Date();
    const fechaMinima = hoy.toISOString().split('T')[0];
    fechaInput.setAttribute('min', fechaMinima);

    // Escuchar cambios en el campo de fecha
    fechaInput.addEventListener('change', () => {
        const fechaSeleccionada = new Date(fechaInput.value);

        if (fechaSeleccionada.toDateString() === hoy.toDateString()) {
            // Si la fecha seleccionada es hoy, establecer hora mínima
            const horas = hoy.getHours().toString().padStart(2, '0');
            const minutos = hoy.getMinutes().toString().padStart(2, '0');
            horarioInput.setAttribute('min', `${horas}:${minutos}`);
        } else {
            // Eliminar restricciones de hora si no es hoy
            horarioInput.removeAttribute('min');
        }
    });

    // Asegurarse de validar al cargar la página si ya hay una fecha seleccionada
    const fechaSeleccionadaInicial = new Date(fechaInput.value);
    if (fechaSeleccionadaInicial.toDateString() === hoy.toDateString()) {
        const horas = hoy.getHours().toString().padStart(2, '0');
        const minutos = hoy.getMinutes().toString().padStart(2, '0');
        horarioInput.setAttribute('min', `${horas}:${minutos}`);
    }
}

