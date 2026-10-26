/* ============================================================
   panel-vivo.js — Mantiene el dashboard actualizado sin recargar.

   POR QUE EXISTE: el panel dibujaba los datos una sola vez, al abrir la
   pagina. Si el equipo mandaba una medicion nueva, habia que apretar F5 para
   verla. Durante una demostracion eso parece que el sistema no recibe nada.

   COMO FUNCIONA
     Cada 10 segundos le pide a GET panel/datos el MISMO bloque de datos que
     usa la vista, y reemplaza solo los textos que cambiaron. No recarga la
     pagina: no parpadea, no se mueve el scroll y no se pierde nada de lo que
     estabas mirando.

     Eran 30 segundos cuando el equipo medía cada 5 minutos. Ahora mide cada
     30: mirando cada 10, una medicion nueva aparece como mucho 10 segundos
     despues de llegar al servidor.

   COMO ENCUENTRA QUE ACTUALIZAR
     Por atributos puestos en panel.php:
       data-vivo="clave"          -> el texto de ese elemento
       data-vivo-sensor="temp"    -> la tarjeta de un sensor
       data-vivo-tono             -> elemento cuya clase tone-* hay que cambiar
       data-vivo-spark            -> las dos curvas del grafico de tendencia
       data-vivo-mensajes         -> la tira de avisos del equipo
       data-vivo-led="green_led"  -> cada uno de los tres LEDs
       data-vivo-act="fan"        -> la fila de cada actuador
       data-vivo-toggle           -> su interruptor (modo manual)

   LOS INTERRUPTORES NO RECARGAN LA PAGINA. Al tocar uno, se mueve en el acto
   a la posicion elegida (atenuado: la placa todavia no la aplico) y la orden
   viaja por fetch. El servidor contesta con el panel ya recalculado, que
   incluye la orden pendiente, asi que ni esa respuesta ni los refrescos
   siguientes lo devuelven a su posicion vieja. Cuando la placa confirma, el
   refresco le saca el atenuado.

   SI EL SERVIDOR NO CONTESTA no pasa nada: se muestra el ultimo dato bueno
   y se reintenta en el proximo ciclo. Nunca se borra lo que ya estaba.
   ============================================================ */
