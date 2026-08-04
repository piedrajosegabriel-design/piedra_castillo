/* ============================================================
   panel-vivo.js — Mantiene el dashboard actualizado sin recargar.

   POR QUE EXISTE: el panel dibujaba los datos una sola vez, al abrir la
   pagina. Si el equipo mandaba una medicion nueva, habia que apretar F5 para
   verla. Durante una demostracion eso parece que el sistema no recibe nada.

   COMO FUNCIONA
     Cada 30 segundos le pide a GET panel/datos el MISMO bloque de datos que
     usa la vista, y reemplaza solo los textos que cambiaron. No recarga la
     pagina: no parpadea, no se mueve el scroll y no se pierde nada de lo que
     estabas mirando.

   COMO ENCUENTRA QUE ACTUALIZAR
     Por atributos puestos en panel.php:
       data-vivo="clave"          -> el texto de ese elemento
       data-vivo-sensor="temp"    -> la tarjeta de un sensor
       data-vivo-tono             -> elemento cuya clase tone-* hay que cambiar
       data-vivo-spark            -> las dos curvas del grafico de tendencia

   SI EL SERVIDOR NO CONTESTA no pasa nada: se muestra el ultimo dato bueno
   y se reintenta en el proximo ciclo. Nunca se borra lo que ya estaba.
   ============================================================ */
