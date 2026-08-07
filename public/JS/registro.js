/**
 * EdenAir — registro: medidor de seguridad y coincidencia de contraseñas.
 *
 * Lo genérico (mostrar/ocultar la contraseña, bloquear el botón al enviar)
 * NO está acá: vive en acceso.js, compartido con el resto de las pantallas.
 * Este archivo solo tiene lo propio del alta de una cuenta.
 */
document.addEventListener("DOMContentLoaded", function () {
    var password     = document.getElementById("registroPassword");
    var confirmacion = document.getElementById("confirmPassword");
    var barra        = document.getElementById("fuerzaBarra");
    var textoFuerza  = document.getElementById("fuerzaTexto");
    var textoIgual   = document.getElementById("coincideTexto");

    if (!password) return;

    var ROJO  = "#be5159";
    var AMBAR = "#c67a26";
    var VERDE = "#3f8b5e";

    /* -------------------- Medidor de seguridad -------------------- */

    /** Puntaje 0-100 según largo y variedad de caracteres. */
    function puntaje(valor) {
        var total = 0;
        if (valor.length >= 8)            total += 30;
        if (/[a-z]/.test(valor))          total += 20;
        if (/[A-Z]/.test(valor))          total += 20;
        if (/\d/.test(valor))             total += 20;
        if (/[^A-Za-z0-9]/.test(valor))   total += 10;
        return Math.min(total, 100);
    }

    function actualizarFuerza() {
        if (!barra || !textoFuerza) return;

        var valor = puntaje(password.value);
        barra.style.width = Math.max(valor, 10) + "%";

        if (valor < 40) {
            barra.style.backgroundColor = ROJO;
            textoFuerza.textContent = "Seguridad baja.";
        } else if (valor < 75) {
            barra.style.backgroundColor = AMBAR;
            textoFuerza.textContent = "Seguridad media.";
        } else {
            barra.style.backgroundColor = VERDE;
            textoFuerza.textContent = "Seguridad alta.";
        }
    }

    /* -------------------- ¿Coinciden las dos contraseñas? -------------------- */
    function actualizarCoincidencia() {
        if (!confirmacion || !textoIgual) return;

        if (confirmacion.value === "") {
            textoIgual.textContent = "Esperando confirmación de contraseña.";
            textoIgual.style.color = "";
        } else if (password.value === confirmacion.value) {
            textoIgual.textContent = "Las contraseñas coinciden.";
            textoIgual.style.color = VERDE;
        } else {
            textoIgual.textContent = "Las contraseñas no coinciden.";
            textoIgual.style.color = ROJO;
        }
    }

    password.addEventListener("input", function () {
        actualizarFuerza();
        actualizarCoincidencia();
    });

    if (confirmacion) {
        confirmacion.addEventListener("input", actualizarCoincidencia);
    }

    actualizarFuerza();
    actualizarCoincidencia();
});
