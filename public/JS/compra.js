/**
 * EdenAir — pantalla de compra (/panel/compra).
 *
 * El cobro lo hace el servidor: el botón "Comprar" es el submit de un
 * formulario POST que genera el link de Mercado Pago y redirige allá. Este
 * archivo solo cuida el botón mientras tanto:
 *
 *   1. Al enviar → queda en "Conectando con Mercado Pago…" y deshabilitado,
 *      así un doble clic no genera dos links de pago.
 *   2. Si el usuario vuelve con el botón "Atrás" del navegador, algunos
 *      navegadores muestran la página guardada en memoria (bfcache) con el
 *      botón todavía cargando y un token CSRF ya usado. En ese caso se recarga
 *      para traer un token nuevo y el botón en reposo.
 */
(function () {
    "use strict";

    var formulario = document.querySelector("[data-plan-form]");
    if (!formulario) { return; }

    var boton = formulario.querySelector("[data-plan-buy]");
    var texto = formulario.querySelector("[data-plan-buy-text]");

    formulario.addEventListener("submit", function (evento) {
        if (boton.classList.contains("is-loading")) {
            evento.preventDefault();
            return;
        }

        boton.classList.add("is-loading");
        boton.setAttribute("aria-busy", "true");
        if (texto) { texto.textContent = "Conectando con Mercado Pago…"; }

        // Se deshabilita en el próximo ciclo: si se deshabilita ya, algunos
        // navegadores cancelan el envío del formulario.
        window.setTimeout(function () { boton.disabled = true; }, 0);
    });

    window.addEventListener("pageshow", function (evento) {
        if (evento.persisted) { window.location.reload(); }
    });
})();
