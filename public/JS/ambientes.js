/* EdenAir — Editar ambiente: selector de tipo de espacio y rangos.
 * --------------------------------------------------------------------------
 * VISTA:  app/Views/ambientes/editar.php
 * DATOS:  cada radio [data-ea-tipo] trae en data-valores los rangos de su
 *         tipo (los arma EnvironmentPresetService: no hay números acá).
 *
 * QUÉ HACE:
 *   1. Al elegir un tipo, carga sus valores recomendados en los campos.
 *   2. Avisa si los números son los del tipo o quedaron "a medida".
 *   3. Deja volver a los valores del tipo con un botón.
 *   4. Mantiene el nombre del tipo en el texto de ayuda del campo Nombre.
 *
 * Sin JS el formulario sigue andando: los campos son inputs normales y el
 * tipo se elige igual con los radios.
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    var TOLERANCIA = 0.001; // los decimales de la base (20.00) vs el preset (20)

    function alEstarListo(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    alEstarListo(function () {
        var form = document.querySelector("[data-ea-ambiente]");
        if (!form) return;

        var radios     = Array.prototype.slice.call(form.querySelectorAll("[data-ea-tipo]"));
        var campos     = Array.prototype.slice.call(form.querySelectorAll("[data-ea-valor]"));
        var estado     = form.querySelector("[data-ea-estado]");
        var estadoTxt  = form.querySelector("[data-ea-estado-texto]");
        var restaurar  = form.querySelector("[data-ea-restaurar]");
        var restTipo   = form.querySelector("[data-ea-restaurar-tipo]");
        var nombre     = form.querySelector("[data-ea-nombre]");
        var nombreTipo = form.querySelector("[data-ea-nombre-tipo]");

        if (!radios.length || !campos.length) return;

        /** El radio marcado (o el primero, por las dudas). */
        function tipoElegido() {
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) return radios[i];
            }
            return radios[0];
        }

        /** Los valores recomendados del tipo elegido. */
        function valoresDelTipo(radio) {
            try {
                return JSON.parse(radio.getAttribute("data-valores"));
            } catch (e) {
                return null;
            }
        }

        /** Escribe en los campos los valores del tipo. */
        function aplicarValores(radio) {
            var valores = valoresDelTipo(radio);
            if (!valores) return;

            campos.forEach(function (campo) {
                var clave = campo.getAttribute("data-ea-valor");
                if (valores[clave] !== undefined) campo.value = String(valores[clave]);
            });
        }

        /** ¿Los campos coinciden con los valores del tipo elegido? */
        function coincideConTipo(radio) {
            var valores = valoresDelTipo(radio);
            if (!valores) return true;

            return campos.every(function (campo) {
                var esperado = Number(valores[campo.getAttribute("data-ea-valor")]);
                var actual   = parseFloat(campo.value);
                if (isNaN(actual)) return false;

                return Math.abs(actual - esperado) <= TOLERANCIA;
            });
        }

        /** Pone al día el cartelito de estado, el botón de restaurar y la ayuda. */
        function sincronizar() {
            var radio = tipoElegido();
            var label = radio.getAttribute("data-label") || "";
            var libre = radio.getAttribute("data-libre") === "1";
            var igual = coincideConTipo(radio);

            if (nombreTipo) nombreTipo.textContent = label;
            if (nombre && nombre.value.trim() === "") nombre.placeholder = label;
            if (restTipo) restTipo.textContent = label;

            if (estadoTxt) {
                estadoTxt.textContent = libre
                    ? "Valores propios"
                    : (igual ? "Valores de " + label : "Ajustados a medida");
            }
            if (estado) {
                estado.classList.toggle("tone-info", libre || igual);
                estado.classList.toggle("tone-warning", !libre && !igual);
            }
            // En "Personalizable" no hay nada a lo que volver.
            if (restaurar) restaurar.hidden = libre || igual;
        }

        radios.forEach(function (radio) {
            radio.addEventListener("change", function () {
                // Cambiar de tipo carga sus rangos: es lo que se espera al elegirlo.
                if (radio.getAttribute("data-libre") !== "1") aplicarValores(radio);
                sincronizar();
            });
        });

        campos.forEach(function (campo) {
            campo.addEventListener("input", sincronizar);
        });

        if (restaurar) {
            restaurar.addEventListener("click", function () {
                aplicarValores(tipoElegido());
                sincronizar();
            });
        }

        sincronizar();
    });
})();
