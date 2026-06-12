/* EdenAir — Dashboard · capa GSAP
 * --------------------------------------------------------------------------
 * ScrollSmoother para el panel. La sidebar y el header son position:fixed
 * (no viven dentro de #smooth-content) por lo que no hay conflicto con
 * position:sticky.
 *
 * window.__eaSmoother se expone para que dashboard.js use smoother.scrollTo
 * en lugar de window.scrollTo cuando el smooth scroll está activo.
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    function init() {
        var gsap = window.gsap;
        if (!gsap) {
            if (window.console) console.warn("[EdenAir GSAP · dashboard] gsap no cargó (CDN). Scroll nativo.");
            return;
        }

        if (window.ScrollTrigger)  gsap.registerPlugin(window.ScrollTrigger);
        if (window.ScrollSmoother) gsap.registerPlugin(window.ScrollSmoother);

        var wrapper = document.querySelector("#smooth-wrapper");
        var content = document.querySelector("#smooth-content");
        if (!wrapper || !content) {
            if (window.console) console.warn("[EdenAir GSAP · dashboard] Falta #smooth-wrapper/#smooth-content.");
            return;
        }

        var mm = gsap.matchMedia();

        mm.add("(prefers-reduced-motion: no-preference)", function () {
            if (!window.ScrollSmoother) {
                if (window.console) console.warn("[EdenAir GSAP · dashboard] ScrollSmoother no disponible. Scroll nativo.");
                return;
            }

            var smoother = window.ScrollSmoother.create({
                wrapper: wrapper,
                content: content,
                smooth: 0.9,        // un poco más responsivo que la landing, dashboard se siente controlado
                effects: false,     // no hay data-speed en el dashboard
                smoothTouch: 0,
                normalizeScroll: false
            });

            window.__eaSmoother = smoother;
            document.body.classList.add("ea-has-smooth");
            document.documentElement.style.scrollBehavior = "auto";

            // El offset del header ya no se setea acá: vive en CSS
            // (.ea-main { padding-top: var(--ead-header-h) }) y dashboard.js
            // sincroniza la variable con la altura real en load/resize.

            // Cuando el sidebar se colapsa / expande, el ancho del layout cambia
            // → refrescar ScrollTrigger después de que la transición CSS termine (0.25s)
            document.addEventListener("click", function (e) {
                var btn = e.target.closest("[data-sidebar-toggle]");
                if (!btn) return;
                window.setTimeout(function () {
                    if (window.ScrollTrigger) window.ScrollTrigger.refresh();
                }, 280);
            });

            if (document.fonts && document.fonts.ready && window.ScrollTrigger) {
                document.fonts.ready.then(function () { window.ScrollTrigger.refresh(); });
            }

            return function () {
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
