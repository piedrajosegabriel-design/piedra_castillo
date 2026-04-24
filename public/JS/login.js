document.addEventListener("DOMContentLoaded", function () {
    // Referencias a los elementos que vamos a animar o controlar.
    var runId = "initial";
    var debugUrl = "http://127.0.0.1:7485/ingest/e70a9ff8-dd80-42b3-a7de-06e52d5bf193";
    var loginBox = document.querySelector(".login-box");
    var toggleBtn = document.getElementById("togglePassword");
    var passwordInput = document.getElementById("password");
    var allInputs = document.querySelectorAll(".input-box input");
    var submitBtn = document.querySelector('form > button[type="submit"]');
    var registerLink = document.getElementById("goRegisterLink");

    function sendDebugLog(hypothesisId, location, message, data) {
        // Utilidad comun para registrar eventos de depuracion del frontend.
        fetch(debugUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Debug-Session-Id": "5c2724" },
            body: JSON.stringify({
                sessionId: "5c2724",
                runId: runId,
                hypothesisId: hypothesisId,
                location: location,
                message: message,
                data: data || {},
                timestamp: Date.now()
            })
        }).catch(function () { });
    }

    sendDebugLog("H1", "public/JS/login.js:28", "DOM loaded and elements collected", {
        hasLoginBox: !!loginBox,
        hasToggleBtn: !!toggleBtn,
        hasPasswordInput: !!passwordInput,
        inputsCount: allInputs.length,
        hasSubmitBtn: !!submitBtn,
        hasRegisterLink: !!registerLink
    });

    if (registerLink) {
        // Sirve para confirmar que el link secundario existe y apunta bien.
        sendDebugLog("H5", "public/JS/login.js:40", "Register link rendered", {
            href: registerLink.getAttribute("href")
        });
    }

    if (loginBox) {
        // Activa la animacion de entrada de la tarjeta.
        requestAnimationFrame(function () {
            loginBox.classList.add("loaded");
        });
    }

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener("click", function () {
            // Alterna entre mostrar y ocultar la contrasena.
            var wasPassword = passwordInput.type === "password";
            passwordInput.type = wasPassword ? "text" : "password";
            toggleBtn.textContent = wasPassword ? "Ocultar" : "Ver";
            toggleBtn.setAttribute("aria-label", wasPassword ? "Ocultar contrase\u00f1a" : "Mostrar contrase\u00f1a");

            // Dejamos rastro del cambio para depurar la interaccion.
            sendDebugLog("H2", "public/JS/login.js:58", "Password toggle interaction", {
                previousType: wasPassword ? "password" : "text",
                newType: passwordInput.type
            });
        });
    }

    allInputs.forEach(function (input) {
        input.addEventListener("focus", function () {
            // Marca visualmente el bloque activo para mejorar foco del usuario.
            var wrapper = input.closest(".input-box");
            if (wrapper) {
                wrapper.classList.add("input-active");
            }

            // Permite ver en el log si el foco llego correctamente al input.
            sendDebugLog("H3", "public/JS/login.js:76", "Input focus animation applied", {
                inputName: input.name || "unknown",
                hasWrapper: !!wrapper
            });
        });

        input.addEventListener("blur", function () {
            var wrapper = input.closest(".input-box");
            if (wrapper) {
                wrapper.classList.remove("input-active");
            }
        });
    });

    if (submitBtn) {
        // Solo da feedback visual al boton.
        submitBtn.addEventListener("mouseenter", function () {
            submitBtn.classList.add("is-hovered");
        });

        submitBtn.addEventListener("mouseleave", function () {
            submitBtn.classList.remove("is-hovered");
        });

        submitBtn.addEventListener("click", function () {
            // El click real lo maneja el navegador con el submit del form.
            // Aqui solo registramos que el usuario llego a esa accion.
            sendDebugLog("H4", "public/JS/login.js:103", "Submit button interaction reached", {
                hoverClass: submitBtn.classList.contains("is-hovered")
            });
        });
    }
});
