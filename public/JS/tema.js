document.addEventListener("DOMContentLoaded", function () {
    var botones = document.querySelectorAll("[data-boton-tema]");
    var raiz = document.documentElement;

    function leerTemaGuardado() {
        try {
            return localStorage.getItem("tema") === "dark" ? "dark" : "light";
        } catch (error) {
            return "light";
        }
    }

    function guardarTema(tema) {
        try {
            localStorage.setItem("tema", tema);
        } catch (error) {
            return;
        }
    }

    function temaActual() {
        return raiz.getAttribute("data-theme") === "dark" ? "dark" : "light";
    }

    function actualizarBotones(tema) {
        botones.forEach(function (boton) {
            boton.textContent = tema === "dark" ? "Tema claro" : "Tema oscuro";
            boton.setAttribute("aria-pressed", tema === "dark" ? "true" : "false");
        });
    }

    function aplicarTema(tema) {
        raiz.setAttribute("data-theme", tema);
        guardarTema(tema);
        actualizarBotones(tema);
    }

    botones.forEach(function (boton) {
        boton.addEventListener("click", function () {
            aplicarTema(temaActual() === "dark" ? "light" : "dark");
        });
    });

    aplicarTema(leerTemaGuardado());
});
