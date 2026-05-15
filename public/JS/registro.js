document.addEventListener("DOMContentLoaded", function () {
    var formRegistro = document.getElementById("formRegistro");
    var ambiente = document.getElementById("environment_type");
    var bloquePersonalizado = document.getElementById("bloquePersonalizado");
    var password = document.getElementById("registroPassword");
    var confirmacion = document.getElementById("confirmPassword");
    var botonVer = document.getElementById("verPasswordRegistro");
    var barra = document.getElementById("fuerzaBarra");
    var textoFuerza = document.getElementById("fuerzaTexto");
    var textoCoincide = document.getElementById("coincideTexto");
    var botonRegistro = document.getElementById("botonRegistro");

    function mostrarBloquePersonalizado() {
        if (!ambiente || !bloquePersonalizado) {
            return;
        }

        bloquePersonalizado.classList.toggle("oculto", ambiente.value !== "personalizable");
    }

    function puntajePassword(valor) {
        var puntaje = 0;

        if (valor.length >= 8) puntaje += 30;
        if (/[a-z]/.test(valor)) puntaje += 20;
        if (/[A-Z]/.test(valor)) puntaje += 20;
        if (/\d/.test(valor)) puntaje += 20;
        if (/[^A-Za-z0-9]/.test(valor)) puntaje += 10;

        return Math.min(puntaje, 100);
    }

    function actualizarFuerza() {
        if (!password || !barra || !textoFuerza) {
            return;
        }

        var puntaje = puntajePassword(password.value);
        barra.style.width = Math.max(puntaje, 10) + "%";

        if (puntaje < 40) {
            barra.style.backgroundColor = "#be5159";
            textoFuerza.textContent = "Seguridad baja.";
            return;
        }

        if (puntaje < 75) {
            barra.style.backgroundColor = "#c67a26";
            textoFuerza.textContent = "Seguridad media.";
            return;
        }

        barra.style.backgroundColor = "#3f8b5e";
        textoFuerza.textContent = "Seguridad alta.";
    }

    function actualizarCoincidencia() {
        if (!password || !confirmacion || !textoCoincide) {
            return;
        }

        if (confirmacion.value === "") {
        textoCoincide.textContent = "Esperando confirmación de contraseña.";
            textoCoincide.style.color = "";
            return;
        }

        if (password.value === confirmacion.value) {
        textoCoincide.textContent = "Las contraseñas coinciden.";
            textoCoincide.style.color = "#3f8b5e";
            return;
        }

        textoCoincide.textContent = "Las contraseñas no coinciden.";
        textoCoincide.style.color = "#be5159";
    }

    if (ambiente) {
        ambiente.addEventListener("change", mostrarBloquePersonalizado);
        mostrarBloquePersonalizado();
    }

    if (botonVer && password) {
        botonVer.addEventListener("click", function () {
            var oculto = password.type === "password";
            password.type = oculto ? "text" : "password";

            if (confirmacion) {
                confirmacion.type = oculto ? "text" : "password";
            }

            botonVer.textContent = oculto ? "Ocultar" : "Mostrar";
        });
    }

    if (password) {
        password.addEventListener("input", function () {
            actualizarFuerza();
            actualizarCoincidencia();
        });
    }

    if (confirmacion) {
        confirmacion.addEventListener("input", actualizarCoincidencia);
    }

    if (formRegistro && botonRegistro) {
        formRegistro.addEventListener("submit", function () {
            botonRegistro.disabled = true;
            botonRegistro.textContent = "Creando cuenta...";
        });
    }

    actualizarFuerza();
    actualizarCoincidencia();
});
