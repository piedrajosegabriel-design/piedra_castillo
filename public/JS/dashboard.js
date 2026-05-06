document.addEventListener("DOMContentLoaded", function () {
    var dashboard = document.getElementById("dashboard");
    var menuButton = document.getElementById("menuButton");
    var updateTime = document.getElementById("updateTime");
    var tempMetric = document.getElementById("tempMetric");
    var humMetric = document.getElementById("humMetric");
    var airMetric = document.getElementById("airMetric");
    var tempGauge = document.getElementById("tempGauge");
    var humGauge = document.getElementById("humGauge");
    var airGauge = document.getElementById("airGauge");

    function setCurrentTime() {
        if (!updateTime) return;
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, "0");
        var mm = String(now.getMinutes()).padStart(2, "0");
        updateTime.textContent = "Actualizado " + hh + ":" + mm;
    }

    function randomRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function setGaugeFill(element, value) {
        if (!element) return;
        element.style.setProperty("--gauge-fill", clamp(value, 8, 100) + "%");
    }

    function updateMetrics() {
        var temperature = randomRange(21.8, 26.9);
        var humidity = randomRange(42, 64);
        var airStates = [
            { label: "Excelente", fill: 84 },
            { label: "Equilibrada", fill: 74 },
            { label: "Adecuada", fill: 67 }
        ];
        var airState = airStates[Math.floor(Math.random() * airStates.length)];

        if (tempMetric) tempMetric.textContent = temperature.toFixed(1) + " \u00b0C";
        if (humMetric) humMetric.textContent = humidity.toFixed(0) + "%";
        if (airMetric) airMetric.textContent = airState.label;

        setGaugeFill(tempGauge, ((temperature - 18) / 12) * 100);
        setGaugeFill(humGauge, humidity);
        setGaugeFill(airGauge, airState.fill);

        setCurrentTime();
    }

    if (menuButton && dashboard) {
        menuButton.addEventListener("click", function () {
            if (window.innerWidth <= 860) {
                dashboard.classList.toggle("sidebar-open");
            }
            dashboard.classList.toggle("sidebar-closed");
        });
    }

    setCurrentTime();
    updateMetrics();
    setInterval(updateMetrics, 5000);
});
