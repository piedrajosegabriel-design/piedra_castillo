document.addEventListener("DOMContentLoaded", function () {
    var CLAVE_TEMA = "tema";
    var TEMA_CLARO = "light";
    var TEMA_OSCURO = "dark";
    var raiz = document.documentElement;
    // Soporta varios toggles a la vez (navbar desktop + drawer mobile, etc.)
    var toggles = Array.prototype.slice.call(
        document.querySelectorAll('.ea-theme-switch input[type="checkbox"]')
    );
    if (!toggles.length) {
        var legacy = document.getElementById("input");
        if (legacy) toggles.push(legacy);
    }

    function esTemaValido(tema) {
        return tema === TEMA_CLARO || tema === TEMA_OSCURO;
    }

    function leerTemaGuardado() {
        try {
            var tema = localStorage.getItem(CLAVE_TEMA);
            return esTemaValido(tema) ? tema : null;
        } catch (error) {
            return null;
        }
    }

    function guardarTema(tema) {
        try {
            localStorage.setItem(CLAVE_TEMA, tema);
        } catch (error) {
            return;
        }
    }

    function leerTemaDelDocumento() {
        var tema = raiz.getAttribute("data-theme");
        return esTemaValido(tema) ? tema : null;
    }

    function obtenerTemaInicial() {
        return leerTemaGuardado() || leerTemaDelDocumento() || TEMA_CLARO;
    }

    function actualizarToggle(tema) {
        if (!toggles.length) {
            return;
        }

        var esOscuro = tema === TEMA_OSCURO;

        toggles.forEach(function (toggle) {
            toggle.checked = esOscuro;
            toggle.defaultChecked = esOscuro;
            toggle.setAttribute("aria-checked", esOscuro ? "true" : "false");
            toggle.setAttribute("title", esOscuro ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
        });
    }

    function aplicarTema(tema, guardar) {
        var temaFinal = tema === TEMA_OSCURO ? TEMA_OSCURO : TEMA_CLARO;

        raiz.setAttribute("data-theme", temaFinal);

        if (guardar !== false) {
            guardarTema(temaFinal);
        }

        actualizarToggle(temaFinal);
    }

    toggles.forEach(function (toggle) {
        toggle.addEventListener("change", function () {
            var temaDestino = this.checked ? TEMA_OSCURO : TEMA_CLARO;

            // Sin animación si el usuario prefiere movimiento reducido
            if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                aplicarTema(temaDestino, true);
                return;
            }

            // Posición central del toggle para anclar la expansión
            var etiqueta = toggle.closest(".ea-theme-switch") || toggle.parentElement;
            var rect = etiqueta.getBoundingClientRect();
            var cx = rect.left + rect.width  / 2;
            var cy = rect.top  + rect.height / 2;
            var radio = Math.hypot(window.innerWidth, window.innerHeight);

            raiz.style.setProperty("--theme-x", cx + "px");
            raiz.style.setProperty("--theme-y", cy + "px");
            raiz.style.setProperty("--theme-radius", radio + "px");

            // Fallback para navegadores sin View Transitions API
            if (!document.startViewTransition) {
                aplicarTema(temaDestino, true);
                return;
            }

            document.startViewTransition(function () {
                aplicarTema(temaDestino, true);
            });
        });
    });

    window.addEventListener("storage", function (event) {
        if (event.key && event.key !== CLAVE_TEMA) {
            return;
        }

        if (esTemaValido(event.newValue)) {
            aplicarTema(event.newValue, false);
        }
    });

    aplicarTema(obtenerTemaInicial(), false);
});
