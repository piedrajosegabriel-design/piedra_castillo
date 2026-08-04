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
# Conexion normal
# ---------------------------------------------------------------------------
def conectar(ssid, password, espera=20):
    """Intenta conectarse. Devuelve la IP, o None si no pudo."""
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)

    if wlan.isconnected():
        return wlan.ifconfig()[0]

    print("Conectando a", ssid, "...")
    wlan.connect(ssid, password)

    for _ in range(espera * 2):
        if wlan.isconnected():
            ip = wlan.ifconfig()[0]
            print("Conectado. IP:", ip)
            return ip
        time.sleep_ms(500)

    print("No se pudo conectar a", ssid)
    wlan.active(False)
    return None


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
 body{margin:0;font-family:system-ui,sans-serif;background:#f6f4ec;color:#14231b;
      display:flex;justify-content:center;padding:24px}
 .caja{width:100%;max-width:380px}
 h1{font-size:22px;margin:0 0 6px}
 p{color:#6e7d73;font-size:14px;line-height:1.5;margin:0 0 20px}
 label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
 select,input{width:100%;box-sizing:border-box;padding:11px 12px;font-size:15px;
      border:1px solid #dcdfd2;border-radius:10px;background:#fff;color:#14231b}
 button{width:100%;margin-top:22px;padding:13px;font-size:16px;font-weight:600;
      border:0;border-radius:10px;background:#2f6b4f;color:#fff}
 button:disabled{opacity:.6}
 .ok{margin-top:18px;padding:12px;border-radius:10px;background:#dff0e4;font-size:14px}
</style></head><body>
<div class="caja">
 <h1>Conecta tu Eden Air</h1>
 <p>Elegi el WiFi de tu casa para que el equipo pueda enviar sus mediciones.</p>
 <form method="POST" action="/guardar" onsubmit="this.b.disabled=true;this.b.textContent='Conectando...'">
  <label for="ssid">Red WiFi</label>
  <select id="ssid" name="ssid">%REDES%</select>
  <label for="password">Contrase&ntilde;a</label>
  <input id="password" name="password" type="password" placeholder="Clave de tu WiFi">
  <button name="b" type="submit">Conectar</button>
 </form>
</div></body></html>"""

PAGINA_OK = """<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Listo</title>
<style>body{margin:0;font-family:system-ui,sans-serif;background:#f6f4ec;color:#14231b;
 display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;padding:24px}
 h1{font-size:22px;margin:0 0 8px} p{color:#6e7d73;line-height:1.5}</style></head>
<body><div><h1>&#10003; Listo</h1>
<p>El equipo se esta conectando a <b>%SSID%</b>.<br>
Ya podes cerrar esta pagina y volver a EdenAir.</p></div></body></html>"""


def _redes_disponibles():
    """Escanea y devuelve los SSID visibles, del mas fuerte al mas debil."""
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)

    vistas = []
    try:
        for red in wlan.scan():
            nombre = red[0].decode("utf-8", "ignore")
            if nombre and nombre not in vistas and nombre != config.AP_SSID:
                vistas.append(nombre)
    except OSError:
        pass

    return vistas


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


def abrir_portal():
    """
    Levanta el punto de acceso y atiende hasta que alguien manda un WiFi.

    Se queda bloqueado hasta conseguirlo. Devuelve (ssid, password).

    ORDEN IMPORTANTE: primero se escanean las redes y recien despues se
    levanta el punto de acceso. La ESP32 tiene UNA sola antena: escanear
    mientras el AP esta activo la hace saltar de canal y le corta la conexion
    al celular ("se detecto un cambio en la red"). Por eso el escaneo va
    antes, y la interfaz de estacion se apaga mientras dura el portal.
    """
    # 1) Escanear ANTES de levantar el AP, con la antena libre.
    print("Buscando redes WiFi cercanas...")
    redes = _redes_disponibles()

    opciones = "".join("<option>%s</option>" % r for r in redes)
    if not opciones:
        opciones = "<option>(no se detectaron redes)</option>"
    print("Redes encontradas:", len(redes))

    # 2) Apagar la interfaz de estacion: nada mas debe tocar la antena.
    sta = network.WLAN(network.STA_IF)
    sta.active(False)
    time.sleep_ms(300)

    # 3) Recien ahora, el punto de acceso.
    ap = network.WLAN(network.AP_IF)
    ap.active(True)
    ap.config(essid=config.AP_SSID, password=config.AP_PASSWORD,
              authmode=network.AUTH_WPA_WPA2_PSK)

    while not ap.active():
        time.sleep_ms(100)

    ip_portal = ap.ifconfig()[0]
    print("Punto de acceso activo:", config.AP_SSID)
    print("Si no se abre sola, entra desde el celular a http://" + ip_portal)

    # 4) Dos servidores a la vez: DNS (para que abra solo) y HTTP (la pagina).
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

                    if pedido.startswith("POST /guardar"):
                        cuerpo = pedido.split("\r\n\r\n", 1)[1] if "\r\n\r\n" in pedido else ""
                        campos = {}
                        for par in cuerpo.split("&"):
                            if "=" in par:
                                k, v = par.split("=", 1)
                                campos[k] = _desescapar(v)

                        ssid = campos.get("ssid", "").strip()
                        password = campos.get("password", "")

                        if ssid:
                            cliente.send("HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\nConnection: close\r\n\r\n")
                            cliente.send(PAGINA_OK.replace("%SSID%", ssid))
                            elegido = (ssid, password)
                        else:
                            cliente.send("HTTP/1.1 303 See Other\r\nLocation: /\r\nConnection: close\r\n\r\n")
                    else:
                        # Cualquier URL devuelve el formulario. Junto con el DNS,
                        # esto hace que el celular lo abra solo.
                        cliente.send("HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\nConnection: close\r\n\r\n")
                        cliente.send(PAGINA.replace("%REDES%", opciones))
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


def asegurar_conexion():
    """
    Deja el equipo conectado a internet, cueste lo que cueste.

    Si tiene red guardada la usa; si falla o no hay, abre el portal.
    No devuelve hasta estar conectado.
    """
    ssid, password = leer_wifi_guardado()

    if ssid:
        ip = conectar(ssid, password)
        if ip:
            return ip
        print("La red guardada ya no funciona. Abriendo el portal.")

    while True:
        ssid, password = abrir_portal()
        ip = conectar(ssid, password)

        if ip:
            guardar_wifi(ssid, password)
            return ip

        print("No se pudo conectar con esos datos. Reintentando.")
