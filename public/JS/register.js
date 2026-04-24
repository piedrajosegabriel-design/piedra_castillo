document.addEventListener("DOMContentLoaded", function () {
    // Referencias a inputs y ayudas visuales del formulario.
    var runId = "register-initial";
    var debugUrl = "http://127.0.0.1:7485/ingest/e70a9ff8-dd80-42b3-a7de-06e52d5bf193";
    var registerBox = document.querySelector(".register-box");
    var registerForm = document.getElementById("registerForm");
    var passwordInput = document.getElementById("registerPassword");
    var confirmInput = document.getElementById("confirmPassword");
    var toggleBtn = document.getElementById("toggleRegisterPassword");
    var strengthBar = document.getElementById("strengthBar");
    var strengthText = document.getElementById("strengthText");
    var matchText = document.getElementById("matchText");
    var submitBtn = document.getElementById("registerSubmit");
    var inputs = document.querySelectorAll(".input-box input");

    function sendDebugLog(hypothesisId, location, message, data) {
        // Utilidad comun para dejar rastro de interacciones del registro.
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

    sendDebugLog("R1", "public/JS/register.js:30", "Register DOM collected", {
        hasRegisterBox: !!registerBox,
        hasForm: !!registerForm,
        hasPassword: !!passwordInput,
        hasConfirm: !!confirmInput,
        hasToggle: !!toggleBtn,
        inputsCount: inputs.length
    });

    if (registerBox) {
        // Dispara la animacion de entrada de la tarjeta.
        requestAnimationFrame(function () {
            registerBox.classList.add("loaded");
        });
    }

    function getPasswordScore(value) {
        // Puntaje simple para mostrar seguridad visual de la contrasena.
        // No reemplaza validaciones reales del backend; solo da feedback al usuario.
        var score = 0;
        if (value.length >= 8) score += 30;
        if (/[A-Z]/.test(value)) score += 20;
        if (/[0-9]/.test(value)) score += 20;
        if (/[^A-Za-z0-9]/.test(value)) score += 30;
        return Math.min(score, 100);
    }

    function updateStrength() {
        if (!passwordInput || !strengthBar || !strengthText) return;

        // Actualiza barra y texto segun el puntaje calculado.
        var score = getPasswordScore(passwordInput.value);
        strengthBar.style.width = score + "%";
        if (score < 40) {
            strengthBar.style.backgroundColor = "#cf8b8b";
            strengthText.textContent = "Seguridad: baja";
        } else if (score < 75) {
            strengthBar.style.backgroundColor = "#d1b682";
            strengthText.textContent = "Seguridad: media";
        } else {
            strengthBar.style.backgroundColor = "#86b596";
            strengthText.textContent = "Seguridad: alta";
        }

        // Trazamos el puntaje para depuracion del comportamiento visual.
        sendDebugLog("R2", "public/JS/register.js:68", "Password strength updated", {
            length: passwordInput.value.length,
            score: score
        });
    }

    function updateMatch() {
        if (!passwordInput || !confirmInput || !matchText) return;

        // Compara ambas contrasenas y muestra si coinciden.
        if (!confirmInput.value) {
            matchText.textContent = "A\u00fan no verificadas";
            matchText.classList.remove("match-ok", "match-error");
            return;
        }
        var matched = passwordInput.value === confirmInput.value;
        matchText.textContent = matched ? "Contrase\u00f1as coinciden" : "Contrase\u00f1as no coinciden";
        matchText.classList.toggle("match-ok", matched);
        matchText.classList.toggle("match-error", !matched);

        // Tambien registramos si las claves coinciden o no.
        sendDebugLog("R3", "public/JS/register.js:90", "Password match evaluated", {
            confirmLength: confirmInput.value.length,
            matched: matched
        });
    }

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener("click", function () {
            // Mostrar u ocultar ambas contrasenas al mismo tiempo.
            var isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            if (confirmInput) {
                confirmInput.type = isPassword ? "text" : "password";
            }
            toggleBtn.textContent = isPassword ? "Ocultar" : "Ver";
            toggleBtn.setAttribute("aria-label", isPassword ? "Ocultar contrase\u00f1a" : "Mostrar contrase\u00f1a");
        });
    }

    if (passwordInput) {
        // Cada cambio en la clave principal actualiza seguridad y coincidencia.
        passwordInput.addEventListener("input", function () {
            updateStrength();
            updateMatch();
        });
    }

    if (confirmInput) {
        // La segunda clave solo afecta al texto de coincidencia.
        confirmInput.addEventListener("input", updateMatch);
    }

    inputs.forEach(function (input) {
        input.addEventListener("focus", function () {
            // Resalta el campo activo.
            var wrapper = input.closest(".input-box");
            if (wrapper) wrapper.classList.add("input-active");
        });
        input.addEventListener("blur", function () {
            var wrapper = input.closest(".input-box");
            if (wrapper) wrapper.classList.remove("input-active");
        });
    });

    if (submitBtn) {
        submitBtn.addEventListener("mouseenter", function () {
            submitBtn.classList.add("is-hovered");
        });
        submitBtn.addEventListener("mouseleave", function () {
            submitBtn.classList.remove("is-hovered");
        });
    }

    if (registerForm) {
        registerForm.addEventListener("submit", function () {
            // El backend sigue siendo el responsable final de validar.
            sendDebugLog("R4", "public/JS/register.js:142", "Register form submit reached", {
                hasMismatchClass: matchText ? matchText.classList.contains("match-error") : false
            });
        });
    }
});
