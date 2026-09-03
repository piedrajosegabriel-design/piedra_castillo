/* ============================================================
   conectar.js — pantalla "Conectá tu Eden Air".

   QUÉ HACE: maneja los cuatro estados de la tarjeta de conexión
   (reposo → en vivo → conectado / vencido) y el diálogo con el
   servidor.

   EL CICLO
     1. Clic en "Conectar"  → POST .../conectar
        El servidor abre la ventana de vinculación y devuelve el
        SVG del QR ya dibujado, el nombre y la clave de la red, y
        cuántos segundos dura.
     2. Mientras espera     → GET .../conectar/estado?token=...
        Cada 2,5 segundos pregunta si el equipo ya apareció.
     3. Cuando aparece      → muestra el panel de éxito.
        Si se vence el tiempo o el usuario cancela, muestra el
        panel de fin y deja de preguntar.

   CSRF: el proyecto regenera el token en cada request, así que
   los endpoints POST devuelven el hash nuevo y acá se guarda
   para el próximo envío.
   ============================================================ */
(function () {
    "use strict";

    var raiz = document.querySelector("[data-pair]");
    if (!raiz) { return; }

    // ---------------------------------------------------------------------
    // Referencias y estado
    // ---------------------------------------------------------------------
    var urls = {
        iniciar:  raiz.getAttribute("data-url-iniciar"),
        estado:   raiz.getAttribute("data-url-estado"),
        cancelar: raiz.getAttribute("data-url-cancelar")
    };

    var csrfName = raiz.getAttribute("data-csrf-name");
    var csrfHash = raiz.getAttribute("data-csrf-hash");

    var paneles   = {};
    var elQr      = raiz.querySelector("[data-pair-qr]");
    var elSsid    = raiz.querySelector("[data-pair-ssid]");
    var elPass    = raiz.querySelector("[data-pair-pass]");
    var elTimer   = raiz.querySelector("[data-pair-timer]");
    var elEstado  = raiz.querySelector("[data-pair-status-text]");
    var elSpinner = raiz.querySelector(".ea-pair-spinner");
    var botonIr   = raiz.querySelector("[data-pair-start]");

    Array.prototype.forEach.call(raiz.querySelectorAll("[data-pair-panel]"), function (p) {
        paneles[p.getAttribute("data-pair-panel")] = p;
    });

    var token       = null;   // ventana de vinculación abierta
    var restante    = 0;      // segundos que le quedan
    var idSondeo    = null;
    var idReloj     = null;

    // ---------------------------------------------------------------------
    // Helpers de pantalla
    // ---------------------------------------------------------------------
    function mostrar(nombre) {
        Object.keys(paneles).forEach(function (k) {
            paneles[k].hidden = k !== nombre;
        });
    }

    function estadoTexto(texto, esError) {
        if (elEstado) { elEstado.textContent = texto; }
        if (elSpinner) { elSpinner.style.display = esError ? "none" : ""; }
    }

    function relojTexto(segundos) {
        var m = Math.floor(segundos / 60);
        var s = segundos % 60;
        return m + ":" + (s < 10 ? "0" : "") + s;
    }

    function frenar() {
        if (idSondeo) { window.clearInterval(idSondeo); idSondeo = null; }
        if (idReloj)  { window.clearInterval(idReloj);  idReloj  = null; }
    }

    function terminar(titulo, texto) {
        frenar();
        token = null;
        var h = raiz.querySelector("[data-pair-fin-title]");
        var p = raiz.querySelector("[data-pair-fin-text]");
        if (h && titulo) { h.textContent = titulo; }
        if (p && texto)  { p.textContent = texto; }
        mostrar("fin");
    }

    // ---------------------------------------------------------------------
    // Envío con CSRF (el hash se renueva en cada respuesta)
    // ---------------------------------------------------------------------
    function postear(url, extra) {
        var cuerpo = new FormData();
        cuerpo.append(csrfName, csrfHash);
        Object.keys(extra || {}).forEach(function (k) { cuerpo.append(k, extra[k]); });

        return fetch(url, {
            method: "POST",
            body: cuerpo,
            headers: { "X-Requested-With": "fetch" },
            credentials: "same-origin"
        }).then(function (r) {
            if (!r.ok) { throw new Error("HTTP " + r.status); }
            return r.json();
        }).then(function (data) {
            if (data && data.csrf) { csrfHash = data.csrf; }
            return data;
        });
    }

    // ---------------------------------------------------------------------
    // 1) Abrir la ventana y pintar el QR
    // ---------------------------------------------------------------------
    function conectar() {
        if (botonIr) { botonIr.disabled = true; botonIr.textContent = "Generando código…"; }

        postear(urls.iniciar, {}).then(function (data) {
            if (!data || !data.ok) { throw new Error("respuesta inesperada"); }

            token    = data.token;
            restante = data.expira_en;

            if (elQr)   { elQr.innerHTML = data.svg; }
            if (elSsid) { elSsid.textContent = data.ssid; }
            if (elPass) { elPass.textContent = data.password; }
            if (elTimer) { elTimer.textContent = relojTexto(restante); }

            estadoTexto("Esperando a que tu Eden Air se conecte…", false);
            mostrar("vivo");
            arrancarReloj();
            arrancarSondeo();
        }).catch(function () {
            estadoTexto("No pudimos generar el código. Probá de nuevo.", true);
            terminar("No pudimos generar el código", "Revisá tu conexión y volvé a intentarlo.");
        }).then(function () {
            if (botonIr) { botonIr.disabled = false; botonIr.textContent = "Conectar"; }
        });
    }

    // ---------------------------------------------------------------------
    // 2) Cuenta regresiva y sondeo
    // ---------------------------------------------------------------------
    function arrancarReloj() {
        idReloj = window.setInterval(function () {
            restante = Math.max(0, restante - 1);
            if (elTimer) { elTimer.textContent = relojTexto(restante); }
            if (restante === 0) {
                terminar("Se agotó el tiempo", "Nadie conectó un equipo durante la espera. Podés volver a intentarlo cuando quieras.");
            }
        }, 1000);
    }

    function arrancarSondeo() {
        idSondeo = window.setInterval(preguntar, 2500);
    }

    function preguntar() {
        if (!token) { return; }

        fetch(urls.estado + "?token=" + encodeURIComponent(token), {
            headers: { "X-Requested-With": "fetch" },
            credentials: "same-origin"
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data) { return; }

            if (data.estado === "vinculado") {
                frenar();
                token = null;   // la ventana ya se cerró: no hay nada que cancelar al salir
                var h = raiz.querySelector("[data-pair-ok-title]");
                if (h && data.device && data.device.nombre) {
                    h.textContent = "“" + data.device.nombre + "” quedó conectado";
                }
                mostrar("ok");
                return;
            }

            if (data.estado === "expirado" || data.estado === "cancelado" || data.estado === "desconocida") {
                terminar(
                    data.estado === "expirado" ? "Se agotó el tiempo" : "La vinculación se cerró",
                    data.mensaje || "Podés volver a intentarlo cuando quieras."
                );
                return;
            }

            // Sigue esperando: sincronizamos el reloj con el servidor.
            if (typeof data.expira_en === "number") { restante = data.expira_en; }
        }).catch(function () {
            // Un fallo suelto de red no corta la espera: se reintenta solo.
        });
    }

    // ---------------------------------------------------------------------
    // 3) Cancelar / reintentar
    // ---------------------------------------------------------------------
    function cancelar() {
        frenar();
        if (token) { postear(urls.cancelar, { token: token }).catch(function () {}); }
        token = null;
        mostrar("reposo");
    }

    // ---------------------------------------------------------------------
    // Eventos
    // ---------------------------------------------------------------------
    raiz.addEventListener("click", function (e) {
        if (e.target.closest("[data-pair-start]"))  { conectar(); }
        if (e.target.closest("[data-pair-cancel]")) { cancelar(); }
        if (e.target.closest("[data-pair-retry]"))  { mostrar("reposo"); conectar(); }
    });

    // Si el usuario se va de la página con una ventana abierta, la cerramos
    // para no dejarla esperando un equipo que nadie va a enchufar.
    window.addEventListener("pagehide", function () {
        if (!token || !navigator.sendBeacon) { return; }
        var cuerpo = new FormData();
        cuerpo.append(csrfName, csrfHash);
        cuerpo.append("token", token);
        navigator.sendBeacon(urls.cancelar, cuerpo);
    });
})();

