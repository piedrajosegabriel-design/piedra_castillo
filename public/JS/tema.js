document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.getElementById("input");
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

    function actualizarToggle(tema) {
        if (!toggle) {
            return;
        }

        toggle.checked = tema === "dark";
        toggle.setAttribute("aria-checked", tema === "dark" ? "true" : "false");
    }

    function aplicarTema(tema) {
        raiz.setAttribute("data-theme", tema);
        guardarTema(tema);
        actualizarToggle(tema);
    }

    if (toggle) {
        toggle.addEventListener("change", function () {
            aplicarTema(toggle.checked ? "dark" : "light");
        });
    }

    aplicarTema(leerTemaGuardado());
});
