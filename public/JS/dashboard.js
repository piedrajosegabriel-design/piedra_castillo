document.addEventListener("DOMContentLoaded", function () {
    // Elementos del panel que vamos a actualizar con valores de ejemplo.
    var runId = "dashboard-futuristic";
    var debugUrl = "http://127.0.0.1:7485/ingest/e70a9ff8-dd80-42b3-a7de-06e52d5bf193";
    var tempMetric = document.getElementById("tempMetric");
    var humMetric = document.getElementById("humMetric");
    var airMetric = document.getElementById("airMetric");
    var cards = document.querySelectorAll(".tilt-card");

    function sendDebugLog(hypothesisId, location, message, data) {
        // Utilidad comun para enviar eventos de depuracion del dashboard.
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

    sendDebugLog("DB1", "public/JS/dashboard.js:27", "Dashboard widgets loaded", {
        hasTemp: !!tempMetric,
        hasHum: !!humMetric,
        hasAir: !!airMetric,
        cards: cards.length
    });

    function randomRange(min, max) {
        // Genera datos de demo mientras no conectemos el ESP32 real.
        return Math.random() * (max - min) + min;
    }

    function updateMetrics() {
        // Refresca las metricas visibles del dashboard.
        // Hoy son valores simulados; mas adelante pueden venir de la base o del ESP32.
        if (tempMetric) tempMetric.textContent = randomRange(21.5, 27.3).toFixed(1) + " \u00b0C";
        if (humMetric) humMetric.textContent = randomRange(38, 62).toFixed(0) + "%";
        if (airMetric) airMetric.textContent = ["\u00d3ptimo", "Bueno", "Moderado"][Math.floor(Math.random() * 3)];
    }

    // Carga inicial inmediata y luego refresco periodico de demo.
    updateMetrics();
    setInterval(updateMetrics, 2500);

    cards.forEach(function (card) {
        // Efecto visual suave al pasar el mouse.
        card.addEventListener("mouseenter", function () {
            card.classList.add("pulse-neon");
        });
        card.addEventListener("mouseleave", function () {
            card.classList.remove("pulse-neon");
        });
    });
});
