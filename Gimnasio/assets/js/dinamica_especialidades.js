function configurarMonitoresPorEspecialidad(especialidadSelectId, monitorSelectId) {
    const especialidadSelect = document.getElementById(especialidadSelectId);
    const monitorSelect = document.getElementById(monitorSelectId);

    const cargarMonitores = () => {
        const especialidadOption = especialidadSelect.options[especialidadSelect.selectedIndex];
        const monitoresData = especialidadOption ? especialidadOption.getAttribute('data-monitores') : null;

        monitorSelect.innerHTML = '<option value="" disabled selected>Cargando monitores...</option>';

        if (monitoresData) {
            const monitores = monitoresData.split(',');
            monitorSelect.innerHTML = '<option value="" disabled selected>Seleccionar monitor</option>';
            const selectedMonitor = monitorSelect.dataset.selectedMonitor; // Leer monitor seleccionado

            monitores.forEach(monitor => {
                const [id, nombre, disponibilidad] = monitor.split(':');
                const option = document.createElement('option');
                option.value = id;
                option.textContent = nombre;

                if (id === selectedMonitor) {
                    option.selected = true;
                }

                if (disponibilidad === 'disponible') {
                    monitorSelect.appendChild(option);
                }
            });

            // Habilitar el selector si hay monitores disponibles o un monitor seleccionado
            monitorSelect.disabled = monitorSelect.options.length <= 1 && !selectedMonitor;
        } else {
            monitorSelect.innerHTML = '<option value="" disabled selected>No hay monitores disponibles</option>';
            monitorSelect.disabled = true;
        }
    };

    especialidadSelect.addEventListener('change', cargarMonitores);

    // Si ya hay una especialidad seleccionada al cargar, cargar monitores inmediatamente
    if (especialidadSelect.value) {
        cargarMonitores();
    }
}