/* ============================================================================
   GLOSARIO DE FUNCIONES DE ESTE ARCHIVO

   Pantalla:
   - mostrar(nombre)      → deja visible uno de los 4 paneles
                            (reposo | vivo | ok | fin)
   - estadoTexto(t, err)  → texto de la línea de estado; oculta el spinner
                            cuando es un error
   - relojTexto(seg)      → 545 → "9:05"
   - frenar()             → corta el sondeo y la cuenta regresiva
   - terminar(t, p)       → muestra el panel final con ese título y texto

   Servidor:
   - postear(url, extra)  → POST con el token CSRF; guarda el hash nuevo
   - conectar()           → abre la ventana y pinta el QR
   - arrancarReloj()      → cuenta regresiva de 1 en 1 segundo
   - arrancarSondeo()     → pregunta cada 2,5 s si el equipo apareció
   - preguntar()          → una consulta de estado
   - cancelar()           → cierra la ventana y vuelve al reposo

   Funciones del navegador usadas acá:
   - fetch(url, opciones)          → pedido HTTP sin recargar la página
   - FormData                      → arma el cuerpo del POST
   - setInterval/clearInterval     → repetir cada N milisegundos
   - encodeURIComponent(v)         → escapa el token para la URL
   - navigator.sendBeacon(u, d)    → envío que sobrevive al cierre de la página
   - e.target.closest(sel)         → sube por el DOM buscando ese selector
   ============================================================================ */
