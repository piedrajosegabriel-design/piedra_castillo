/* ============================================================
   vinculacion.js — pantalla de espera del CELULAR.

   QUÉ HACE: pregunta cada 2 segundos si el equipo que el usuario
   acaba de configurar ya se dio de alta, y muestra el resultado.

   POR QUÉ EXISTE ESTA PANTALLA
   El celular viene de la red del propio Eden Air, donde no había
   internet. Al configurar el WiFi, esa red desaparece y el
   teléfono vuelve solo a la de la casa. Recién ahí puede hablar
   con el servidor, y lo único que trae encima es el código de
   sesión que le dio el portal de la placa.

   TOLERANTE A LA RECONEXIÓN: los primeros sondeos suelen fallar
   porque el WiFi del teléfono todavía se está restableciendo. Un
   error de red NO se muestra como problema; se reintenta. Solo
   se rinde cuando el servidor dice explícitamente que la ventana
   venció, o cuando pasó el tiempo máximo de espera.
   ============================================================ */
(function () {
    "use strict";

    var raiz = document.querySelector("[data-seguir]");
    if (!raiz) { return; }

    var sesion    = raiz.getAttribute("data-sesion") || "";
    var urlEstado = raiz.getAttribute("data-url-estado");

    var paneles = {
        espera:   raiz.querySelector('[data-panel="espera"]'),
        listo:    raiz.querySelector('[data-panel="listo"]'),
        problema: raiz.querySelector('[data-panel="problema"]')
    };

    var elMensaje  = raiz.querySelector("[data-mensaje]");
    var elNombre   = raiz.querySelector("[data-nombre]");
    var elProbTit  = raiz.querySelector("[data-problema-titulo]");
    var elProbTxt  = raiz.querySelector("[data-problema-texto]");

    var INTERVALO = 2000;
    // 11 minutos: un poco más que la ventana de vinculación (10), para no
    // rendirnos justo antes de que el servidor la dé por vencida.
    var LIMITE = 11 * 60 * 1000;

    var arranque = Date.now();
    var timer    = null;

    // ---------------------------------------------------------------------
    // Paneles
    // ---------------------------------------------------------------------
    function mostrar(cual) {
        Object.keys(paneles).forEach(function (nombre) {
            if (paneles[nombre]) { paneles[nombre].hidden = (nombre !== cual); }
        });
    }

    function frenar() {
        if (timer) { clearTimeout(timer); timer = null; }
    }

    function terminarConProblema(titulo, texto) {
        frenar();
        if (elProbTit && titulo) { elProbTit.textContent = titulo; }
        if (elProbTxt && texto) { elProbTxt.textContent = texto; }
        mostrar("problema");
    }

    // ---------------------------------------------------------------------
    // Sondeo
    // ---------------------------------------------------------------------
    function preguntar() {
        fetch(urlEstado + "?s=" + encodeURIComponent(sesion), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            cache: "no-store"
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (datos) {
                if (datos.estado === "vinculado") {
                    frenar();
                    if (elNombre && datos.device) { elNombre.textContent = datos.device; }
                    mostrar("listo");
                    return;
                }

                if (datos.estado === "expirado" || datos.estado === "cancelado") {
                    terminarConProblema(
                        datos.estado === "expirado" ? "Se agotó el tiempo" : "Vinculación cancelada",
                        datos.mensaje
                    );
                    return;
                }

                // 'esperando' y 'desconocida' son lo mismo para el usuario: el
                // equipo todavía no llegó. 'desconocida' es lo normal durante
                // los primeros segundos, porque la placa recién está
                // conectándose y todavía no llamó a la API.
                if (elMensaje && datos.mensaje) { elMensaje.textContent = datos.mensaje; }
                seguir();
            })
            .catch(function () {
                // Error de red: casi siempre es el WiFi del teléfono todavía
                // restableciéndose. Se reintenta en silencio.
                seguir();
            });
    }

    function seguir() {
        if (Date.now() - arranque > LIMITE) {
            terminarConProblema(
                "Tardó más de lo esperado",
                "No pudimos confirmar la conexión de tu equipo. Revisá que esté enchufado y volvé a intentarlo."
            );
            return;
        }

        timer = setTimeout(preguntar, INTERVALO);
    }

    // ---------------------------------------------------------------------
    // Arranque
    // ---------------------------------------------------------------------
    if (!sesion) {
        terminarConProblema(
            "Falta el código de la vinculación",
            "Abrí este enlace desde el botón que te mostró tu Eden Air al terminar de configurar el WiFi."
        );
        return;
    }

    mostrar("espera");
    preguntar();

    // Si el usuario deja la pestaña y vuelve, preguntamos enseguida en vez de
    // esperar el próximo turno: es exactamente lo que pasa cuando cambia de
    // red y regresa al navegador.
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden && timer) {
            frenar();
            preguntar();
        }
    });
})();
