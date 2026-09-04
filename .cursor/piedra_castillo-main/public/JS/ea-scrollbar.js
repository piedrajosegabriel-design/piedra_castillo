/* EdenAir — Barra de scroll moderna flotante
 * --------------------------------------------------------------------------
 * Reemplaza el scrollbar nativo (y el viejo indicador de progreso) por un
 * thumb fino tipo píldora, redondeado, sin track ni marco, que recorre el
 * borde derecho. Aparece al scrollear / hover, se desvanece al quedar quieto
 * y se puede arrastrar.
 *
 * Funciona igual en la landing (con ScrollSmoother) y en el panel (scroll
 * nativo): siempre lee window.scrollY, que ScrollSmoother mantiene válido.
 * Para arrastrar, si ScrollSmoother está activo usa smoother.scrollTop(y);
 * si no, window.scrollTo.
 *
 * Accesibilidad: el scroll nativo (rueda, teclado, gestos) sigue intacto —
 * esta barra es sólo un indicador/arrastre visual (aria-hidden). Respeta
 * prefers-reduced-motion (sin fundidos; queda visible y discreta) y en
 * pantallas táctiles se oculta para dejar el scroll nativo del sistema.
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    var MIN_THUMB = 40;     // alto mínimo del thumb (px)
    var HIDE_DELAY = 1000;  // ms de inactividad antes de desvanecer

    function init() {
        if (document.querySelector("[data-ea-floatbar]")) return; // no duplicar

        var docEl = document.documentElement;
        var reduce = window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        // Estructura: <div.ea-floatbar><div.ea-floatbar-thumb></div></div>
        var bar = document.createElement("div");
        bar.className = "ea-floatbar";
        bar.setAttribute("data-ea-floatbar", "");
        bar.setAttribute("aria-hidden", "true");

        var thumb = document.createElement("div");
        thumb.className = "ea-floatbar-thumb";
        bar.appendChild(thumb);

        // Va al final del body → fuera de #smooth-wrapper (clave para ScrollSmoother).
        document.body.appendChild(bar);
        docEl.classList.add("ea-floatbar-on"); // el CSS oculta el scrollbar nativo

        var hideTimer = 0;
        var dragging = false;
        var trackH = 0;
        var thumbH = 0;

        function metrics() {
            var scrollH = docEl.scrollHeight;
            var clientH = window.innerHeight;
            return {
                scrollH: scrollH,
                clientH: clientH,
                maxScroll: Math.max(0, scrollH - clientH),
                y: window.scrollY || window.pageYOffset || docEl.scrollTop || 0
            };
        }

        function position(m) {
            m = m || metrics();
            var travel = trackH - thumbH;
            var p = m.maxScroll > 0 ? m.y / m.maxScroll : 0;
            p = p < 0 ? 0 : (p > 1 ? 1 : p);
            thumb.style.transform = "translateY(" + Math.round(p * travel) + "px)";
        }

        function layout() {
            var m = metrics();
            trackH = bar.clientHeight; // alto real del track (con sus insets)

            if (m.maxScroll <= 1 || trackH <= 0) {
                // Página sin scroll: no mostramos nada.
                bar.classList.remove("is-active");
                bar.style.visibility = "hidden";
                return;
            }
            bar.style.visibility = "";
            thumbH = Math.max(MIN_THUMB, Math.round(trackH * (m.clientH / m.scrollH)));
            if (thumbH > trackH) thumbH = trackH;
            thumb.style.height = thumbH + "px";
            position(m);
        }

        function show() {
            bar.classList.add("is-active");
            if (reduce || dragging) return;
            window.clearTimeout(hideTimer);
            hideTimer = window.setTimeout(function () {
                bar.classList.remove("is-active");
            }, HIDE_DELAY);
        }

        var ticking = false;
        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(function () {
                    position();
                    ticking = false;
                });
            }
            show();
        }

        // ---- Arrastre del thumb ----
        function setScroll(y) {
            var sm = window.__eaSmoother;
            if (sm && typeof sm.scrollTop === "function") {
                sm.scrollTop(y); // ScrollSmoother fija el objetivo y suaviza
            } else {
                window.scrollTo(0, y);
            }
        }

        var dragStartPointer = 0;
        var dragStartTop = 0;

        function onPointerDown(e) {
            if (e.button != null && e.button !== 0) return;
            dragging = true;
            bar.classList.add("is-dragging", "is-active");
            window.clearTimeout(hideTimer);

            var m = metrics();
            var travel = trackH - thumbH;
            dragStartTop = m.maxScroll > 0 ? (m.y / m.maxScroll) * travel : 0;
            dragStartPointer = e.clientY;

            if (thumb.setPointerCapture) {
                try { thumb.setPointerCapture(e.pointerId); } catch (err) {}
            }
            e.preventDefault();
        }

        function onPointerMove(e) {
            if (!dragging) return;
            var travel = trackH - thumbH;
            var top = dragStartTop + (e.clientY - dragStartPointer);
            top = top < 0 ? 0 : (top > travel ? travel : top);
            thumb.style.transform = "translateY(" + top + "px)";
            var p = travel > 0 ? top / travel : 0;
            setScroll(p * metrics().maxScroll);
        }

        function onPointerUp() {
            if (!dragging) return;
            dragging = false;
            bar.classList.remove("is-dragging");
            show();
        }

        thumb.addEventListener("pointerdown", onPointerDown);
        thumb.addEventListener("pointermove", onPointerMove);
        thumb.addEventListener("pointerup", onPointerUp);
        thumb.addEventListener("pointercancel", onPointerUp);

        bar.addEventListener("pointerenter", show);

        // ---- Listeners globales / recálculo ----
        window.addEventListener("scroll", onScroll, { passive: true });
        window.addEventListener("resize", function () { layout(); show(); });
        window.addEventListener("load", layout);
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(layout);
        }
        // Recalcular el tamaño del thumb si el contenido cambia de alto
        // (p.ej. "ver más" en el panel, secciones que cargan, etc.).
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () { layout(); });
            ro.observe(document.body);
        }

        layout();
        show(); // breve aparición inicial para señalar que existe
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
