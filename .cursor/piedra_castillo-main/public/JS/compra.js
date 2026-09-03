/**
 * EdenAir — pantalla de compra (/panel/compra).
 *
 * Mientras no haya pago real, el botón "Comprar" solo muestra el aviso verde
 * durante unos segundos. Cuando se integre el cobro, este archivo se reemplaza
 * por el envío del formulario al proveedor de pago.
 */
document.addEventListener("DOMContentLoaded", function () {
    var boton = document.querySelector("[data-plan-buy]");
    var aviso = document.querySelector("[data-plan-toast]");
    if (!boton || !aviso) return;

    var temporizador;

    boton.addEventListener("click", function () {
        aviso.classList.add("is-visible");
        boton.classList.add("is-done");

        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
            aviso.classList.remove("is-visible");
            boton.classList.remove("is-done");
        }, 3200);
    });
});
