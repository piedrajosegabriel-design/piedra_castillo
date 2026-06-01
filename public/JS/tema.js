/**
 * Eden Air — Theme toggle (modo claro / oscuro).
 *
 * Diseño:
 *  - El toggle es un <input type="checkbox"> dentro de un <label> (no es un
 *    <a href="#">, así que no hay navegación ni saltos por hash).
 *  - El cambio es SIN View Transitions API: con esa API el navegador re-mapea
 *    el scroll a la nueva instantánea y, si las alturas difieren mínimamente
 *    entre temas, mueve la posición del scroll. La transición visual queda
 *    delegada a CSS (transiciones de color en el shell).
 *  - Antes de aplicar el tema desactivamos `scroll-behavior: smooth` y la
 *    transición global del body; restauramos scroll en 3 checkpoints
 *    (sync, microtask, rAF) para evitar el salto en cualquier navegador.
 */
document.addEventListener("DOMContentLoaded", function () {
    var CLAVE_TEMA = "tema";
    var TEMA_CLARO = "light";
    var TEMA_OSCURO = "dark";
    var raiz = document.documentElement;
    var toggles = Array.prototype.slice.call(
        document.querySelectorAll('.ea-theme-switch input[type="checkbox"]')
    );
    if (!toggles.length) {
        var legacy = document.getElementById("input");
        if (legacy) toggles.push(legacy);
    }

    function esTemaValido(tema) { return tema === TEMA_CLARO || tema === TEMA_OSCURO; }

    function leerTemaGuardado() {
        try {
            var tema = localStorage.getItem(CLAVE_TEMA);
            return esTemaValido(tema) ? tema : null;
        } catch (error) { return null; }
    }

    function guardarTema(tema) {
        try { localStorage.setItem(CLAVE_TEMA, tema); } catch (error) {}
    }

    function leerTemaDelDocumento() {
        var tema = raiz.getAttribute("data-theme");
        return esTemaValido(tema) ? tema : null;
    }

    function obtenerTemaInicial() {
        return leerTemaGuardado() || leerTemaDelDocumento() || TEMA_CLARO;
    }

    function actualizarToggle(tema) {
        if (!toggles.length) return;
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
        if (guardar !== false) guardarTema(temaFinal);
        actualizarToggle(temaFinal);
    }

    /**
     * Aplica el cambio de tema preservando exactamente la posición de scroll.
     * Tres checkpoints para vencer cualquier reflow:
     *   1) Sync       — inmediatamente después de cambiar el atributo.
     *   2) Microtask  — antes de que el navegador pinte.
     *   3) rAF        — después del primer paint.
     * Además anulamos temporalmente `scroll-behavior: smooth` para que el
     * scrollTo sea instantáneo, no animado.
     */
    function aplicarTemaPreservandoScroll(temaDestino) {
        var x = window.scrollX || window.pageXOffset || 0;
        var y = window.scrollY || window.pageYOffset || 0;

        var prevHtmlBehavior = raiz.style.scrollBehavior;
        var prevBodyBehavior = document.body ? document.body.style.scrollBehavior : "";
        raiz.style.scrollBehavior = "auto";
        if (document.body) document.body.style.scrollBehavior = "auto";

        aplicarTema(temaDestino, true);

        // 1) Restauración síncrona.
        window.scrollTo(x, y);

        // 2) Restauración en microtask (antes del paint).
        Promise.resolve().then(function () { window.scrollTo(x, y); });

        // 3) Restauración después del primer frame.
        window.requestAnimationFrame(function () {
            window.scrollTo(x, y);
            raiz.style.scrollBehavior = prevHtmlBehavior;
            if (document.body) document.body.style.scrollBehavior = prevBodyBehavior;
        });
    }

    toggles.forEach(function (toggle) {
        toggle.addEventListener("change", function () {
            var temaDestino = this.checked ? TEMA_OSCURO : TEMA_CLARO;
            aplicarTemaPreservandoScroll(temaDestino);
        });
    });

    // Sincroniza entre pestañas.
    window.addEventListener("storage", function (event) {
        if (event.key && event.key !== CLAVE_TEMA) return;
        if (esTemaValido(event.newValue)) aplicarTema(event.newValue, false);
    });

    aplicarTema(obtenerTemaInicial(), false);
});
