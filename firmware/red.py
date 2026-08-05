"""
red.py — Conexion WiFi y portal de configuracion.

Resuelve el problema del huevo y la gallina: el equipo necesita WiFi para
hablar con el servidor, pero nadie puede escribirle la clave de la casa
porque no tiene teclado ni pantalla.

COMO SE RESUELVE
  1. Si ya hay una red guardada en wifi.json, se conecta y listo.
  2. Si no, levanta su PROPIA red (EdenAir-Setup) y sirve una pagina.
  3. El usuario escanea el QR de la web con el celular: el celular entra
     solo a esa red y se abre la pagina.
  4. Elige su WiFi de casa, pone la clave, y el equipo la guarda y se conecta.

El nombre y la clave de esa red estan en config.py y TIENEN que coincidir
con los que la web mete adentro del QR.
"""

import json
import select
import socket
import time

import network

import config


# ---------------------------------------------------------------------------
# Credenciales guardadas
# ---------------------------------------------------------------------------
def leer_wifi_guardado():
    try:
        with open(config.ARCHIVO_WIFI) as f:
            datos = json.load(f)
            return datos.get("ssid"), datos.get("password")
    except (OSError, ValueError):
        return None, None


def guardar_wifi(ssid, password):
    with open(config.ARCHIVO_WIFI, "w") as f:
        json.dump({"ssid": ssid, "password": password}, f)


def olvidar_wifi():
    """Borra la red guardada. El equipo vuelve a pedir configuracion."""
    try:
        import os
        os.remove(config.ARCHIVO_WIFI)
    except OSError:
        pass


# ---------------------------------------------------------------------------
# Codigo de sesion: el puente entre el portal y el panel
# ---------------------------------------------------------------------------
def nueva_sesion():
    """
    Inventa el codigo que le va a entregar al celular.

    POR QUE LO GENERA EL EQUIPO Y NO LA WEB: el QR se dibuja antes de que la
    placa exista para el servidor, asi que no hay forma de meterle nada
    adentro. El dato tiene que nacer aca y viajar al reves —celular por un
    lado, API por el otro— para que las dos puntas se encuentren.

    16 bytes al azar: imposible de adivinar, y solo sirve unos minutos.
    """
    try:
        import os
        crudo = os.urandom(16)
    except (ImportError, AttributeError):
        # Respaldo por si el port no trae os.urandom.
        import urandom
        crudo = bytes(urandom.getrandbits(8) for _ in range(16))

    return "".join("%02x" % b for b in crudo)


def leer_sesion():
    """El codigo pendiente, o None si no hay ninguno."""
    try:
        with open(config.ARCHIVO_SESION) as f:
            return json.load(f).get("codigo") or None
    except (OSError, ValueError, AttributeError):
        return None


def guardar_sesion(codigo):
    with open(config.ARCHIVO_SESION, "w") as f:
        json.dump({"codigo": codigo}, f)


def olvidar_sesion():
    """Se llama cuando el equipo ya quedo vinculado: el codigo no sirve mas."""
    try:
        import os
        os.remove(config.ARCHIVO_SESION)
    except OSError:
        pass


# ---------------------------------------------------------------------------
# Conexion normal
# ---------------------------------------------------------------------------
def _motivo_falla(wlan, ssid):
    """
    Traduce el codigo de error del WiFi a algo que le sirva a una persona.

    Es la diferencia entre "no se pudo conectar" (inutil: no sabes que tocar)
    y "la contraseña no es correcta" (accionable: la volves a escribir).
    Los STAT_* no existen en todos los ports, por eso el getattr.
    """
    try:
        estado = wlan.status()
    except (OSError, AttributeError):
        estado = None

    mala_clave = getattr(network, "STAT_WRONG_PASSWORD", object())
    sin_red = getattr(network, "STAT_NO_AP_FOUND", object())

    if estado == mala_clave:
        return ("La contraseña no es correcta",
                "Revisala y proba de nuevo. Fijate en mayúsculas y minúsculas: "
                "toca el boton Ver para leer lo que escribis.")

    if estado == sin_red:
        return ("No se encontró la red " + ssid,
                "Puede estar apagada o demasiado lejos del equipo. "
                "Acerca el Eden Air al router y proba otra vez.")

    return ("No se pudo conectar a " + ssid,
            "La red respondio pero la conexion no se completó. "
            "Verifica que el router este funcionando y proba de nuevo.")


