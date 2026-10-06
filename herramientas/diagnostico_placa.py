"""
diagnostico_placa.py — Dice en castellano por que la placa no manda datos.

Como usarlo (Thonny):
  1. Apreta Stop para frenar la placa.
  2. Abri este archivo y apreta el boton verde "Ejecutar" (F5).
  3. Lee lo que dice abajo, en la consola. Cada paso dice OK o el problema.

No borra ni cambia nada en la placa: solo mira.
"""

import json
import os
import time

import network

import config

try:
    import urequests
except ImportError:
    urequests = None


def paso(texto):
    print("\n--- " + texto)


def fallo(problema, solucion):
    print("  PROBLEMA: " + problema)
    print("  SOLUCION: " + solucion)
    print("\n=== Arregla esto y volve a correr el diagnostico ===")
    raise SystemExit


def leer_json(nombre):
    try:
        with open(nombre) as f:
            return json.load(f)
    except (OSError, ValueError):
        return None


print("\n=== DIAGNOSTICO EDEN AIR ===")

# 1) Archivos de la placa ---------------------------------------------------
paso("1) Archivos en la placa")
archivos = os.listdir()
print("  " + ", ".join(archivos))

for necesario in ("main.py", "config.py", "red.py", "servidor.py"):
    if necesario not in archivos:
        fallo("Falta " + necesario + " en la placa.",
              "Subilo con Thonny: abrilo y Guardar como -> Dispositivo MicroPython.")
print("  OK: estan los archivos del programa.")

# 1b) Sensor SCD41 ------------------------------------------------------------
paso("1b) Sensor SCD41 (temperatura, humedad, CO2)")
try:
    from sensor import SCD41, ErrorSensor
    try:
        print("  Buscando el sensor y pidiendole una medicion (hasta 20 s)...")
        co2, temp, hum = SCD41().leer()
        print("  OK: %s C  %s %%  %s ppm" % (temp, hum, co2))
    except ErrorSensor as e:
        fallo(str(e),
              "Con la placa DESENCHUFADA revisa los 4 cables del SCD41: VCC->3V3, "
              "GND->GND, SDA->GPIO%s, SCL->GPIO%s. Que esten firmes (sin falso "
              "contacto) y que SDA y SCL no esten al reves." % (config.PIN_I2C_SDA, config.PIN_I2C_SCL))
except ImportError:
    print("  (no se pudo cargar sensor.py: se saltea)")

# 2) Direccion del servidor -------------------------------------------------
paso("2) Direccion del servidor que usa la placa")
servidor = config.servidor()
print("  config.py dice:     " + config.SERVIDOR_DEFECTO)
guardado = leer_json(config.ARCHIVO_SERVIDOR)
if guardado:
    print("  servidor.json dice: " + str(guardado.get("url")) + "   <- ESTE es el que se usa")
print("  Se usa:             " + servidor)

if "localhost" in servidor or "127.0.0.1" in servidor:
    fallo("La direccion usa localhost: para la placa eso es ella misma.",
          "Pone la IPv4 de tu PC (cmd -> ipconfig) en la linea 27 de config.py.")
print("  OK: la direccion tiene buena pinta.")

# 3) WiFi --------------------------------------------------------------------
paso("3) WiFi de tu casa")
wifi = leer_json(config.ARCHIVO_WIFI)
if not wifi:
    fallo("No hay WiFi guardado (falta wifi.json).",
          "Reinicia la placa (Ctrl+D): va a aparecer la red EdenAir-Setup en el celular.")
print("  Red guardada: " + str(wifi.get("ssid")))

sta = network.WLAN(network.STA_IF)
sta.active(True)
if not sta.isconnected():
    print("  Conectando...")
    sta.connect(wifi.get("ssid"), wifi.get("password"))
    for _ in range(40):
        if sta.isconnected():
            break
        time.sleep(0.5)

if not sta.isconnected():
    fallo("La placa no entra a la red " + str(wifi.get("ssid")) + " (estado %s)." % sta.status(),
          "Revisa que la red sea de 2.4 GHz (la ESP32 no ve las de 5 GHz) y que este "
          "cerca. Si cambiaste la clave, borra wifi.json y reinicia para cargarla de nuevo.")

ip = sta.ifconfig()[0]
print("  OK: conectada. IP de la placa: " + ip)

# La PC y la placa tienen que estar en la misma red (mismos 3 primeros numeros).
host = servidor.split("//", 1)[-1].split("/", 1)[0].split(":")[0]
if host.count(".") == 3 and ip.rsplit(".", 1)[0] != host.rsplit(".", 1)[0]:
    print("  OJO: la placa esta en %s.x y el servidor en %s.x: son redes distintas."
          % (ip.rsplit(".", 1)[0], host.rsplit(".", 1)[0]))
    print("  Si el paso 4 falla, es por esto: pone la IPv4 actual de tu PC en config.py.")

# 4) Llegar a la web -----------------------------------------------------------
paso("4) La placa llega a la web EdenAir?")
if urequests is None:
    fallo("Falta la libreria urequests en la placa.",
          "Instalala en Thonny: Herramientas -> Administrar paquetes -> urequests.")

try:
    r = urequests.get(servidor + "/")
    estado = r.status_code
    r.close()
except OSError as e:
    fallo("La placa no llega a " + host + " (%s)." % e,
          "1) Que XAMPP tenga Apache prendido. 2) Que " + host + " sea la IPv4 ACTUAL de tu PC "
          "(cmd -> ipconfig). 3) Firewall de Windows: Permitir una aplicacion -> Apache HTTP "
          "Server -> tildar Privada. 4) La PC tiene que estar en el MISMO WiFi que la placa.")

print("  La web contesto HTTP %s" % estado)
if estado == 404:
    fallo("La web existe pero esa carpeta no (HTTP 404).",
          "La carpeta en C:\\xampp\\htdocs\\ tiene que llamarse igual que en la direccion: "
          + servidor)
if estado >= 500:
    fallo("La web tiene un error interno (HTTP %s)." % estado,
          "Abri la web en la PC y fijate el error. Suele ser MySQL apagado o el .env mal.")
print("  OK: la placa llega a la web.")

# 5) Credenciales ---------------------------------------------------------------
paso("5) Vinculacion con tu cuenta")
cred = leer_json(config.ARCHIVO_CREDENCIALES)
if not cred:
    print("  La placa no esta vinculada todavia (normal si es nueva).")
    print("  Entra a la web -> Mis dispositivos -> Conectar, y despues Ctrl+D en Thonny.")
    print("\n=== Todo lo demas esta OK ===")
    raise SystemExit

uid = cred.get("device_uid")
r = urequests.get(servidor + "/api/devices/%s/config" % uid,
                  headers={"X-Device-Token": str(cred.get("api_token"))})
estado = r.status_code
r.close()
print("  Equipo " + str(uid) + " -> HTTP %s" % estado)

if estado in (401, 403, 404):
    fallo("La web no reconoce las credenciales de la placa (la base se cambio o se borro).",
          "Borra credenciales.json de la placa, entra a la web -> Conectar, y Ctrl+D.")
if estado != 200:
    fallo("La web contesto algo raro (HTTP %s)." % estado, "Pasale este numero a quien te ayuda.")

print("  OK: la web reconoce a la placa.")
print("\n=== TODO OK. Apreta Ctrl+D: la placa arranca y manda mediciones cada 30 s ===")
