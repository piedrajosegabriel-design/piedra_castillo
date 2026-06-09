/* EdenAir — Portfolio · capa GSAP
 * --------------------------------------------------------------------------
 * ScrollSmoother (scroll suave) para el portfolio, replicando el patrón de la
 * landing (JS/inicio-gsap.js). Se carga DESPUÉS de gsap/ScrollTrigger/
 * ScrollSmoother (CDN) y de JS/portfolio.js.
 *
 * Accesibilidad: con prefers-reduced-motion NO se inicializa el smooth scroll
 * (queda el scroll nativo). Lo mismo si el CDN de GSAP no cargó. En táctil
 * (smoothTouch:0) se usa el scroll nativo, que se siente mejor.
 *
 * Anchors: el smoother necesita su propio scrollTo, así que interceptamos los
 * clicks en fase de captura y frenamos el handler nativo de portfolio.js con
 * stopImmediatePropagation (idéntico criterio que en la landing).
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    function init() {
        var gsap = window.gsap;
        if (!gsap) {
            if (window.console) console.warn("[EdenAir GSAP · portfolio] gsap no cargó (CDN). Scroll nativo.");
            return;
        }

        if (window.ScrollTrigger)  gsap.registerPlugin(window.ScrollTrigger);
        if (window.ScrollSmoother) gsap.registerPlugin(window.ScrollSmoother);

        var wrapper = document.querySelector("#smooth-wrapper");
        var content = document.querySelector("#smooth-content");
        if (!wrapper || !content) {
            if (window.console) console.warn("[EdenAir GSAP · portfolio] Falta #smooth-wrapper/#smooth-content.");
            return;
        }

        var mm = gsap.matchMedia();

        mm.add("(prefers-reduced-motion: no-preference)", function () {
            if (!window.ScrollSmoother) {
                if (window.console) console.warn("[EdenAir GSAP · portfolio] ScrollSmoother no disponible. Scroll nativo.");
                return;
            }

            var smoother = window.ScrollSmoother.create({
                wrapper: wrapper,
                content: content,
                smooth: 1.2,
                effects: true,
                smoothTouch: 0,
                normalizeScroll: false
            });

            window.__eaSmoother = smoother;
            document.body.classList.add("ea-has-smooth");
            document.documentElement.style.scrollBehavior = "auto";

            // --- Anchors internos → smoother.scrollTo respetando el navbar ---
            function navOffset() {
                var nav = document.querySelector(".ea-navbar");
                return (nav ? nav.getBoundingClientRect().height : 72) + 12;
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
                // Evita que el handler de anchors (bubble) de portfolio.js corra.
                event.stopImmediatePropagation();

                var y = smoother.offset(target, "top top") - navOffset();
                if (y < 0) y = 0;
                smoother.scrollTo(y, true);
                if (history.replaceState) history.replaceState(null, "", href);
            }
            document.addEventListener("click", onAnchorCapture, true);

            // Si entró con #anchor en la URL, posicionar con el smoother.
            if (location.hash && location.hash.length > 1) {
                var initialTarget = document.getElementById(location.hash.slice(1));
                if (initialTarget) {
                    requestAnimationFrame(function () {
                        var y = smoother.offset(initialTarget, "top top") - navOffset();
                        if (y < 0) y = 0;
                        smoother.scrollTo(y, false);
                    });
                }
            }

            // Recalcular medidas cuando cargan las fuentes (cambian alturas).
            if (document.fonts && document.fonts.ready && window.ScrollTrigger) {
                document.fonts.ready.then(function () { window.ScrollTrigger.refresh(); });
            }

            // Cleanup si el media query deja de matchear (reduced-motion en SO).
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