def conectar(ssid, password, espera=20):
    """
    Intenta conectarse. Devuelve (ip, error).

    Si sale bien, error es None. Si sale mal, ip es None y error es el par
    (titulo, detalle) que el portal muestra arriba del formulario.
    """
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)

    if wlan.isconnected():
        return wlan.ifconfig()[0], None

    print("Conectando a", ssid, "...")
    wlan.connect(ssid, password)

    for _ in range(espera * 2):
        if wlan.isconnected():
            ip = wlan.ifconfig()[0]
            print("Conectado. IP:", ip)
            return ip, None
        time.sleep_ms(500)

    error = _motivo_falla(wlan, ssid)
    print("No se pudo conectar a", ssid, "->", error[0])
    wlan.active(False)
    return None, error


def mac():
    """MAC del equipo en formato AA:BB:CC:DD:EE:FF. Es su identidad."""
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    crudo = wlan.config("mac")
    return ":".join("%02X" % b for b in crudo)


def hay_internet():
    wlan = network.WLAN(network.STA_IF)
    return wlan.active() and wlan.isconnected()


# ---------------------------------------------------------------------------
# Portal de configuracion
# ---------------------------------------------------------------------------
PAGINA = """<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar Eden Air</title>
<style>
 *{box-sizing:border-box}
 body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:#f6f4ec;
      color:#14231b;display:flex;justify-content:center;padding:20px 18px 40px}
 .caja{width:100%;max-width:400px}
 .marca{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
      color:#2f6b4f;margin-bottom:10px}
 h1{font-size:23px;line-height:1.25;margin:0 0 8px}
 .lede{color:#6e7d73;font-size:14px;line-height:1.55;margin:0 0 18px}
 .err{background:#fdeaea;border:1px solid #f3c2c2;color:#8f2525;padding:12px 14px;
      border-radius:12px;font-size:14px;line-height:1.5;margin:0 0 18px}
 .err b{display:block;margin-bottom:2px}
 label{display:block;font-size:13px;font-weight:700;margin:16px 0 6px}
 select,input{width:100%;padding:12px;font-size:16px;border:1px solid #dcdfd2;
      border-radius:11px;background:#fff;color:#14231b;font-family:inherit}
 select:focus,input:focus{outline:2px solid #2f6b4f;outline-offset:1px}
 .clave{position:relative}
 .clave input{padding-right:64px}
 .ver{position:absolute;right:6px;top:6px;bottom:6px;width:52px;border:0;
      border-radius:8px;background:#eef1e8;color:#2f6b4f;font-size:12px;
      font-weight:700;padding:0}
 .ayuda{font-size:12.5px;color:#8a968e;margin:7px 0 0;line-height:1.45}
 button.enviar{width:100%;margin-top:22px;padding:15px;font-size:16px;font-weight:700;
      border:0;border-radius:12px;background:#2f6b4f;color:#fff}
 button.enviar:disabled{opacity:.55}
 details{margin-top:20px;border-top:1px solid #e4e7dc;padding-top:14px}
 summary{font-size:13px;color:#6e7d73;font-weight:600}
 .aviso{margin-top:24px;padding:13px 14px;border-radius:12px;background:#eef1e8;
      font-size:13px;line-height:1.5;color:#4a5a50}
 [hidden]{display:none}
</style></head><body>
<div class="caja">
 <div class="marca">Eden Air</div>
 <h1>Conect&aacute; tu equipo al WiFi de tu casa</h1>
 <p class="lede">El Eden Air necesita tu WiFi para enviarle las mediciones al panel.
    Eleg&iacute; tu red y escrib&iacute; <b>la clave de tu WiFi</b> (no la del c&oacute;digo QR).</p>

 %ERROR%

 <form method="POST" action="/guardar" onsubmit="this.enviar.disabled=true;this.enviar.textContent='Probando la clave...'">
  <label for="ssid">Tu red WiFi</label>
  <select id="ssid" name="ssid" onchange="document.getElementById('fila_otra').hidden=(this.value!='__otra__')">%REDES%</select>
  <p class="ayuda">Ordenadas por se&ntilde;al: la tuya suele ser la primera. &#9679;&#9679;&#9679;&#9679; es se&ntilde;al fuerte.</p>

  <div id="fila_otra" hidden>
   <label for="ssid_manual">Nombre de la red</label>
   <input id="ssid_manual" name="ssid_manual" placeholder="Escrib&iacute; el nombre exacto" autocapitalize="none" autocorrect="off" spellcheck="false">
  </div>

  <label for="password">Contrase&ntilde;a de tu WiFi</label>
  <div class="clave">
   <input id="password" name="password" type="password" placeholder="La clave de tu router" autocapitalize="none" autocorrect="off" spellcheck="false">
   <button type="button" class="ver" onclick="var c=document.getElementById('password');var v=c.type=='password';c.type=v?'text':'password';this.textContent=v?'Ocultar':'Ver'">Ver</button>
  </div>
  <p class="ayuda">Toc&aacute; &laquo;Ver&raquo; para revisar lo que escribiste. La may&uacute;scula y min&uacute;scula importan.</p>

  <details>
   <summary>Opciones avanzadas</summary>
   <label for="servidor">Direcci&oacute;n del servidor EdenAir</label>
   <input id="servidor" name="servidor" value="%SERVIDOR%" autocapitalize="none" autocorrect="off" spellcheck="false">
   <p class="ayuda">Solo si moviste EdenAir a otra computadora o cambi&oacute; su IP.
      Si no sab&eacute;s qu&eacute; es esto, dejalo como est&aacute;.</p>
  </details>

  <button name="enviar" class="enviar" type="submit">Conectar</button>
 </form>

 <div class="aviso">
  <b>&iquest;Tu celular avisa que esta red no tiene internet?</b><br>
  Es normal y esperable: esta red es el propio Eden Air, no da internet.
  Eleg&iacute; &laquo;mantener conexi&oacute;n&raquo; hasta terminar.
 </div>
</div></body></html>"""

