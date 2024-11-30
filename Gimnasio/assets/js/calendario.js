let nombreMes = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

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

let fechaActual = new Date();
let diaActual = fechaActual.getDate();
let numeroMes = fechaActual.getMonth();
let currentYear = fechaActual.getFullYear();
let semanaActual = getDateWeek(fechaActual);

let fechas = document.getElementById('fechas');
let semana = document.getElementById('semana');
let mes = document.getElementById('mes');
let anyo = document.getElementById('anyo');

let prevMonthDOM = document.getElementById('mesPrevio');
let nextMonthDOM = document.getElementById('mesPosterior');

semana.textContent = semanaActual.toString();
mes.textContent = nombreMes[numeroMes];
anyo.textContent = currentYear.toString();


