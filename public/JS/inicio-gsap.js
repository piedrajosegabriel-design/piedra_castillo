/* EdenAir — Landing · capa GSAP
 * --------------------------------------------------------------------------
 * FASE 1: ScrollSmoother (scroll suave) + integración de anchors.
 * Se carga DESPUÉS de gsap/ScrollTrigger/ScrollSmoother (CDN) y de inicio.js.
 *
 * Accesibilidad: si el usuario pide prefers-reduced-motion NO se inicializa el
 * smooth scroll — queda el scroll nativo del navegador. Lo mismo si por algún
 * motivo el CDN de GSAP no cargó.
 *
 * Mobile: smoothTouch:0 → en pantallas táctiles dejamos el scroll nativo, que
 * se siente mejor y evita lag.
 *
 * Las animaciones de scroll (reveals, parallax, pinned, contadores) se suman
 * en fases posteriores dentro de este mismo archivo.
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    function init() {
        var gsap = window.gsap;
        if (!gsap) {
            if (window.console) console.warn("[EdenAir GSAP] gsap no cargó (CDN). Scroll nativo.");
            return;
        }

        // Registrar plugins disponibles vía CDN (todos libres desde GSAP 3.13).
        if (window.ScrollTrigger)  gsap.registerPlugin(window.ScrollTrigger);
        if (window.ScrollSmoother) gsap.registerPlugin(window.ScrollSmoother);

        var wrapper = document.querySelector("#smooth-wrapper");
        var content = document.querySelector("#smooth-content");
        if (!wrapper || !content) {
            if (window.console) console.warn("[EdenAir GSAP] Falta #smooth-wrapper/#smooth-content.");
            return;
        }

        // matchMedia: el smooth scroll vive SOLO cuando el usuario admite movimiento.
        // Si cambia a reduced-motion, gsap.matchMedia revierte y dispara el cleanup.
        var mm = gsap.matchMedia();

        mm.add("(prefers-reduced-motion: no-preference)", function () {
            if (!window.ScrollSmoother) {
                if (window.console) console.warn("[EdenAir GSAP] ScrollSmoother no disponible. Scroll nativo.");
                return;
            }

            var smoother = window.ScrollSmoother.create({
                wrapper: wrapper,
                content: content,
                smooth: 1.2,        // segundos de "catch-up": sensación suave sin sentirse pesado
                effects: true,      // habilita data-speed / data-lag (parallax en fases siguientes)
                smoothTouch: 0,     // touch: scroll nativo (mejor en mobile)
                normalizeScroll: false
            });

            window.__eaSmoother = smoother;
            document.body.classList.add("ea-has-smooth");
            // scroll-behavior:smooth del CSS pelea con smoother.scrollTo → lo apagamos.
            document.documentElement.style.scrollBehavior = "auto";

            // --- Anchors internos → smoother.scrollTo respetando la altura del navbar ---
            function navOffset() {
                var nav = document.querySelector(".ea-navbar");
                return (nav ? nav.getBoundingClientRect().height : 60) + 12;
            }

            function onAnchorCapture(event) {
                if (event.defaultPrevented) return;
                var link = event.target.closest && event.target.closest('a[href^="#"]');
                if (!link) return;
                var href = link.getAttribute("href");
                if (!href || href.length < 2) return;
                var target = document.getElementById(href.slice(1));
                if (!target) return;

                event.preventDefault();
                // Evita que el handler de anchors nativo de inicio.js también corra.
                event.stopImmediatePropagation();

                var y = smoother.offset(target, "top top") - navOffset();
                if (y < 0) y = 0;
                smoother.scrollTo(y, true);
                if (history.replaceState) history.replaceState(null, "", href);
            }
            // Fase de captura → corre antes que el listener (bubble) de inicio.js.
            document.addEventListener("click", onAnchorCapture, true);

            // La barra de scroll moderna (thumb flotante) la maneja
            // JS/ea-scrollbar.js, que funciona con y sin ScrollSmoother.

            // ---- Video "Experience": pin + scrub con ScrollTrigger ----
            // Reemplaza el sticky + scrollY de inicio.js (que se rompe con el
            // transform del smoother). pinSpacing:false porque la sección ya
            // reserva la altura de scroll. El scroll suavizado de ScrollSmoother
            // hace que el frame del video siga lo que se ve en pantalla.
            var expSection = document.querySelector("[data-ea-experience]");
            if (expSection && window.ScrollTrigger) {
                var expStage = expSection.querySelector(".ea-experience-stage");
                var expVideo = expSection.querySelector("[data-ea-experience-video]");
                var expTexts = Array.prototype.slice.call(
                    expSection.querySelectorAll(".ea-experience-text")
                );
                // Ventanas de visibilidad de cada texto (idénticas al original).
                var EXP_WINDOWS = [
                    { in: 0.02, out: 0.22 },
                    { in: 0.20, out: 0.42 },
                    { in: 0.38, out: 0.60 },
                    { in: 0.56, out: 0.78 },
                    { in: 0.74, out: 0.98 }
                ];

                var applyExperience = function (progress) {
                    for (var i = 0; i < expTexts.length; i++) {
                        var w = EXP_WINDOWS[i];
                        if (!w) continue;
                        var vis = progress >= w.in && progress <= w.out;
                        if (vis !== expTexts[i].classList.contains("is-visible")) {
                            expTexts[i].classList.toggle("is-visible", vis);
                        }
                    }
                    if (expVideo) {
                        var dur = expVideo.duration;
                        if (isFinite(dur) && dur > 0) {
                            var t = (0.02 + progress * 0.96) * dur; // padding en bordes
                            if (Math.abs(expVideo.currentTime - t) > 0.015) {
                                try { expVideo.currentTime = t; } catch (e) {}
                            }
                        }
                    }
                };

                window.ScrollTrigger.create({
                    trigger: expSection,
                    start: "top top",
                    end: "bottom bottom",
                    pin: expStage,
                    pinSpacing: false,   // la sección ya tiene la altura del scrub
                    onUpdate: function (self) { applyExperience(self.progress); }
                });

                applyExperience(0);
                if (expVideo) {
                    // Cuando el video conoce su duración, recalibrar medidas del pin.
                    expVideo.addEventListener("loadedmetadata", function () {
                        window.ScrollTrigger.refresh();
                    });
                }
            }

            // Recalcular medidas cuando terminan de cargar las fuentes (cambia alturas).
            if (document.fonts && document.fonts.ready && window.ScrollTrigger) {
                document.fonts.ready.then(function () { window.ScrollTrigger.refresh(); });
            }

            // Cleanup automático si el media query deja de matchear (p.ej. el usuario
            // activa reduced-motion en el SO).
            return function () {
                document.removeEventListener("click", onAnchorCapture, true);
                document.body.classList.remove("ea-has-smooth");
                document.documentElement.style.scrollBehavior = "";
                window.__eaSmoother = null;
                smoother.kill();
            };
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