PAGINA_OK = """<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Probando la conexion</title>
<style>
 *{box-sizing:border-box}
 body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:#f6f4ec;
 color:#14231b;display:flex;align-items:center;justify-content:center;min-height:100vh;
 text-align:center;padding:26px 20px}
 .caja{max-width:380px;width:100%}
 .tic{font-size:42px;line-height:1;margin-bottom:10px}
 h1{font-size:21px;margin:0 0 10px;line-height:1.3}
 p{color:#6e7d73;line-height:1.55;font-size:14.5px;margin:0 0 14px}
 .seguir{display:block;margin:22px 0 0;padding:15px;border-radius:12px;
 background:#2f6b4f;color:#fff;font-size:16px;font-weight:700;text-decoration:none}
 .pasos{margin:14px 0 0;padding:13px 15px;border-radius:12px;background:#eef1e8;
 font-size:13px;line-height:1.55;color:#4a5a50;text-align:left}
 .pasos b{color:#14231b}
</style></head>
<body><div class="caja">
<div class="tic">&#10003;</div>
<h1>Probando la conexi&oacute;n con %SSID%</h1>
<p>Tu celular va a volver solo a su WiFi normal en unos segundos.
Cuando vuelva, toc&aacute; el bot&oacute;n:</p>

<a class="seguir" href="%PANEL%">Ver mi Eden Air</a>

<div class="pasos">
 <b>&iquest;Y si no anduvo?</b><br>
 Si la clave estaba mal, la red <b>%AP%</b> vuelve a aparecer en tu celular en
 menos de un minuto. Conectate de nuevo y te decimos qu&eacute; pas&oacute;.
</div>
</div></body></html>"""

# Lo que se muestra arriba del formulario cuando el intento anterior fallo.
BANNER_ERROR = """<div class="err"><b>%TITULO%</b>%DETALLE%</div>"""


def _redes_disponibles():
    """
    Escanea y devuelve las redes visibles, de la mas fuerte a la mas debil.

    Cada elemento es (nombre, rssi, protegida). El rssi sirve para dibujar las
    barritas de señal: en una casa hay varias redes visibles (la del vecino
    tambien) y el nombre solo no alcanza para saber cual es la tuya.

    Formato de wlan.scan() en MicroPython:
      (ssid, bssid, canal, RSSI, authmode, oculta)   authmode 0 = abierta
    """
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)

    encontradas = []
    nombres = []

    try:
        for red in wlan.scan():
            nombre = red[0].decode("utf-8", "ignore")

            if not nombre or nombre in nombres or nombre == config.AP_SSID:
                continue

            nombres.append(nombre)
            encontradas.append((nombre, red[3], red[4] != 0))
    except OSError:
        pass

    # De la mas fuerte a la mas debil: la de tu casa casi siempre es la primera.
    encontradas.sort(key=lambda r: r[1], reverse=True)
    return encontradas


