/**
 * EdenAir — comportamiento común de las pantallas de acceso.
 * (login · registro · recuperar contraseña · restablecer contraseña)
 *
 * Antes cada pantalla repetía este mismo código con ids distintos: login.js,
 * registro.js y un <script> suelto adentro de restablecer_password.php. Ahora
 * se enganchan por atributos, así que sirve igual en cualquier formulario.
 *
 * ---------------------------------------------------------------------------
 * 1. MOSTRAR / OCULTAR CONTRASEÑA
 *    El botón lo pone app/Views/partials/ojo_password.php:
 *    <?= view('partials/ojo_password', ['objetivo' => '#miPassword']) ?>
 *    Podés apuntar a varios campos a la vez:
 *    ['objetivo' => '#pass, #confirmar']
 *
 * 2. BLOQUEAR EL BOTÓN AL ENVIAR (evita el doble click y el doble alta)
 *    <form data-enviando="Validando…"> … <button type="submit">Entrar</button>
 */
document.addEventListener("DOMContentLoaded", function () {

    /* -------------------- Mostrar / ocultar contraseña -------------------- */
    document.querySelectorAll("[data-ver-password]").forEach(function (boton) {
        var campos = document.querySelectorAll(boton.getAttribute("data-ver-password"));
        if (!campos.length) return;

        boton.addEventListener("click", function () {
            var estaOculto = campos[0].type === "password";

            campos.forEach(function (campo) {
                campo.type = estaOculto ? "text" : "password";
            });

            var etiqueta = estaOculto ? "Ocultar contraseña" : "Mostrar contraseña";
            boton.setAttribute("aria-label", etiqueta);
            boton.setAttribute("title", etiqueta);

            // El botón con ojo (partials/ojo_password.php) cambia de ícono por
            // CSS mirando data-visible. El botón de solo texto, si queda alguno
            // dado de alta a mano, sigue cambiando su palabra como antes.
            if (boton.querySelector("svg")) {
                boton.setAttribute("data-visible", estaOculto ? "true" : "false");
            } else {
                boton.textContent = estaOculto ? "Ocultar" : "Mostrar";
            }
        });
    });

    /* -------------------- Bloqueo del botón al enviar -------------------- */
    document.querySelectorAll("[data-enviando]").forEach(function (formulario) {
        formulario.addEventListener("submit", function () {
            var boton = formulario.querySelector('button[type="submit"]');
            if (!boton) return;

            boton.disabled = true;
            boton.textContent = formulario.getAttribute("data-enviando");
        });
    });
});
