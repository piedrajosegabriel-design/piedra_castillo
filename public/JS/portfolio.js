/* EdenAir — Portfolio: scroll-spy, mobile drawer, anchor offset, placeholders */
(function () {
    "use strict";

    function ready(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    ready(function () {
        var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        var navbar  = document.querySelector(".ea-navbar");

        /* -------- Navbar shrink + altura dinámica para offset --------- */
        function syncNavbarScrolled() {
            if (!navbar) return;
            navbar.classList.toggle("is-scrolled", window.scrollY > 14);
        }
        function syncNavHeight() {
            if (!navbar) return;
            var h = Math.round(navbar.getBoundingClientRect().height);
            document.documentElement.style.setProperty("--ea-nav-h", h + "px");
        }
        syncNavbarScrolled();
        syncNavHeight();
        window.addEventListener("scroll", syncNavbarScrolled, { passive: true });
        window.addEventListener("resize", syncNavHeight);

        function getNavbarOffset() {
            if (!navbar) return 80;
            return navbar.getBoundingClientRect().height + 12;
        }

        /* -------- Scroll suave para anchors locales ------------------- */
        function scrollToTarget(target) {
            if (!target) return;
            var rect = target.getBoundingClientRect();
            var top  = window.scrollY + rect.top - getNavbarOffset();
            if (top < 0) top = 0;
            window.scrollTo({ top: top, behavior: reducedMotion ? "auto" : "smooth" });
        }

        document.addEventListener("click", function (event) {
            var link = event.target.closest && event.target.closest('a[href^="#"]');
            if (!link) return;
            var href = link.getAttribute("href");
            if (!href || href.length < 2) return;
            var target = document.getElementById(href.slice(1));
            if (!target) return;
            event.preventDefault();
            scrollToTarget(target);
            if (history.replaceState) history.replaceState(null, "", href);
        });

        /* -------- Si entra con #anchor en URL, ajustar offset --------- */
        if (location.hash && location.hash.length > 1) {
            var initialTarget = document.getElementById(location.hash.slice(1));
            if (initialTarget) {
                window.requestAnimationFrame(function () {
                    window.setTimeout(function () { scrollToTarget(initialTarget); }, 50);
                });
            }
        }

        /* -------- Mobile drawer (replica del patrón de inicio.js) ----- */
        var navToggle = document.querySelector("[data-ea-nav-toggle]");
        var mobileNav = document.querySelector("[data-ea-mobile-nav]");

        function setMobileNav(open) {
            if (!navToggle || !mobileNav) return;
            navToggle.setAttribute("aria-expanded", open ? "true" : "false");
            mobileNav.setAttribute("aria-hidden", open ? "false" : "true");
            mobileNav.classList.toggle("is-open", open);
            document.body.classList.toggle("ea-nav-open", open);
            navToggle.setAttribute("aria-label", open ? "Cerrar menú de navegación" : "Abrir menú de navegación");
        }

        if (navToggle && mobileNav) {
            navToggle.addEventListener("click", function () {
                var isOpen = navToggle.getAttribute("aria-expanded") === "true";
                setMobileNav(!isOpen);
            });
            mobileNav.addEventListener("click", function (e) {
                var link = e.target.closest && e.target.closest("a");
                if (link) setMobileNav(false);
            });
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") setMobileNav(false);
            });
            window.addEventListener("resize", function () {
                if (window.innerWidth > 900) setMobileNav(false);
            });
        }

        /* -------- Scroll-spy dentro del portfolio --------------------- */
        var spyLinks = Array.prototype.slice.call(
            document.querySelectorAll('[data-ea-portfolio-spy] a[href^="#"]')
        );
        var spySections = spyLinks
            .map(function (link) {
                var id = link.getAttribute("href").slice(1);
                var el = document.getElementById(id);
                return el ? { id: id, el: el, link: link } : null;
            })
            .filter(Boolean);

        function setActiveLink(hash) {
            spyLinks.forEach(function (l) {
                l.classList.toggle("is-active", l.getAttribute("href") === hash);
            });
        }

        var spyTicking = false;
        function updateSpy() {
            if (!spySections.length) return;
            var offset = getNavbarOffset() + 24;
            var current = spySections[0];
            for (var i = 0; i < spySections.length; i++) {
                var rect = spySections[i].el.getBoundingClientRect();
                if (rect.top - offset <= 0) current = spySections[i];
                else break;
            }
            setActiveLink("#" + current.id);
        }

        window.addEventListener("scroll", function () {
            if (spyTicking) return;
            spyTicking = true;
            window.requestAnimationFrame(function () {
                updateSpy();
                spyTicking = false;
            });
        }, { passive: true });
        updateSpy();

        /* -------- Scroll reveal suave para secciones ------------------ */
        var reveals = document.querySelectorAll("[data-reveal]");
        if (reveals.length) {
            if (reducedMotion || typeof IntersectionObserver !== "function") {
                reveals.forEach(function (el) { el.classList.add("is-visible"); });
            } else {
                var obs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("is-visible");
                            obs.unobserve(entry.target);
                        }
                    });
                /* threshold 0: las secciones largas (análisis de mercado mide
                   ~12.000px en mobile) nunca llegan a un ratio de 0.08 porque
                   el viewport es una fracción mínima de su altura → quedaban
                   con opacity:0 para siempre en el celular. */
                }, { threshold: 0, rootMargin: "0px 0px -6% 0px" });
                reveals.forEach(function (el) { obs.observe(el); });
            }
        }

        /* -------- Chart.js: encuesta Eden Air (datos manuales) ---------- */
        // TODO: Reemplazar estos datos manuales por datos importados desde Google Sheets/MySQL
        // cuando esté lista la integración.
        if (typeof window.Chart === "function") {
            var dark = document.documentElement.getAttribute("data-theme") === "dark";
            var C = {
                green:  "#4A7A55",
                mid:    "#6B9E72",
                light:  "#BCD2BD",
                citrus: "#C9D870",
                breath: "#B8D5D0",
                dark:   "#1C4029",
                muted:  "#A8C4A8",
                text:   dark ? "rgba(226,230,218,0.82)" : "rgba(28,40,32,0.72)",
                grid:   dark ? "rgba(255,255,255,0.07)" : "rgba(0,0,0,0.07)"
            };

            Chart.defaults.font.family = "'DM Sans', sans-serif";
            Chart.defaults.font.size   = 12;
            Chart.defaults.color       = C.text;

            function mkBar(labels, data, colors, horizontal) {
                return {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderRadius: 4,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        indexAxis: horizontal ? "y" : "x",
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: function (ctx) {
                                var v = horizontal ? ctx.parsed.x : ctx.parsed.y;
                                return " " + v + " resp. (" + Math.round(v / 17 * 100) + "%)";
                            }}}
                        },
                        scales: {
                            x: { ticks: { color: C.text }, grid: { color: C.grid } },
                            y: { ticks: { color: C.text }, grid: { color: C.grid } }
                        }
                    }
                };
            }

            function mkDoughnut(labels, data, colors) {
                return {
                    type: "doughnut",
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: { color: C.text, padding: 12, font: { size: 11 } }
                            },
                            tooltip: { callbacks: { label: function (ctx) {
                                return " " + ctx.label + ": " + ctx.parsed + " resp.";
                            }}}
                        }
                    }
                };
            }

            function init(id, cfg) {
                var el = document.getElementById(id);
                if (el) new Chart(el, cfg);
            }

            // 1 · Tipo de espacio
            init("ea-chart-1", mkBar(
                ["Hogar", "Escuela/Facultad", "Oficina", "Habitación", "Trabajo"],
                [10, 4, 1, 1, 1],
                [C.green, C.mid, C.light, C.breath, C.citrus],
                true
            ));

            // 2 · Rango de edad
            init("ea-chart-2", mkBar(
                ["15 – 20", "21 – 30", "31 – 40", "41 en adelante"],
                [12, 2, 2, 1],
                [C.green, C.mid, C.light, C.breath],
                true
            ));

            // 3 · Incomodidad por temperatura
            init("ea-chart-3", mkBar(
                ["Muy frecuentemente", "Frecuentemente", "A veces", "Rara vez", "Nunca"],
                [3, 7, 5, 1, 1],
                [C.dark, C.green, C.mid, C.light, C.muted],
                false
            ));

            // 4 · Importancia del confort automático (opciones "Muy importante" combinadas)
            init("ea-chart-4", mkDoughnut(
                ["Muy importante (combinado)", "Importante"],
                [11, 6],
                [C.green, C.light]
            ));

            // 5 · Interés en automatización
            init("ea-chart-5", mkDoughnut(
                ["Sí", "Tal vez"],
                [11, 6],
                [C.green, C.citrus]
            ));

            // 6 · Prioridades del producto
            init("ea-chart-6", mkBar(
                ["Amable c/ el medio ambiente", "Funciones", "Utilidad", "Precio", "Estética", "Velocidad"],
                [5, 4, 4, 3, 1, 0],
                [C.citrus, C.green, C.mid, C.light, C.breath, C.muted],
                true
            ));

            // 7 · Sustentabilidad — pregunta abierta, mostrada como tags (sin canvas)

            // 8 · Utilidad de la personalización
            init("ea-chart-8", mkDoughnut(
                ["Muy útil", "Útil", "No tan necesario"],
                [10, 6, 1],
                [C.green, C.light, C.muted]
            ));

            // 9 · Disposición de precio
            init("ea-chart-9", mkBar(
                ["$20k – $40k", "$40k – $60k", "$60k – $80k", "$80k en adelante"],
                [2, 7, 7, 1],
                [C.light, C.green, C.mid, C.dark],
                true
            ));
        }
    });
})();