def _barras(rssi):
    """Traduce el RSSI (dBm, negativo) a cuatro circulitos llenos o vacios."""
    if rssi >= -55:
        llenas = 4
    elif rssi >= -65:
        llenas = 3
    elif rssi >= -75:
        llenas = 2
    else:
        llenas = 1

    return "●" * llenas + "○" * (4 - llenas)


def _escapar_html(texto):
    """
    Escapa lo minimo para que un SSID raro no rompa la pagina.

    Los nombres de red los elige el vecino, no nosotros: uno que contenga
    & o " dejaria el <option> mal armado y la lista aparecerian cortada.
    """
    return (texto.replace("&", "&amp;")
                 .replace("<", "&lt;")
                 .replace(">", "&gt;")
                 .replace('"', "&quot;"))


def _opciones_html(redes):
    """Arma los <option> de la lista de redes, con señal y candado."""
    if not redes:
        return '<option value="">(no se detectaron redes)</option>'

    partes = []
    for nombre, rssi, protegida in redes:
        limpio = _escapar_html(nombre)
        partes.append(
            '<option value="%s">%s &nbsp; %s %s</option>' % (
                limpio,
                limpio,
                _barras(rssi),
                "\U0001f512" if protegida else "",
            )
        )

    partes.append('<option value="__otra__">✎  Otra red (escribirla a mano)</option>')
    return "".join(partes)


def _enviar_todo(cliente, texto):
    """
    Manda el texto completo, aunque el socket lo acepte de a pedazos.

    socket.send() puede escribir MENOS de lo que se le dio y devolver cuanto
    escribio. Con la pagina vieja (chica) casi nunca pasaba; con esta, mas
    grande, pasa seguido: si no se reintenta, el celular recibe HTML cortado
    y muestra una pagina rota a la mitad.
    """
    datos = texto.encode("utf-8") if isinstance(texto, str) else texto
    enviados = 0

    while enviados < len(datos):
        try:
            escritos = cliente.send(datos[enviados:])
        except OSError:
            return False

        if not escritos:
            return False

        enviados += escritos

    return True


# URLs con las que Android, iOS y Windows preguntan "¿hay internet acá?".
# Contestarles con un redirect es lo que hace que el telefono abra la pagina
# solo. El truco del DNS no alcanza: si el celular tiene DNS privado (DoH/DoT)
# ignora nuestras respuestas DNS y sin esto no se abre nada.
SONDAS_PORTAL = (
    "/generate_204",           # Android
    "/gen_204",                # Android (variante)
    "/hotspot-detect.html",    # iOS y macOS
    "/library/test/success.html",
    "/ncsi.txt",               # Windows
    "/connecttest.txt",        # Windows
    "/canonical.html",         # Ubuntu / Firefox
    "/success.txt",            # Firefox
)


def _es_sonda(camino):
    """¿Este pedido es el chequeo de internet del sistema operativo?"""
    for sonda in SONDAS_PORTAL:
        if camino.startswith(sonda):
            return True
    return False


def _desescapar(texto):
    """Deshace el encoding de formulario (%XX y '+')."""
    texto = texto.replace("+", " ")
    partes = texto.split("%")
    salida = partes[0]
    for parte in partes[1:]:
        try:
            salida += chr(int(parte[:2], 16)) + parte[2:]
        except ValueError:
            salida += "%" + parte
    return salida


def _respuesta_dns(consulta, ip):
    """
    Arma una respuesta DNS que apunta CUALQUIER nombre a la IP del equipo.

    Es lo que hace que el celular abra la pagina solo: apenas se conecta,
    consulta un dominio para chequear si hay internet; le respondemos con
    nuestra propia IP, el chequeo "falla" de una forma que el sistema
    interpreta como portal cautivo, y abre el navegador.
    """
    respuesta = consulta[:2]            # mismo ID de la consulta
    respuesta += b"\x81\x80"            # respuesta estandar, sin error
    respuesta += consulta[4:6]          # cantidad de preguntas
    respuesta += consulta[4:6]          # misma cantidad de respuestas
    respuesta += b"\x00\x00\x00\x00"    # sin registros de autoridad ni extra
    respuesta += consulta[12:]          # se repite la pregunta original
    respuesta += b"\xc0\x0c"            # puntero al nombre preguntado
    respuesta += b"\x00\x01\x00\x01"    # tipo A, clase IN
    respuesta += b"\x00\x00\x00\x3c"    # vale 60 segundos
    respuesta += b"\x00\x04"            # la direccion ocupa 4 bytes
    respuesta += bytes(int(x) for x in ip.split("."))
    return respuesta


