let nombreMes = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
let nombreDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

function getDateWeek(date) {
    const currentDate = 
        (typeof date === 'object') ? date : new Date();
    const januaryFirst = 
        new Date(currentDate.getFullYear(), 0, 1);
    const daysToNextMonday = 
        (januaryFirst.getDay() === 1) ? 0 : 
        (7 - januaryFirst.getDay()) % 7;
    const nextMonday = 
        new Date(currentDate.getFullYear(), 0, 
        januaryFirst.getDate() + daysToNextMonday);

    return (currentDate < nextMonday) ? 52 : 
    Math.ceil((currentDate - nextMonday) / (24 * 3600 * 1000) / 7) + 1;
}

function isSameWeek(date1, date2){
    const startOfWeek1 = new Date(date1); 
    const startOfWeek2 = new Date(date2); 
    startOfWeek1.setDate(date1.getDate() - date1.getDay() + 1); 
    startOfWeek2.setDate(date2.getDate() - date2.getDay() + 1); 
    return startOfWeek1.getTime() === startOfWeek2.getTime(); 
}

let fechaActual = new Date();
let diaActual = fechaActual.getDate();
let numeroMes = fechaActual.getMonth();
let currentYear = fechaActual.getFullYear();
let semanaActual = getDateWeek(fechaActual);

let horas = document.getElementById('horas');
let calendarioContenido = document.getElementById('calendarioContenido');
let semana = document.getElementById('semana');
let mes = document.getElementById('mes');
let anyo = document.getElementById('anyo');

let prevWeekDOM = document.getElementById('semanaPrevia'); 
let nextWeekDOM = document.getElementById('semanaPosterior');

semana.textContent = semanaActual.toString();
mes.textContent = nombreMes[numeroMes];
anyo.textContent = currentYear.toString();

//llamada a las funciones al hacer click sobre las flechas mes anterior y posterior
prevWeekDOM.addEventListener('click', lastWeek); 
nextWeekDOM.addEventListener('click', nextWeek);

//es año bisiesto?
function isLeap(){
    return((currentYear % 100 !==0) && (currentYear % 4 ===0) || (currentYear % 400 ===0));
}

/*
//sacar numero de la semana
function startDay() {
    let start = new Date(currentYear, numeroMes, 1);
    return((start.getDay()-1) === -1) ? 6 : start.getDay()-1;
}
*/

//ir a semana anterior
function lastWeek() { 
    fechaActual.setDate(fechaActual.getDate() - 7); 
    setNewDate(); 
}

//ir a la próxima semana
function nextWeek() { 
    fechaActual.setDate(fechaActual.getDate() + 7); 
    setNewDate(); 
}


function setNewDate() { 
    diaActual = fechaActual.getDate(); 
    numeroMes = fechaActual.getMonth(); 
    currentYear = fechaActual.getFullYear(); 
    semanaActual = getDateWeek(fechaActual); 
    
    semana.textContent = semanaActual.toString(); 
    mes.textContent = nombreMes[numeroMes]; 
    anyo.textContent = currentYear.toString(); 
    
    // Actualizar las fechas en la semana 
    updateWeekDates(); 
    //Actualizar las horas
    updateHours();
    // Colocar las clases en el calendario
    placeClasses();
} 

//actualizar numeros de semana
function updateWeekDates() { 
    let startOfWeek = new Date(fechaActual); 
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay() + 1);// Ajustar para que empiece el lunes 
    
    calendarioContenido.innerHTML = '';
    
    
    for (let i = 0; i < 7; i++) { 
        let day = new Date(startOfWeek); 
        day.setDate(startOfWeek.getDate() + i); 
        
        // Crear un contenedor para día de la semana y fecha 
        let dayContainer = document.createElement('div'); 
        dayContainer.className = 'calendarioDiaFecha'; 
        
        // Crear elemento para el día de la semana 
        let dayNameElement = document.createElement('div'); 
        dayNameElement.className = 'calendarioDia'; 
        dayNameElement.textContent = nombreDias[i]; 
        dayContainer.appendChild(dayNameElement); 
        
        // Crear elemento para la fecha 
        let dayElement = document.createElement('div'); 
        dayElement.className = 'calendarioFecha'; 
        dayElement.textContent = day.getDate() + ' ' + nombreMes[day.getMonth()]; 
        dayContainer.appendChild(dayElement); 
        
        // Añadir las clases correspondientes al día 
        clases.forEach(clase => { 
            let claseFecha = new Date(clase.fecha); 
            if (isSameWeek(fechaActual, claseFecha) && claseFecha.getDay() === day.getDay()) { 
                let claseElement = document.createElement('div'); 
                claseElement.className = 'calendarioClase'; 
                claseElement.textContent = `${clase.nombre} (${clase.horario})`; 
                dayContainer.appendChild(claseElement); 
            }
        });

        // Añadir el contenedor al calendario 
        calendarioContenido.appendChild(dayContainer);
        }
    }

//actualizar horas
function updateHours(){
    horas.innerHTML = '';
    for (let i = 6; i<= 22; i++){
        let horaElement = document.createElement('div');
        horaElement.className = 'calendarioHora';
        horaElement.textContent = (i < 10 ? '0' + i : i) + ':00';
        horas.appendChild(horaElement);
    }
}
/*
// Función para colocar las clases en el calendario 
function placeClasses() { 
    clases.forEach(clase => { let claseFecha = clase.fecha; 
        let claseHora = clase.horario; 
        
        // Encontrar el contenedor de la fecha correspondiente 
        let dayElements = document.querySelectorAll(`[data-fecha="${claseFecha}"]`); 
        dayElements.forEach(dayElement => { 
            // Crear el elemento de la clase 
            let claseElement = document.createElement('div'); 
            claseElement.className = 'claseEvento'; 
            claseElement.textContent = `${clase.nombre} (${clase.horario})`; 
            // Añadir el elemento de la clase al contenedor de la fecha 
            dayElement.appendChild(claseElement); 
        }); 
    }); 
}
*/
// Inicializar la vista de la semana actual 
setNewDate();

