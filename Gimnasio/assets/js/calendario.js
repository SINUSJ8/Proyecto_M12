let nombreMes = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
let nombreDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Obtiene el número de la semana del año para una fecha dada
function getDateWeek(date) {
    const currentDate = (typeof date === 'object') ? date : new Date();
    const januaryFirst = new Date(currentDate.getFullYear(), 0, 1);

    // Calcula los días hasta el próximo lunes desde el 1 de enero
    const daysToNextMonday = (januaryFirst.getDay() === 1) ? 0 : (7 - januaryFirst.getDay()) % 7;
    const nextMonday = new Date(currentDate.getFullYear(), 0, januaryFirst.getDate() + daysToNextMonday);

    // Determina el número de la semana basado en el primer lunes del año
    return (currentDate < nextMonday) ? 52 : Math.ceil((currentDate - nextMonday) / (24 * 3600 * 1000) / 7) + 1;
}

// Comprueba si dos fechas están en la misma semana
function isSameWeek(date1, date2) {
    const startOfWeek1 = new Date(date1);
    const startOfWeek2 = new Date(date2);

    // Ajusta las fechas para obtener el lunes de cada semana
    startOfWeek1.setDate(date1.getDate() - date1.getDay() + 1);
    startOfWeek2.setDate(date2.getDate() - date2.getDay() + 1);

    return startOfWeek1.getTime() === startOfWeek2.getTime();
}

// Obtiene la fecha actual y sus datos relevantes
let fechaActual = new Date();
let diaActual = fechaActual.getDate();
let numeroMes = fechaActual.getMonth();
let currentYear = fechaActual.getFullYear();
let semanaActual = getDateWeek(fechaActual);

// Obtiene elementos del DOM
let horas = document.getElementById('horas');
let calendarioContenido = document.getElementById('calendarioContenido');
let semana = document.getElementById('semana');
let mes = document.getElementById('mes');
let anyo = document.getElementById('anyo');
let prevWeekDOM = document.getElementById('semanaPrevia');
let nextWeekDOM = document.getElementById('semanaPosterior');

// Muestra la información inicial en la interfaz
semana.textContent = semanaActual.toString();
mes.textContent = nombreMes[numeroMes];
anyo.textContent = currentYear.toString();

// Asigna eventos para navegar entre semanas
prevWeekDOM.addEventListener('click', lastWeek);
nextWeekDOM.addEventListener('click', nextWeek);

// Comprueba si el año actual es bisiesto
function isLeap() {
    return ((currentYear % 100 !== 0) && (currentYear % 4 === 0) || (currentYear % 400 === 0));
}

// Retrocede una semana
function lastWeek() {
    fechaActual.setDate(fechaActual.getDate() - 7);
    setNewDate();
}

// Avanza una semana
function nextWeek() {
    fechaActual.setDate(fechaActual.getDate() + 7);
    setNewDate();
}

// Actualiza la fecha en la interfaz y los elementos relacionados
function setNewDate() {
    diaActual = fechaActual.getDate();
    numeroMes = fechaActual.getMonth();
    currentYear = fechaActual.getFullYear();
    semanaActual = getDateWeek(fechaActual);

    semana.textContent = semanaActual.toString();
    mes.textContent = nombreMes[numeroMes];
    anyo.textContent = currentYear.toString();

    // Refresca la vista del calendario
    updateWeekDates();
    updateHours();
    placeClasses();
}

// Actualiza los días de la semana en el calendario
function updateWeekDates() {
    let startOfWeek = new Date(fechaActual);

    // Ajusta para que la semana empiece en lunes
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay() + 1);

    // Limpia el contenido anterior del calendario
    calendarioContenido.innerHTML = '';

    for (let i = 0; i < 7; i++) {
        let day = new Date(startOfWeek);
        day.setDate(startOfWeek.getDate() + i);

        // Contenedor del día con fecha
        let dayContainer = document.createElement('div');
        dayContainer.className = 'calendarioDiaFecha';

        // Elemento con el nombre del día
        let dayNameElement = document.createElement('div');
        dayNameElement.className = 'calendarioDia';
        dayNameElement.textContent = nombreDias[i];
        dayContainer.appendChild(dayNameElement);

        // Elemento con la fecha
        let dayElement = document.createElement('div');
        dayElement.className = 'calendarioFecha';
        dayElement.textContent = day.getDate() + ' ' + nombreMes[day.getMonth()];
        dayContainer.appendChild(dayElement);

        // Agrega clases programadas para este día
        clases.forEach(clase => {
            let claseFecha = new Date(clase.fecha);
            if (isSameWeek(fechaActual, claseFecha) && claseFecha.getDay() === day.getDay()) {
                let claseElement = document.createElement('div');
                claseElement.className = 'calendarioClase';
                claseElement.textContent = `${clase.nombre} (${clase.horario})`;
                dayContainer.appendChild(claseElement);
            }
        });

        // Agrega el contenedor al calendario
        calendarioContenido.appendChild(dayContainer);
    }
}

// Genera la lista de horas en el calendario
function updateHours() {
    horas.innerHTML = '';

    for (let i = 6; i <= 22; i++) {
        let horaElement = document.createElement('div');
        horaElement.className = 'calendarioHora';
        horaElement.textContent = (i < 10 ? '0' + i : i) + ':00';
        horas.appendChild(horaElement);
    }
}

// Inicializa la vista de la semana actual
setNewDate();