(function () {
    "use strict";

    var raiz = document.querySelector("[data-dashboard-app]");
    if (!raiz) { return; }

    var URL_DATOS = raiz.getAttribute("data-url-datos");
    if (!URL_DATOS) { return; }

    var CADA_MS = 30000;          // cada cuanto preguntar
    var TONOS = ["success", "warning", "danger", "neutral", "info"];

    var ultimoOk = Date.now();    // cuando entro el ultimo dato bueno
    var sello = null;             // elemento que muestra "actualizado hace..."

    // -----------------------------------------------------------------------
    // Helpers de DOM
    // -----------------------------------------------------------------------

    /** Cambia el texto solo si es distinto, y lo resalta un instante. */
    function texto(el, valor) {
        if (!el || valor === undefined || valor === null) { return; }
        valor = String(valor);
        if (el.textContent === valor) { return; }

        el.textContent = valor;

        // Destello breve para que se note que ese numero cambio.
        el.classList.remove("ea-vivo-cambio");
        void el.offsetWidth;                 // fuerza reinicio de la animacion
        el.classList.add("ea-vivo-cambio");
    }

    /** Reemplaza la clase tone-* de un elemento. */
    function tono(el, nuevo) {
        if (!el || !nuevo) { return; }
        TONOS.forEach(function (t) { el.classList.remove("tone-" + t); });
        el.classList.add("tone-" + nuevo);
    }

    // -----------------------------------------------------------------------
    // Pintado
    // -----------------------------------------------------------------------
    function pintar(view) {
        // ---- Diagnostico general ----
        ["estadoLabel", "estadoTitulo", "estadoDetalle", "ultimaLectura"].forEach(function (clave) {
            var el = document.querySelector('[data-vivo="' + clave + '"]');
            texto(el, view[clave]);
        });

        // El color general del hero y su chip.
        if (view.tono) {
            document.querySelectorAll("[data-vivo-tono]").forEach(function (el) {
                // Los de las tarjetas se pintan mas abajo, con su propio tono.
                if (!el.closest("[data-vivo-sensor]")) { tono(el, view.tono); }
            });
        }

        // ---- Tarjetas de sensores ----
        (view.sensores || []).forEach(function (sensor) {
            var tarjeta = document.querySelector('[data-vivo-sensor="' + sensor.icono + '"]');
            if (!tarjeta) { return; }

            texto(tarjeta.querySelector('[data-vivo="valor"]'), sensor.valor);
            texto(tarjeta.querySelector('[data-vivo="rango"]'), sensor.rango);
            texto(tarjeta.querySelector('[data-vivo="badge"]'), etiquetaTono(sensor.tono));

            tarjeta.querySelectorAll("[data-vivo-tono]").forEach(function (el) {
                tono(el, sensor.tono);
            });

            var pin = tarjeta.querySelector('[data-vivo="pin"]');
            if (pin && typeof sensor.pct === "number") {
                pin.style.left = Math.max(0, Math.min(100, sensor.pct)).toFixed(1) + "%";
            }

            var banda = tarjeta.querySelector('[data-vivo="band"]');
            if (banda && typeof sensor.bandLow === "number" && typeof sensor.bandHigh === "number") {
                banda.style.left = sensor.bandLow.toFixed(1) + "%";
                banda.style.width = Math.max(0, sensor.bandHigh - sensor.bandLow).toFixed(1) + "%";
            }
        });

        // ---- Curva de tendencia ----
        if (view.sparkPath) {
            var relleno = document.querySelector('[data-vivo-spark="relleno"]');
            var linea = document.querySelector('[data-vivo-spark="linea"]');
            if (relleno) { relleno.setAttribute("d", view.sparkPath + " L 220 60 L 0 60 Z"); }
            if (linea) { linea.setAttribute("d", view.sparkPath); }
        }
    }

    /** Misma traduccion que usa panel.php para el badge de cada sensor. */
    function etiquetaTono(t) {
        if (t === "danger") { return "Crítico"; }
        if (t === "warning") { return "Atención"; }
        if (t === "neutral") { return "Sin datos"; }
        return "Normal";
    }

    // -----------------------------------------------------------------------
    // Sello de "actualizado hace..."
    // -----------------------------------------------------------------------
    function crearSello() {
        var ancla = document.querySelector('[data-vivo="ultimaLectura"]');
        if (!ancla || !ancla.parentElement) { return; }

        sello = document.createElement("span");
        sello.className = "ea-vivo-sello";
        sello.setAttribute("aria-live", "polite");
        ancla.parentElement.appendChild(sello);
        refrescarSello();
    }

    function refrescarSello() {
        if (!sello) { return; }

        var seg = Math.round((Date.now() - ultimoOk) / 1000);
        var txt;

        if (seg < 45) { txt = "actualizado recién"; }
        else if (seg < 5400) { txt = "actualizado hace " + Math.round(seg / 60) + " min"; }
        else { txt = "sin actualizar"; }

        sello.textContent = txt;
        sello.classList.toggle("is-viejo", seg > 180);
    }

    // -----------------------------------------------------------------------
    // Ciclo
    // -----------------------------------------------------------------------
    function preguntar() {
        // Si la pestaña no se ve, no gastamos pedidos.
        if (document.hidden) { return; }

        fetch(URL_DATOS, {
            headers: { "X-Requested-With": "fetch" },
            credentials: "same-origin",
            cache: "no-store"
        }).then(function (r) {
            if (r.status === 409) {
                // La cuenta se quedó sin dispositivos: la página ya no aplica.
                window.location.reload();
                return null;
            }
            if (!r.ok) { throw new Error("HTTP " + r.status); }
            return r.json();
        }).then(function (data) {
            if (!data || !data.ok || !data.view) { return; }
            pintar(data.view);
            ultimoOk = Date.now();
            refrescarSello();
        }).catch(function () {
            // Un fallo suelto no rompe nada: queda el último dato bueno.
            refrescarSello();
        });
    }

    crearSello();
    window.setInterval(preguntar, CADA_MS);
    window.setInterval(refrescarSello, 15000);

    // Al volver a la pestaña, actualizar en el acto en vez de esperar.
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) { preguntar(); }
    });
})();

/* ============================================================================
   GLOSARIO DE FUNCIONES DE ESTE ARCHIVO

   Pintado:
   - texto(el, valor)     → cambia el texto solo si difiere, con destello
   - tono(el, nuevo)      → reemplaza la clase tone-* (success/warning/danger)
   - pintar(view)         → aplica todo el bloque de datos a la página
   - etiquetaTono(t)      → "danger" → "Crítico" (igual que en panel.php)

   Sello de frescura:
   - crearSello()         → inserta el "actualizado hace…" junto a la lectura
   - refrescarSello()     → recalcula ese texto cada 15 s

   Ciclo:
   - preguntar()          → pide GET panel/datos y pinta la respuesta

   Detalles que importan:
   - document.hidden      → si la pestaña no se ve, no se piden datos
   - cache: "no-store"    → evita que el navegador devuelva una copia vieja
   - HTTP 409             → la cuenta se quedó sin dispositivos: recargar
   - void el.offsetWidth  → truco para reiniciar una animación CSS
   ============================================================================ */
