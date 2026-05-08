document.addEventListener("DOMContentLoaded", function () {
    var menu = document.querySelector("[data-menu-panel]");
    var enlaces = menu ? Array.from(menu.querySelectorAll("a[href^='#']")) : [];
    var secciones = Array.from(document.querySelectorAll("[data-seccion-panel]"));

    if (enlaces.length === 0 || secciones.length === 0) {
        return;
    }

    function activar(hash) {
        enlaces.forEach(function (enlace) {
            enlace.classList.toggle("activo", enlace.getAttribute("href") === hash);
        });
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (entrada.isIntersecting) {
                activar("#" + entrada.target.id);
            }
        });
    }, {
        rootMargin: "-35% 0px -50% 0px",
        threshold: 0.1
    });

    secciones.forEach(function (seccion) {
        if (seccion.id) {
            observador.observe(seccion);
        }
    });

    enlaces.forEach(function (enlace) {
        enlace.addEventListener("click", function () {
            activar(enlace.getAttribute("href"));
        });
    });

    if (window.location.hash) {
        activar(window.location.hash);
    }
});
