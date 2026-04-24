document.addEventListener("DOMContentLoaded", function () {
    // Estado minimo y elementos interactivos de la landing.
    var runId = "home-futuristic";
    var debugUrl = "http://127.0.0.1:7485/ingest/e70a9ff8-dd80-42b3-a7de-06e52d5bf193";
    var cards = document.querySelectorAll(".tilt-card");
    var pills = document.querySelectorAll(".tech-pill");

    function sendDebugLog(hypothesisId, location, message, data) {
        // Envia trazas de depuracion sin interrumpir la experiencia si falla.
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

    // #region agent log
    sendDebugLog("HM1", "public/JS/home.js:27", "Home interactive widgets ready", {
        cards: cards.length,
        pills: pills.length
    });
    // #endregion

    // Efecto 3D suave sobre tarjetas al mover el mouse.
    cards.forEach(function (card) {
        card.addEventListener("mousemove", function (event) {
            var rect = card.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;
            var rotateY = (x / rect.width - 0.5) * 12;
            var rotateX = (0.5 - y / rect.height) * 12;
            card.style.transform = "perspective(900px) rotateX(" + rotateX.toFixed(2) + "deg) rotateY(" + rotateY.toFixed(2) + "deg)";
        });
        card.addEventListener("mouseleave", function () {
            card.style.transform = "";
        });
    });

    // Recorre los pills de tecnologias para resaltar uno por vez.
    if (pills.length > 0) {
        var index = 0;
        setInterval(function () {
            pills.forEach(function (pill, i) {
                pill.classList.toggle("active-pill", i === index);
            });
            index = (index + 1) % pills.length;
        }, 1200);
    }
});