def abrir_portal(error=None):
    """
    Levanta el punto de acceso y atiende hasta que alguien manda un WiFi.

    Se queda bloqueado hasta conseguirlo. Devuelve (ssid, password, servidor).

    `error` es el motivo por el que fallo el intento ANTERIOR. Se muestra
    arriba del formulario: sin esto, una clave mal tipeada dejaba al equipo
    reabriendo el portal en silencio y el usuario se quedaba mirando la web
    para siempre, sin enterarse de que tenia que volver al celular.

    ORDEN IMPORTANTE: primero se escanean las redes y recien despues se
    levanta el punto de acceso. La ESP32 tiene UNA sola antena: escanear
    mientras el AP esta activo la hace saltar de canal y le corta la conexion
    al celular ("se detecto un cambio en la red"). Por eso el escaneo va
    antes, y la interfaz de estacion se apaga mientras dura el portal.
    """
    # 1) Escanear ANTES de levantar el AP, con la antena libre.
    print("Buscando redes WiFi cercanas...")
    redes = _redes_disponibles()
    opciones = _opciones_html(redes)
    print("Redes encontradas:", len(redes))

    # 2) El banner rojo, solo si venimos de un intento fallido.
    if error:
        banner = (BANNER_ERROR
                  .replace("%TITULO%", _escapar_html(error[0]))
                  .replace("%DETALLE%", _escapar_html(error[1])))
    else:
        banner = ""

    # El codigo con el que el celular va a poder seguir la vinculacion desde la
    # web. Se reutiliza el pendiente si ya habia uno: si el usuario erro la
    # clave y vuelve a intentar, el link que ya tiene en la mano sigue valiendo.
    sesion = leer_sesion() or nueva_sesion()

    # 3) Apagar la interfaz de estacion: nada mas debe tocar la antena.
    sta = network.WLAN(network.STA_IF)
    sta.active(False)
    time.sleep_ms(300)

    # 4) Recien ahora, el punto de acceso.
    ap = network.WLAN(network.AP_IF)
    ap.active(True)
    ap.config(essid=config.AP_SSID, password=config.AP_PASSWORD,
              authmode=network.AUTH_WPA_WPA2_PSK)

    while not ap.active():
        time.sleep_ms(100)

    ip_portal = ap.ifconfig()[0]
    print("Punto de acceso activo:", config.AP_SSID)
    print("Si no se abre sola, entra desde el celular a http://" + ip_portal)

    # 5) Dos servidores a la vez: DNS (para que abra solo) y HTTP (la pagina).
    dns = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    dns.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    dns.bind(("0.0.0.0", 53))
    dns.setblocking(False)

    web = socket.socket()
    web.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    web.bind(("0.0.0.0", 80))
    web.listen(5)
    web.setblocking(False)

    vigilante = select.poll()
    vigilante.register(dns, select.POLLIN)
    vigilante.register(web, select.POLLIN)

    elegido = None

    try:
        while elegido is None:
            for origen, _evento in vigilante.poll(1000):

                # ---- Consulta DNS: todo apunta al equipo ----
                if origen is dns:
                    try:
                        consulta, remitente = dns.recvfrom(256)
                        if len(consulta) > 12:
                            dns.sendto(_respuesta_dns(consulta, ip_portal), remitente)
                    except OSError:
                        pass
                    continue

                # ---- Pedido HTTP ----
                try:
                    cliente, _ = web.accept()
                except OSError:
                    continue

                try:
                    cliente.settimeout(5)
                    pedido = cliente.recv(1024).decode("utf-8", "ignore")
                    camino = pedido.split(" ")[1] if " " in pedido else "/"

                    if pedido.startswith("POST /guardar"):
                        cuerpo = pedido.split("\r\n\r\n", 1)[1] if "\r\n\r\n" in pedido else ""
                        campos = {}
                        for par in cuerpo.split("&"):
                            if "=" in par:
                                k, v = par.split("=", 1)
                                campos[k] = _desescapar(v)

                        ssid = campos.get("ssid", "").strip()
                        password = campos.get("password", "")

                        # "Otra red": el nombre lo escribio a mano porque la
                        # suya esta oculta o no aparecio en el escaneo.
                        if ssid == "__otra__":
                            ssid = campos.get("ssid_manual", "").strip()

                        # La direccion del servidor solo se guarda si de verdad
                        # cambio: no queremos crear servidor.json en cada alta.
                        nuevo_servidor = campos.get("servidor", "").strip().rstrip("/")

                        if ssid:
                            # El link al panel se arma con la direccion que el
                            # usuario acaba de confirmar en el formulario, no
                            # con la vieja: si la corrigio, el boton tiene que
                            # apuntar al servidor nuevo.
                            base = nuevo_servidor or config.servidor()
                            panel = base + "/vinculacion/seguir?s=" + sesion

                            # Se persiste ANTES de mostrar el link: a partir de
                            # aca el usuario lo tiene en la mano, asi que el
                            # codigo tiene que sobrevivir a un reinicio.
                            try:
                                guardar_sesion(sesion)
                            except OSError:
                                pass

                            _enviar_todo(cliente,
                                "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\nConnection: close\r\n\r\n"
                                + PAGINA_OK.replace("%SSID%", _escapar_html(ssid))
                                           .replace("%AP%", _escapar_html(config.AP_SSID))
                                           .replace("%PANEL%", _escapar_html(panel)))
                            elegido = (ssid, password, nuevo_servidor)
                        else:
                            _enviar_todo(cliente, "HTTP/1.1 303 See Other\r\nLocation: /\r\nConnection: close\r\n\r\n")

                    elif _es_sonda(camino):
                        # El sistema operativo esta chequeando si hay internet.
                        # Un 302 hacia el portal es la señal universal de
                        # "aca hay que iniciar sesion" y dispara la ventana
                        # automatica. Funciona aunque el DNS este secuestrado
                        # por un DNS privado del propio telefono.
                        _enviar_todo(cliente,
                            "HTTP/1.1 302 Found\r\nLocation: http://" + ip_portal +
                            "/\r\nCache-Control: no-store\r\nConnection: close\r\n\r\n")

                    else:
                        # Cualquier otra URL devuelve el formulario. Junto con
                        # el DNS y las sondas, esto hace que el celular lo abra
                        # solo casi siempre.
                        _enviar_todo(cliente,
                            "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\nCache-Control: no-store\r\nConnection: close\r\n\r\n"
                            + PAGINA.replace("%REDES%", opciones)
                                    .replace("%ERROR%", banner)
                                    .replace("%SERVIDOR%", _escapar_html(config.servidor())))
                except OSError:
                    pass
                finally:
                    cliente.close()
    finally:
        vigilante.unregister(dns)
        vigilante.unregister(web)
        dns.close()
        web.close()
        time.sleep(1)
        ap.active(False)

    return elegido


