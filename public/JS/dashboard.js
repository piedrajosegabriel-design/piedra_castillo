document.addEventListener("DOMContentLoaded", function () {
    var dashboard = document.getElementById("dashboard");
    var menuButton = document.getElementById("menuButton");
    var updateTime = document.getElementById("updateTime");
    var tempMetric = document.getElementById("tempMetric");
    var humMetric = document.getElementById("humMetric");
    var airMetric = document.getElementById("airMetric");

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

    function updateMetrics() {
        if (tempMetric) tempMetric.textContent = randomRange(21.8, 26.9).toFixed(1) + " \u00b0C";
        if (humMetric) humMetric.textContent = randomRange(42, 64).toFixed(0) + "%";
        if (airMetric) airMetric.textContent = ["Buena", "Estable", "Correcta"][Math.floor(Math.random() * 3)];
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
