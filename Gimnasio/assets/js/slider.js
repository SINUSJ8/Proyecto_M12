let counter = 1;
setInterval(function() {
    document.getElementById('radio' + counter).checked = true;
    counter++;
    if (counter > 5) {
        counter = 1;
    }
}, 5000);

document.addEventListener("DOMContentLoaded", function() {
    const image = document.querySelector(".slide-in-left");
    image.classList.add("active");
});

document.addEventListener("DOMContentLoaded", function() {
    const image = document.querySelector(".slide-in-right");
    image.classList.add("active");
});