def guardar_servidor(url):
    """Guarda la direccion del servidor elegida desde el portal."""
    with open(config.ARCHIVO_SERVIDOR, "w") as f:
        json.dump({"url": url}, f)


def asegurar_conexion():
    """
    Deja el equipo conectado a internet, cueste lo que cueste.

    Si tiene red guardada la usa; si falla o no hay, abre el portal.
    No devuelve hasta estar conectado.

    EL PUNTO IMPORTANTE: cuando un intento falla, el motivo se le pasa al
    portal siguiente. Asi el usuario que puso mal la clave vuelve a conectarse
    a EdenAir-Setup y encuentra el cartel rojo explicandole que paso, en vez
    de un formulario vacio identico al anterior.
    """
    ssid, password = leer_wifi_guardado()
    error = None

    if ssid:
        ip, error = conectar(ssid, password)
        if ip:
            return ip
        print("La red guardada ya no funciona. Abriendo el portal.")

    while True:
        ssid, password, nuevo_servidor = abrir_portal(error)

        # La direccion del servidor se guarda ANTES de probar el WiFi: aunque
        # la clave este mal, lo que el usuario corrigio no se pierde.
        if nuevo_servidor and nuevo_servidor != config.servidor():
            guardar_servidor(nuevo_servidor)
            print("Servidor actualizado:", nuevo_servidor)

        ip, error = conectar(ssid, password)

        if ip:
            guardar_wifi(ssid, password)
            return ip

        print("No se pudo conectar con esos datos. Reabriendo el portal.")
