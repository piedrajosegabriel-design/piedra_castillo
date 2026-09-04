/**
 * EdenAir — mega menú "Portfolio" de la barra superior pública.
 *
 * ESTRUCTURA: app/Views/partials/navbar.php
 * ESTILOS:    eden-brand.css, sección "Portfolio mega menú"
 *
 * Se engancha por atributos, no por ids:
 *   [data-ea-mega]          contenedor
 *   [data-ea-mega-trigger]  el botón "Portfolio"
 *   [data-ea-mega-panel]    el panel que se despliega
 *   [data-ea-mega-link]     cada enlace de adentro (al hacer click, cierra)
 *
 * Comportamiento: en escritorio se abre al pasar el mouse (con una demora
 * corta para que no salte solo) y el click navega al portfolio; en táctil el
 * click abre y cierra. Teclado: Escape cierra, flecha abajo entra al panel.
 */
(function () {
    "use strict";

    var DEMORA_ABRIR  = 60;    // ms antes de abrir: evita aperturas por roce
    var DEMORA_CERRAR = 180;   // ms antes de cerrar: da tiempo a bajar el mouse

    function alEstarListo(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    alEstarListo(function () {
        var menus = document.querySelectorAll("[data-ea-mega]");
        if (!menus.length) return;

        // En táctil no hay hover: el click tiene que abrir en vez de navegar.
        var hayHover = window.matchMedia("(hover: hover) and (pointer: fine)");

        menus.forEach(function (menu) {
            var disparador = menu.querySelector("[data-ea-mega-trigger]");
            var panel      = menu.querySelector("[data-ea-mega-panel]");
            if (!disparador || !panel) return;

            var timerAbrir  = 0;
            var timerCerrar = 0;

            function abrir() {
                window.clearTimeout(timerCerrar);
                menu.classList.add("is-open");
                disparador.setAttribute("aria-expanded", "true");
            }

            function cerrar() {
                window.clearTimeout(timerAbrir);
                menu.classList.remove("is-open");
                disparador.setAttribute("aria-expanded", "false");
            }

            menu.addEventListener("mouseenter", function () {
                if (!hayHover.matches) return;
                window.clearTimeout(timerCerrar);
                timerAbrir = window.setTimeout(abrir, DEMORA_ABRIR);
            });

            menu.addEventListener("mouseleave", function () {
                if (!hayHover.matches) return;
                window.clearTimeout(timerAbrir);
                timerCerrar = window.setTimeout(cerrar, DEMORA_CERRAR);
            });

            disparador.addEventListener("click", function (evento) {
                if (hayHover.matches) return;   // en escritorio el click navega
                evento.preventDefault();
                if (menu.classList.contains("is-open")) cerrar(); else abrir();
            });

            /* -------------------- Teclado -------------------- */
            disparador.addEventListener("focus", abrir);

            disparador.addEventListener("keydown", function (evento) {
                if (evento.key === "Escape") {
                    cerrar();
                    disparador.blur();
                }
                if (evento.key === "ArrowDown") {
                    evento.preventDefault();
                    abrir();
                    var primero = panel.querySelector("a");
                    if (primero) primero.focus();
                }
            });

            panel.addEventListener("keydown", function (evento) {
                if (evento.key === "Escape") {
                    cerrar();
                    disparador.focus();
                }
            });

            /* -------------------- Cierre -------------------- */
            panel.addEventListener("click", function (evento) {
                if (evento.target.closest("a[data-ea-mega-link]")) cerrar();
            });

            document.addEventListener("click", function (evento) {
                if (!menu.contains(evento.target)) cerrar();
            });
        });
    });
})();
