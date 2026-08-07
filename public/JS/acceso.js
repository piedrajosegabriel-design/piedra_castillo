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
 *    <button type="button" data-ver-password="#miPassword">Mostrar</button>
 *    Podés apuntar a varios campos a la vez:
 *    <button type="button" data-ver-password="#pass, #confirmar">Mostrar</button>
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

            boton.textContent = estaOculto ? "Ocultar" : "Mostrar";
            boton.setAttribute("aria-label", estaOculto ? "Ocultar contraseña" : "Mostrar contraseña");
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
