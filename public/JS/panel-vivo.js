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

    // El equipo manda una medicion cada 5 minutos (INTERVALO_MEDICION en
    // firmware/config.py). Recien despues de perder DOS ciclos seguidos tiene
    // sentido avisar que algo anda mal: antes de eso seria una falsa alarma
    // cada vez que el panel mira entre medicion y medicion.
    var VIEJO_SEG = 11 * 60;

    // Momento de la ULTIMA MEDICION, en segundos desde 1970, tal como lo
    // calculo el servidor. Es lo que mide el sello.
    //
    // Antes se usaba Date.now() del ultimo pedido exitoso, y eso estaba mal:
    // si la placa se moria, el servidor seguia devolviendo la misma lectura
    // vieja, el pedido seguia saliendo bien, y el sello decia "actualizado
    // recien" para siempre. Lo unico que confirmaba era que la web respondia.
    var epochLectura = parseInt(raiz.getAttribute("data-epoch-lectura"), 10);
    if (!isFinite(epochLectura)) { epochLectura = null; }

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
        // trendMin/trendMax son los topes de la curva de tendencia: si entran
        // lecturas nuevas la escala cambia, y dejarlos fijos haria que el
        // dibujo y sus numeros dijeran cosas distintas.
        ["estadoLabel", "estadoTitulo", "estadoDetalle", "ultimaLectura",
         "trendMin", "trendMax"].forEach(function (clave) {
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

        // ---- Historial de lecturas ----
        pintarHistorial(view.historial);

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
    // Historial de lecturas
    //
    // La tabla se armaba una sola vez, al cargar la pagina: entraba una lectura
    // nueva, el hero y los sensores se actualizaban, pero el historial seguia
    // mostrando lo viejo hasta que apretaras F5.
    //
    // Las filas se construyen con createElement y textContent, no pegando HTML:
    // asi lo que venga del servidor no puede inyectar marcado.
    // -----------------------------------------------------------------------
    var firmaHistorial = null;   // evita rehacer la tabla si no cambio nada

    function celda(clase, valor) {
        var td = document.createElement("td");
        if (clase) { td.className = clase; }
        td.textContent = (valor === undefined || valor === null) ? "--" : valor;
        return td;
    }

    /** Celda "Origen": puntito + texto, como la arma panel.php. */
    function celdaOrigen(origen) {
        var td = document.createElement("td");
        var caja = document.createElement("span");
        caja.className = "ea-table-dev";

        var punto = document.createElement("span");
        punto.className = "ea-table-dev-dot";

        var texto = document.createElement("span");
        texto.className = "ea-mono";
        texto.textContent = origen || "--";

        caja.appendChild(punto);
        caja.appendChild(texto);
        td.appendChild(caja);
        return td;
    }

    /** Celda "Estado": el badge con su tono. */
    function celdaEstado(tonoFila) {
        var td = document.createElement("td");
        var badge = document.createElement("span");
        badge.className = "ea-badge tone-" + (tonoFila || "success");

        var punto = document.createElement("span");
        punto.className = "ea-dot";

        badge.appendChild(punto);
        badge.appendChild(document.createTextNode(etiquetaTono(tonoFila)));
        td.appendChild(badge);
        return td;
    }

    function filaVacia(columnas) {
        var tr = document.createElement("tr");
        var td = document.createElement("td");
        td.className = "ea-table-empty";
        td.setAttribute("colspan", columnas);

        var caja = document.createElement("div");
        caja.className = "ea-empty";

        var titulo = document.createElement("strong");
        titulo.textContent = "Sin lecturas registradas todavía.";

        var p = document.createElement("p");
        p.textContent = "Cuando el dispositivo envíe datos, las lecturas aparecerán acá.";

        caja.appendChild(titulo);
        caja.appendChild(p);
        td.appendChild(caja);
        tr.appendChild(td);
        return tr;
    }

    /**
     * Ajusta el boton "Ver N mas" cuando cambia la cantidad de filas.
     * Si la pagina se cargo con pocas lecturas el boton no existe en el HTML;
     * en ese caso no hay nada que ajustar y la tabla igual se ve completa.
     */
    function ajustarVerMas(total, fijas) {
        var boton = document.querySelector("[data-readings-toggle]");
        if (!boton) { return; }

        var pie = boton.closest(".ea-readings-foot");
        var ocultas = total - fijas;

        if (pie) { pie.hidden = ocultas <= 0; }
        if (ocultas <= 0) { return; }

        var etiquetaMas = "Ver " + ocultas + " más";
        boton.setAttribute("data-more", etiquetaMas);

        // Solo se reescribe el texto visible si la tabla esta contraida: si el
        // usuario la tiene desplegada, el boton dice "Ver menos" y no hay que
        // pisarselo.
        var tarjeta = document.querySelector("[data-readings]");
        if (tarjeta && tarjeta.classList.contains("is-expanded")) { return; }

        var etiqueta = boton.querySelector("[data-readings-label]");
        if (etiqueta) { etiqueta.textContent = etiquetaMas; }
    }

    function pintarHistorial(historial) {
        var cuerpo = document.querySelector("[data-vivo-historial]");
        if (!cuerpo || Object.prototype.toString.call(historial) !== "[object Array]") { return; }

        // Firma barata: con la misma cantidad de filas y la misma fecha arriba,
        // no hay nada nuevo. Sin esto la tabla se reconstruiria cada 30 s.
        var firma = historial.length + "|" + (historial[0] ? historial[0].fecha : "");
        if (firma === firmaHistorial) { return; }

        var esRefresco = firmaHistorial !== null;
        firmaHistorial = firma;

        var fijas = parseInt(cuerpo.getAttribute("data-filas-fijas"), 10);
        if (!isFinite(fijas)) { fijas = 3; }

        var columnas = (cuerpo.parentElement.querySelectorAll("thead th") || []).length || 7;

        while (cuerpo.firstChild) { cuerpo.removeChild(cuerpo.firstChild); }

        if (!historial.length) {
            cuerpo.appendChild(filaVacia(columnas));
            ajustarVerMas(0, fijas);
            return;
        }

        historial.forEach(function (fila, i) {
            var tr = document.createElement("tr");
            if (i >= fijas) { tr.className = "is-extra"; }

            tr.appendChild(celda("ea-mono ea-table-time", fila.fecha));
            tr.appendChild(celdaOrigen(fila.origen));
            tr.appendChild(celda("ea-num ea-mono", fila.temperatura));
            tr.appendChild(celda("ea-num ea-mono", fila.humedad));
            tr.appendChild(celda("", fila.aire));
            tr.appendChild(celda("ea-num ea-mono", fila.co2));
            tr.appendChild(celdaEstado(fila.tono));

            cuerpo.appendChild(tr);
        });

        // Destello en la lectura recien llegada, con el mismo efecto que usan
        // los numeros del hero. En la primera carga no: ahi no "cambio" nada.
        if (esRefresco && cuerpo.firstChild) {
            cuerpo.firstChild.classList.add("ea-vivo-cambio");
        }

        ajustarVerMas(historial.length, fijas);
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

        if (epochLectura === null) {
            sello.textContent = "sin lecturas todavía";
            sello.classList.remove("is-viejo");
            return;
        }

        var seg = Math.round(Date.now() / 1000 - epochLectura);
        var txt;

        // Negativo = el reloj del visitante atrasa respecto del servidor. No es
        // un dato viejo, asi que no se lo trata como tal.
        if (seg < 0) { seg = 0; }

        if (seg < 90) { txt = "medición de recién"; }
        else if (seg < 5400) { txt = "medido hace " + Math.round(seg / 60) + " min"; }
        else if (seg < 172800) { txt = "medido hace " + Math.round(seg / 3600) + " h"; }
        else { txt = "sin mediciones nuevas"; }

        sello.textContent = txt;
        sello.classList.toggle("is-viejo", seg > VIEJO_SEG);
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

            var epoch = parseInt(data.view.ultimaLecturaEpoch, 10);
            epochLectura = isFinite(epoch) ? epoch : null;

            refrescarSello();
        }).catch(function () {
            // Un fallo suelto no rompe nada: queda el último dato bueno.
            refrescarSello();
        });
    }

    // Firma de la tabla que ya vino armada desde el servidor. Sin esto, el
    // primer refresco la reconstruiria entera aunque no hubiera cambiado nada.
    (function firmarHistorialInicial() {
        var cuerpo = document.querySelector("[data-vivo-historial]");
        if (!cuerpo) { return; }

        var filas = cuerpo.querySelectorAll("tr");
        var primeraFecha = cuerpo.querySelector(".ea-table-time");

        // Si la tabla esta vacia se deja la firma en null a proposito: cuando
        // llegue la primera lectura, se dibuja.
        if (filas.length && primeraFecha) {
            firmaHistorial = filas.length + "|" + primeraFecha.textContent.trim();
        }
    })();

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

   Historial:
   - pintarHistorial(h)   → rehace las filas de la tabla si llegó algo nuevo
   - celda / celdaOrigen / celdaEstado / filaVacia → arman cada <td>
   - ajustarVerMas(n, f)  → recalcula el botón "Ver N más"
   - firmaHistorial       → cantidad + fecha de arriba; si no cambió, no rehace

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
