/* EdenAir — Portfolio · sección 07 "Análisis de costos"
 * --------------------------------------------------------------------------
 * Tres cosas, todas opcionales: si algo no está en la página, se saltea.
 *
 *   1. LABORATORIO  → los 5 sliders recalculan costo, ganancia, punto de
 *      equilibrio y utilidad, y redibujan el gráfico. Las cuentas son las
 *      mismas que hace el PHP de la vista; el HTML ya viene con los valores
 *      del TP, así que sin JS la sección igual se lee completa.
 *   2. CONTADORES   → los números grandes cuentan desde cero al entrar en
 *      pantalla. Con prefers-reduced-motion se muestran directo.
 *   3. FILTRO       → los chips de insumos se filtran por subsistema.
 *
 * Accesibilidad: el resultado del laboratorio vive en un aria-live="polite",
 * cada slider tiene <label> y <output>, y el gráfico repite sus datos en la
 * tabla del <details> de abajo. Todo el movimiento respeta reduced-motion.
 * -------------------------------------------------------------------------- */
(function () {
    "use strict";

    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var fmt    = new Intl.NumberFormat("es-AR", { maximumFractionDigits: 0 });
    var fmt2   = new Intl.NumberFormat("es-AR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    /** $1.234 o $1.234,56 según haga falta. El signo va antes del peso: -$1.234. */
    function pesos(n) {
        var r = Math.round(n * 100) / 100;
        var abs = Math.abs(r);
        return (r < 0 ? "-$" : "$") + (abs % 1 === 0 ? fmt.format(abs) : fmt2.format(abs));
    }

    function pct(n, dec) {
        return new Intl.NumberFormat("es-AR", {
            minimumFractionDigits: dec, maximumFractionDigits: dec
        }).format(n) + " %";
    }

    /* =====================================================================
       1 · Laboratorio de costos
       ===================================================================== */

    /** Las mismas cuentas que la vista: un solo lugar donde vive la fórmula. */
    function calcular(v) {
        var varUnit = v.mpd + v.horas * v.valorHora;
        var varTot  = varUnit * v.unidades;
        var total   = varTot + v.fijos;
        var unit    = total / v.unidades;
        var contrib = v.precio - varUnit;
        var punto   = contrib > 0 ? v.fijos / contrib : Infinity;

        return {
            varUnit:  varUnit,
            total:    total,
            unit:     unit,
            ganancia: v.precio - unit,
            margen:   (v.precio - unit) / unit * 100,
            contrib:  contrib,
            punto:    punto,
            puntoEnt: isFinite(punto) ? Math.ceil(punto) : null,
            utilidad: v.unidades * v.precio - total
        };
    }

    function initLab() {
        var lab = document.querySelector("[data-cost-lab]");
        if (!lab) return;

        var base;
        try { base = JSON.parse(lab.getAttribute("data-cost-seed")); } catch (e) { return; }

        var inputs = Array.prototype.slice.call(lab.querySelectorAll("[data-cost-input]"));
        var outs   = {};
        lab.querySelectorAll("[data-cost-out]").forEach(function (el) {
            outs[el.getAttribute("data-cost-out")] = el;
        });
        var alerta = lab.querySelector("[data-cost-alert]");
        var tarjetaGanancia = lab.querySelector("[data-cost-profit]");
        var chart = crearChart(lab.querySelector("#ea-cost-chart"));

        function valores() {
            var v = { fijos: base.fijos };
            inputs.forEach(function (input) {
                v[input.getAttribute("data-cost-input")] = Number(input.value);
            });
            return v;
        }

        function pintar() {
            var v = valores();
            var r = calcular(v);

            // Cada slider muestra su propio valor al lado de la etiqueta.
            inputs.forEach(function (input) {
                var salida = document.getElementById(input.id + "-out");
                if (!salida) return;
                var unidad = input.getAttribute("data-cost-unit");
                salida.textContent = unidad === "$"
                    ? pesos(Number(input.value))
                    : input.value + " " + unidad;
            });

            if (outs.unit)     outs.unit.textContent     = pesos(r.unit);
            if (outs.ganancia) outs.ganancia.textContent = pesos(r.ganancia);
            if (outs.margen)   outs.margen.textContent   = pct(r.margen, 2) + " sobre el costo";
            if (outs.utilidad) outs.utilidad.textContent = pesos(r.utilidad);
            if (outs.punto) {
                outs.punto.textContent = r.puntoEnt === null
                    ? "No se alcanza"
                    : r.puntoEnt + (r.puntoEnt === 1 ? " unidad" : " unidades");
            }
            if (outs.puntoPct) {
                outs.puntoPct.textContent = r.puntoEnt === null
                    ? "El precio no cubre el costo variable"
                    : pct(r.puntoEnt / v.unidades * 100, 0) + " de la producción";
            }

            // Estado de error: vender por debajo del costo se avisa, no se esconde.
            var enPerdida = r.ganancia < 0;
            if (alerta) alerta.hidden = !enPerdida;
            if (tarjetaGanancia) {
                tarjetaGanancia.classList.toggle("is-good", !enPerdida);
                tarjetaGanancia.classList.toggle("is-bad", enPerdida);
            }

            if (chart) actualizarChart(chart, v, r);
        }

        inputs.forEach(function (input) { input.addEventListener("input", pintar); });

        var reset = lab.querySelector("[data-cost-reset]");
        if (reset) {
            reset.addEventListener("click", function () {
                inputs.forEach(function (input) {
                    var clave = input.getAttribute("data-cost-input");
                    if (base[clave] !== undefined) input.value = base[clave];
                });
                pintar();
            });
        }

        pintar();
    }

    /* --- Gráfico de punto de equilibrio (Chart.js, ya cargado por la vista) --- */

    function paleta() {
        var dark = document.documentElement.getAttribute("data-theme") === "dark";
        return {
            ingresos: dark ? "#C9D870" : "#4A7A55",
            costos:   dark ? "#B8D5D0" : "#A65B3D",
            punto:    dark ? "#ECF2E8" : "#1C4029",
            text:     dark ? "rgba(226,230,218,0.82)" : "rgba(28,40,32,0.72)",
            grid:     dark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)"
        };
    }

    function crearChart(canvas) {
        if (!canvas || typeof window.Chart !== "function") return null;
        var p = paleta();

        var chart = new window.Chart(canvas, {
            type: "line",
            data: {
                datasets: [
                    { label: "Ingresos", data: [], borderColor: p.ingresos, backgroundColor: p.ingresos,
                      borderWidth: 2.5, pointRadius: 0, tension: 0 },
                    { label: "Costo total", data: [], borderColor: p.costos, backgroundColor: p.costos,
                      borderWidth: 2.5, borderDash: [6, 4], pointRadius: 0, tension: 0 },
                    { label: "Punto de equilibrio", data: [], borderColor: p.punto, backgroundColor: p.punto,
                      pointRadius: 6, pointHoverRadius: 9, showLine: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reduce ? false : { duration: 220 },
                interaction: { mode: "nearest", intersect: false },
                plugins: {
                    legend: { position: "bottom", labels: { color: p.text, padding: 14, boxWidth: 14, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            title: function (items) { return items[0].parsed.x + " unidades vendidas"; },
                            label: function (ctx) { return " " + ctx.dataset.label + ": " + pesos(ctx.parsed.y); }
                        }
                    }
                },
                scales: {
                    x: {
                        type: "linear", min: 0,
                        title: { display: true, text: "Unidades vendidas en el mes", color: p.text, font: { size: 11 } },
                        ticks: { color: p.text, precision: 0 },
                        grid: { color: p.grid }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: "Pesos", color: p.text, font: { size: 11 } },
                        ticks: {
                            color: p.text,
                            callback: function (value) { return "$" + fmt.format(value / 1000) + " k"; }
                        },
                        grid: { color: p.grid }
                    }
                }
            }
        });

        // El toggle de tema cambia data-theme: se repintan los colores del gráfico.
        new MutationObserver(function () {
            var q = paleta();
            chart.data.datasets[0].borderColor = chart.data.datasets[0].backgroundColor = q.ingresos;
            chart.data.datasets[1].borderColor = chart.data.datasets[1].backgroundColor = q.costos;
            chart.data.datasets[2].borderColor = chart.data.datasets[2].backgroundColor = q.punto;
            chart.options.plugins.legend.labels.color = q.text;
            ["x", "y"].forEach(function (eje) {
                chart.options.scales[eje].ticks.color = q.text;
                chart.options.scales[eje].title.color = q.text;
                chart.options.scales[eje].grid.color  = q.grid;
            });
            chart.update("none");
        }).observe(document.documentElement, { attributes: true, attributeFilter: ["data-theme"] });

        return chart;
    }

    function actualizarChart(chart, v, r) {
        // El eje llega hasta la producción del mes, o hasta el equilibrio si cae más lejos.
        var tope = Math.max(v.unidades, r.puntoEnt || 0);

        chart.data.datasets[0].data = [{ x: 0, y: 0 }, { x: tope, y: v.precio * tope }];
        chart.data.datasets[1].data = [{ x: 0, y: v.fijos }, { x: tope, y: v.fijos + r.varUnit * tope }];
        chart.data.datasets[2].data = isFinite(r.punto) && r.punto <= tope
            ? [{ x: r.punto, y: v.precio * r.punto }]
            : [];

        chart.options.scales.x.max = tope;
        chart.update();
    }

    /* =====================================================================
       2 · Contadores: los números grandes cuentan al entrar en pantalla
       ===================================================================== */
    function initContadores() {
        var nodos = document.querySelectorAll("[data-cost-count]");
        if (!nodos.length) return;

        if (reduce || typeof IntersectionObserver !== "function") return; // ya están escritos en el HTML

        function animar(el) {
            var destino = Number(el.getAttribute("data-cost-count"));
            var sufijo  = el.getAttribute("data-cost-suffix") || "";
            var plata   = el.hasAttribute("data-cost-money") || el.getAttribute("data-cost-count") > 999;
            var final   = el.textContent;
            var t0      = null;

            function paso(t) {
                if (t0 === null) t0 = t;
                var k = Math.min((t - t0) / 900, 1);
                var e = 1 - Math.pow(1 - k, 3); // ease-out: arranca rápido y frena
                if (k < 1) {
                    var n = destino * e;
                    el.textContent = (plata ? pesos(n) : fmt.format(Math.round(n))) + sufijo;
                    requestAnimationFrame(paso);
                } else {
                    el.textContent = final; // el valor exacto lo pone el PHP
                }
            }
            requestAnimationFrame(paso);
        }

        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                obs.unobserve(entry.target);
                animar(entry.target);
            });
        }, { threshold: 0.6 });

        nodos.forEach(function (el) { obs.observe(el); });
    }

    /* =====================================================================
       3 · Filtro de insumos por subsistema
       ===================================================================== */
    function initFiltro() {
        var caja = document.querySelector("[data-cost-parts]");
        if (!caja) return;

        var botones = Array.prototype.slice.call(caja.querySelectorAll("[data-cost-filter]"));
        var chips   = Array.prototype.slice.call(caja.querySelectorAll("[data-cost-group]"));

        botones.forEach(function (boton) {
            boton.addEventListener("click", function () {
                var grupo = boton.getAttribute("data-cost-filter");

                botones.forEach(function (b) {
                    var activo = b === boton;
                    b.classList.toggle("is-active", activo);
                    b.setAttribute("aria-pressed", activo ? "true" : "false");
                });

                chips.forEach(function (chip) {
                    var visible = grupo === "todos" || chip.getAttribute("data-cost-group") === grupo;
                    chip.hidden = !visible;
                });
            });
        });
    }

    function init() {
        initLab();
        initContadores();
        initFiltro();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
