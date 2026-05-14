document.addEventListener("DOMContentLoaded", function () {
    var panel = document.getElementById("panelAmbiente");
    var form = document.getElementById("formAmbiente");
    var cards = Array.prototype.slice.call(document.querySelectorAll("[data-preset-card]"));
    var summary = document.getElementById("resumenAmbiente");
    var customBlock = document.getElementById("bloquePersonalizado");
    var customName = document.getElementById("custom_name");
    var minTemperature = document.getElementById("min_temperature");
    var maxTemperature = document.getElementById("max_temperature");
    var minHumidity = document.getElementById("min_humidity");
    var maxHumidity = document.getElementById("max_humidity");
    var maxCo2 = document.getElementById("max_co2");
    var submitButton = document.getElementById("botonAmbiente");
    var presetDataNode = document.getElementById("presetData");
    var previewName = document.getElementById("previewNombre");
    var previewState = document.getElementById("previewEstado");
    var previewDescription = document.getElementById("previewDescripcion");
    var previewTemperature = document.getElementById("previewTemperatura");
    var previewHumidity = document.getElementById("previewHumedad");
    var previewCo2 = document.getElementById("previewCo2");
    var presets = {};

    if (presetDataNode) {
        try {
            presets = JSON.parse(presetDataNode.textContent || "{}");
        } catch (error) {
            presets = {};
        }
    }

    function getSelectedEnvironment() {
        var checked = document.querySelector("input[name='environment_type']:checked");
        return checked ? checked.value : "";
    }

    function formatNumber(value, decimals) {
        var numericValue = parseFloat(value);

        if (!isFinite(numericValue)) {
            numericValue = 0;
        }

        return numericValue.toFixed(decimals);
    }

    function formatInteger(value) {
        var numericValue = parseInt(value, 10);

        if (!isFinite(numericValue)) {
            numericValue = 0;
        }

        return String(numericValue);
    }

    function fallbackValue(input, fallback) {
        if (!input) {
            return fallback;
        }

        var value = input.value.trim();
        return value !== "" ? value : fallback;
    }

    function updatePreview() {
        var selectedKey = getSelectedEnvironment() || "hogar";
        var preset = presets[selectedKey] || presets.hogar;

        if (!preset) {
            return;
        }

        var isCustom = selectedKey === "personalizable";
        var minTempValue = isCustom ? fallbackValue(minTemperature, preset.min_temperature) : preset.min_temperature;
        var maxTempValue = isCustom ? fallbackValue(maxTemperature, preset.max_temperature) : preset.max_temperature;
        var minHumidityValue = isCustom ? fallbackValue(minHumidity, preset.min_humidity) : preset.min_humidity;
        var maxHumidityValue = isCustom ? fallbackValue(maxHumidity, preset.max_humidity) : preset.max_humidity;
        var maxCo2Value = isCustom ? fallbackValue(maxCo2, preset.max_co2) : preset.max_co2;
        var customNameValue = customName ? customName.value.trim() : "";
        var previewTitle = isCustom && customNameValue !== "" ? customNameValue : preset.label;

        if (panel) {
            panel.setAttribute("data-preset", selectedKey);
        }

        cards.forEach(function (card) {
            var input = card.querySelector("input[type='radio']");
            card.classList.toggle("activa", !!input && input.checked);
        });

        if (summary) {
            summary.classList.remove("resumen-ambiente-animando");
            void summary.offsetWidth;
            summary.classList.add("resumen-ambiente-animando");
        }

        if (customBlock) {
            customBlock.classList.toggle("oculto", !isCustom);
            customBlock.setAttribute("aria-hidden", isCustom ? "false" : "true");
        }

        [customName, minTemperature, maxTemperature, minHumidity, maxHumidity, maxCo2].forEach(function (input) {
            if (!input) {
                return;
            }

            input.disabled = !isCustom;
        });

        if (customName) {
            customName.required = isCustom;
        }

        if (previewName) {
            previewName.textContent = previewTitle;
        }

        if (previewState) {
            previewState.textContent = isCustom ? "Perfil personalizable" : "Preset listo";
        }

        if (previewDescription) {
            previewDescription.textContent = isCustom
                ? "Tu propio perfil con rangos base ajustados a medida."
                : preset.description;
        }

        if (previewTemperature) {
            previewTemperature.textContent = formatNumber(minTempValue, 1) + " a " + formatNumber(maxTempValue, 1) + " C";
        }

        if (previewHumidity) {
            previewHumidity.textContent = formatInteger(minHumidityValue) + " a " + formatInteger(maxHumidityValue) + " %";
        }

        if (previewCo2) {
            previewCo2.textContent = formatInteger(maxCo2Value) + " ppm";
        }
    }

    cards.forEach(function (card) {
        var input = card.querySelector("input[type='radio']");

        if (input) {
            input.addEventListener("change", updatePreview);
        }
    });

    [customName, minTemperature, maxTemperature, minHumidity, maxHumidity, maxCo2].forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener("input", updatePreview);
    });

    if (form && submitButton) {
        form.addEventListener("submit", function () {
            submitButton.disabled = true;
            submitButton.textContent = "Preparando panel...";
        });
    }

    updatePreview();
});