(function () {
    "use strict";

    var raiz = document.querySelector("[data-dashboard-app]");
    if (!raiz) { return; }

    var URL_DATOS = raiz.getAttribute("data-url-datos");
    if (!URL_DATOS) { return; }

    var CADA_MS = 10000;          // cada cuanto preguntar
    var TONOS = ["success", "warning", "danger", "neutral", "info"];

    // El equipo manda una medicion cada 30 segundos (INTERVALO_MEDICION en
    // DeviceConfigService.php; antes eran 5 minutos). Con ese ritmo, tres minutos
    // sin nada nuevo ya no es "todavia no le toco": es que algo se corto.
    //
    // El numero tiene que quedar MUY por encima del intervalo real igual: un
    // reintento de WiFi o un servidor lento no pueden disparar la alarma.
    var VIEJO_SEG = 3 * 60;

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
    //
    // sinActuadores: true cuando el dato de los actuadores puede ser mas viejo
    // que el ultimo toque del usuario (ver "Interruptores" mas abajo). Se
    // pinta todo lo demas igual.
    // -----------------------------------------------------------------------
    function pintar(view, sinActuadores) {
        // ---- Diagnostico general ----
        // trendMin/trendMax son los topes de la curva de tendencia: si entran
        // lecturas nuevas la escala cambia, y dejarlos fijos haria que el
        // dibujo y sus numeros dijeran cosas distintas.
        // aireOrigen: si el indice lo midio el MQ-135 o se estimo. Cambia solo
        // (el sensor entra en pausa cuando humidifica), asi que se refresca
        // como cualquier otro texto.
        ["estadoLabel", "estadoTitulo", "estadoDetalle", "ultimaLectura",
         "aireOrigen", "trendMin", "trendMax"].forEach(function (clave) {
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

        // ---- Mensajes del equipo y LEDs ----
        pintarMensajes(view.mensajes);
        pintarLeds(view.leds);

        // ---- Actuadores ----
        if (!sinActuadores) {
            pintarActuadores(view.actuadores, view.actuadoresActivos);
        }

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
    // Mensajes del equipo
    //
    // Son la parte que mas cambia del panel: aparecen y desaparecen segun lo
    // que este haciendo el equipo ("aire acondicionado activado", "ventila el
    // ambiente", "medicion de aire en pausa"). Si no se refrescaran, el panel
    // mostraria un pedido que ya no corresponde.
    //
    // Igual que el historial, se arman con createElement y textContent: lo que
    // viene del servidor nunca se pega como HTML.
    // -----------------------------------------------------------------------
    var firmaMensajes = null;

    function pintarMensajes(mensajes) {
        var caja = document.querySelector("[data-vivo-mensajes]");
        if (!caja || Object.prototype.toString.call(mensajes) !== "[object Array]") { return; }

        // Firma barata: los codigos en orden. Si no cambiaron, no se rehace
        // nada y no parpadea.
        var firma = mensajes.map(function (m) { return m.codigo; }).join("|");
        if (firma === firmaMensajes) { return; }
        firmaMensajes = firma;

        while (caja.firstChild) { caja.removeChild(caja.firstChild); }

        caja.hidden = mensajes.length === 0;

        mensajes.forEach(function (msg) {
            var p = document.createElement("p");
            p.className = "ea-msg tone-" + (msg.tono || "neutral");

            var punto = document.createElement("span");
            punto.className = "ea-dot";
            punto.setAttribute("aria-hidden", "true");

            p.appendChild(punto);
            p.appendChild(document.createTextNode(msg.texto || ""));
            caja.appendChild(p);
        });
    }

    // -----------------------------------------------------------------------
    // Los tres LEDs del equipo
    //
    // No se reconstruyen: son siempre los mismos tres y solo cambia si estan
    // encendidos. Alcanza con tocar la clase y la palabra de estado.
    // -----------------------------------------------------------------------
    function pintarLeds(leds) {
        if (Object.prototype.toString.call(leds) !== "[object Array]") { return; }

        leds.forEach(function (led) {
            var el = document.querySelector('[data-vivo-led="' + led.clave + '"]');
            if (!el) { return; }

            el.classList.toggle("is-on", !!led.encendido);
            tono(el, led.tono);
            texto(el.querySelector('[data-vivo="estado"]'), led.encendido ? "encendido" : "apagado");
        });
    }

    // -----------------------------------------------------------------------
    // Actuadores
    //
    // Antes no se refrescaban: si la placa confirmaba una orden, o si en
    // automatico prendia el aire, la tarjeta seguia igual hasta apretar F5.
    // -----------------------------------------------------------------------
    function pintarActuadores(actuadores, activos) {
        if (Object.prototype.toString.call(actuadores) !== "[object Array]") { return; }

        actuadores.forEach(function (act) {
            var fila = document.querySelector('[data-vivo-act="' + act.clave + '"]');
            if (fila) { pintarFila(fila, !!act.encendido, !!act.pendiente); }
        });

        texto(document.querySelector('[data-vivo="actuadoresActivos"]'), activos);
    }

    /** Deja una fila como dice el estado: icono, interruptor o badge, y aviso. */
    function pintarFila(fila, encendido, pendiente) {
        var icono = fila.querySelector(".ea-actuator-icon");
        if (icono) { icono.classList.toggle("is-on", encendido); }

        var aviso = fila.querySelector('[data-vivo="pendiente"]');
        if (aviso) { aviso.hidden = !pendiente; }

        // Modo manual: el interruptor.
        var boton = fila.querySelector("[data-vivo-toggle]");
        if (boton) {
            boton.classList.toggle("is-on", encendido);
            boton.classList.toggle("is-pendiente", pendiente);
            boton.setAttribute("aria-label",
                (encendido ? "Apagar " : "Encender ") + (boton.getAttribute("data-titulo") || ""));

            // Lo que va a mandar el PROXIMO toque: lo contrario de lo que se ve.
            var valor = boton.form && boton.form.querySelector('input[name="value"]');
            if (valor) { valor.value = encendido ? "off" : "on"; }
        }

        // Modo automatico: el badge ON/OFF.
        var badge = fila.querySelector("[data-vivo-badge]");
        if (badge) {
            tono(badge, encendido ? "success" : "neutral");
            texto(badge.querySelector('[data-vivo="onoff"]'), encendido ? "ON" : "OFF");
        }
    }

    // -----------------------------------------------------------------------
    // Interruptores (modo manual)
    //
    // EL PROBLEMA QUE RESUELVE: el interruptor era un formulario comun. Al
    // tocarlo la pagina se recargaba y lo dibujaba con el estado CONFIRMADO,
    // que no cambia hasta que la placa aplica la orden (hasta 15 s). Volvia a
    // su posicion vieja y ahi quedaba, porque el refresco no lo tocaba.
    //
    // Ahora se mueve al instante y la orden viaja por fetch. Queda una
    // carrera: un refresco que salio ANTES del toque puede volver DESPUES con
    // el estado viejo. Para eso estan estos dos contadores: si mientras viajaba
    // un pedido hubo un toque, sus actuadores no se pintan.
    //
    // Si el fetch falla, se envia el formulario de la forma comun: lo peor que
    // puede pasar es lo de antes, una recarga.
    // -----------------------------------------------------------------------
    var versionOrden = 0;      // sube con cada toque
    var ordenesEnCamino = 0;   // ordenes que todavia no contestaron

    /** El proyecto renueva el token CSRF en cada POST: hay que copiarlo a
        todos los formularios de la pagina o el proximo fallaria. */
    function renovarCsrf(nombre, valor) {
        if (!nombre || !valor) { return; }
        document.querySelectorAll('input[name="' + nombre + '"]').forEach(function (input) {
            input.value = valor;
        });
    }

    /** Envio comun del formulario, como antes de este archivo. */
    function enviarComoFormulario(form) {
        if (typeof form.requestSubmit === "function") {
            form.requestSubmit();   // dispara "submit": dashboard.js guarda el scroll
        } else {
            form.submit();
        }
    }

    function enviarOrden(boton) {
        var form = boton.form;
        var fila = boton.closest("[data-vivo-act]");
        if (!form || !fila) { return; }

        var antes = {
            encendido: boton.classList.contains("is-on"),
            pendiente: boton.classList.contains("is-pendiente")
        };
        var encender = !antes.encendido;

        var cuerpo = new FormData(form);   // lleva el CSRF y el actuador
        cuerpo.set("value", encender ? "on" : "off");

        // En el acto: lo elegido, atenuado hasta que la placa lo confirme.
        pintarFila(fila, encender, true);

        var mia = ++versionOrden;
        var abierta = true;
        ordenesEnCamino++;

        function cerrar() {
            if (abierta) { abierta = false; ordenesEnCamino--; }
        }

        fetch(form.action, {
            method: "POST",
            body: cuerpo,
            headers: { "X-Requested-With": "fetch", "Accept": "application/json" },
            credentials: "same-origin"
        }).then(function (r) {
            return r.json().catch(function () { return null; }).then(function (data) {
                return { estado: r.status, data: data };
            });
        }).then(function (res) {
            cerrar();
            var data = res.data;

            if (data && data.ok && data.view) {
                renovarCsrf(data.csrfNombre, data.csrf);
                // Si despues de este toque hubo otro, sus actuadores ya son viejos.
                pintar(data.view, mia !== versionOrden);
                return;
            }

            if (data && data.ok === false) {
                // El servidor no la acepto (por ejemplo, el modo cambio en otra
                // pestaña). Recargando se ve el panel como quedo y el motivo.
                window.location.reload();
                return;
            }

            throw new Error("HTTP " + res.estado);
        }).catch(function () {
            cerrar();
            pintarFila(fila, antes.encendido, antes.pendiente);
            enviarComoFormulario(form);
        });
    }

    // Un solo escuchador para todos los interruptores. Se engancha al click y
    // no al submit para que dashboard.js no guarde un scroll que no se va a
    // usar (no hay recarga).
    document.addEventListener("click", function (e) {
        var boton = e.target && e.target.closest ? e.target.closest("[data-vivo-toggle]") : null;
        if (!boton || !window.fetch || !window.FormData) { return; }

        e.preventDefault();
        enviarOrden(boton);
    });

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

        var version = versionOrden;

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

            // Si mientras viajaba este pedido se toco un interruptor, lo que
            // trae de los actuadores puede ser de antes del toque.
            pintar(data.view, ordenesEnCamino > 0 || version !== versionOrden);

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

   Mensajes y LEDs:
   - pintarMensajes(m)    → rehace la tira de avisos si cambiaron los códigos
   - pintarLeds(l)        → enciende/apaga los tres LEDs del equipo

   Actuadores e interruptores:
   - pintarActuadores(a)  → pone al día las tres filas y el "N de 3 encendidos"
   - pintarFila(f, on, p) → ícono, interruptor (o badge ON/OFF) y aviso de pendiente
   - enviarOrden(boton)   → mueve el interruptor en el acto y manda la orden por fetch
   - renovarCsrf(n, v)    → copia el token CSRF nuevo a todos los formularios
   - enviarComoFormulario → respaldo: el envío de siempre, con recarga
   - versionOrden / ordenesEnCamino → evitan pintar actuadores con un dato viejo

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
