document.addEventListener("DOMContentLoaded", function () {
    var passwordInput = document.getElementById("loginPassword");
    var toggleButton = document.getElementById("verPasswordLogin");
    var loginForm = document.getElementById("formLogin");
    var submitButton = document.getElementById("botonLogin");

    if (toggleButton && passwordInput) {
        toggleButton.addEventListener("click", function () {
            var isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";
            toggleButton.textContent = isHidden ? "Ocultar" : "Mostrar";
            toggleButton.setAttribute("aria-label", isHidden ? "Ocultar contrasena" : "Mostrar contrasena");
        });
    }

    if (loginForm && submitButton) {
        loginForm.addEventListener("submit", function () {
            submitButton.disabled = true;
            submitButton.textContent = "Validando...";
        });
    }
});
