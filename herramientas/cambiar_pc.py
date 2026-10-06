"""
cambiar_pc.py — Conecta la placa a OTRA PC (otra casa, la escuela, otra red).

QUE HACE
  Te pide la IPv4 de la PC que tiene XAMPP, la guarda en la placa
  (servidor.json), prueba que la placa llegue a la web y te dice el
  siguiente paso. No hace falta tocar config.py.

COMO USARLO (Thonny)
  1. En la PC nueva: cmd -> ipconfig -> anota la "Direccion IPv4".
  2. XAMPP abierto, con Apache y MySQL en verde.
  3. En Thonny apreta Stop, abri este archivo y apreta Ejecutar (F5).
  4. Escribi la IP cuando te la pida y segui lo que dice abajo.
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

# Nombres con los que suele estar la carpeta del proyecto en C:\xampp\htdocs\.
# Se prueban en orden y se usa la primera que exista en esa PC.
CARPETAS = ("EdenAir", "piedra_castillo", "edenair", "piedra_castillo-main")


def fin(problema, solucion):
    print("\n  PROBLEMA: " + problema)
    print("  SOLUCION: " + solucion)
    print("\n=== Arregla eso y volve a ejecutar este archivo (F5) ===")
    raise SystemExit


def leer_json(nombre):
    try:
        with open(nombre) as f:
            return json.load(f)
    except (OSError, ValueError):
        return None


def ip_valida(texto):
    partes = texto.split(".")
    if len(partes) != 4:
        return False
    for p in partes:
        if not p.isdigit() or int(p) > 255:
            return False
    return True


print("\n=== CONECTAR LA PLACA A OTRA PC ===")
print("Servidor actual: " + config.servidor())

# 1) La IP de la PC -----------------------------------------------------------
ip_pc = ""
while not ip_valida(ip_pc):
    ip_pc = input("\nEscribi la IPv4 de la PC (ej: 192.168.1.194) y Enter: ").strip()
    if not ip_valida(ip_pc):
        print("  Eso no es una IP. Tiene que ser como 192.168.1.194")

print("\n[1/4] PC: " + ip_pc)

# 3) WiFi -----------------------------------------------------------------------
wifi = leer_json(config.ARCHIVO_WIFI)
if not wifi:
    fin("La placa no tiene WiFi guardado.",
        "Apreta Ctrl+D. Va a aparecer la red EdenAir-Setup en el "
        "celular; conectate, elegi tu WiFi y despues ejecuta esto de nuevo.")

sta = network.WLAN(network.STA_IF)
sta.active(True)
if not sta.isconnected():
    print("[2/4] Conectando a " + str(wifi.get("ssid")) + "...")
    sta.connect(wifi.get("ssid"), wifi.get("password"))
    for _ in range(40):
        if sta.isconnected():
            break
        time.sleep(0.5)

if not sta.isconnected():
    fin("La placa no entra al WiFi " + str(wifi.get("ssid")) + ".",
        "Si estas en OTRO WiFi (otra casa, la escuela), borra wifi.json desde "
        "Ver -> Archivos, apreta Ctrl+D y carga el WiFi nuevo desde el celular "
        "(red EdenAir-Setup). Despues ejecuta esto de nuevo.")

ip_placa = sta.ifconfig()[0]
print("[2/4] WiFi OK: " + str(wifi.get("ssid")) + " | IP de la placa: " + ip_placa)

if ip_placa.rsplit(".", 1)[0] != ip_pc.rsplit(".", 1)[0]:
    fin("La placa (%s) y la PC (%s) estan en redes distintas." % (ip_placa, ip_pc),
        "Conecta la PC al MISMO WiFi que la placa (" + str(wifi.get("ssid")) +
        "), volve a mirar ipconfig y ejecuta esto de nuevo.")

# 4) Llegar a la web ------------------------------------------------------------
if urequests is None:
    fin("Falta la libreria urequests en la placa.",
        "Thonny: Herramientas -> Administrar paquetes -> urequests -> Instalar.")

def probar(carpeta):
    """HTTP de http://IP/carpeta/public/ (OSError si la PC no contesta)."""
    r = urequests.get("http://%s/%s/public/" % (ip_pc, carpeta))
    estado = r.status_code
    r.close()
    return estado


carpeta = None
estado = 404
candidatas = list(CARPETAS)

while carpeta is None:
    for nombre in candidatas:
        try:
            estado = probar(nombre)
        except OSError as e:
            fin("La placa no llega a la PC %s (%s)." % (ip_pc, e),
                "1) XAMPP: Apache en verde. 2) Firewall de Windows: Panel de control -> "
                "Firewall -> Permitir una aplicacion -> Cambiar configuracion -> Apache "
                "HTTP Server -> tildar Privada y Publica. 3) Revisa que la IP sea la de ipconfig.")
        print("  probando carpeta '%s' -> HTTP %s" % (nombre, estado))
        if estado != 404:
            carpeta = nombre
            break

    if carpeta is None:
        print("\n  La PC contesta, pero ninguna de esas carpetas existe.")
        print("  Mira en la PC como se llama la carpeta del proyecto en C:\\xampp\\htdocs\\")
        nombre = input("  Escribi ese nombre (o Enter para salir): ").strip().strip("/\\")
        if not nombre:
            fin("No se encontro la carpeta del proyecto en la PC.",
                "Abri C:\\xampp\\htdocs\\ en la PC, fijate el nombre de la carpeta "
                "de EdenAir y ejecuta esto de nuevo.")
        candidatas = [nombre]

url = "http://%s/%s/public" % (ip_pc, carpeta)

if estado >= 500:
    fin("La web tiene un error (HTTP %s)." % estado,
        "MySQL tiene que estar en verde en XAMPP y el .env bien configurado. "
        "Abri http://localhost/" + carpeta + "/public/ en la PC para ver el error.")

# Recien ahora se guarda: solo una direccion que se comprobo que anda.
f = open(config.ARCHIVO_SERVIDOR, "w")
f.write(json.dumps({"url": url}))
f.close()
print("  Guardado en la placa: " + url)
print("[3/4] La placa llega a la web: OK")

# 5) Vinculacion ----------------------------------------------------------------
cred = leer_json(config.ARCHIVO_CREDENCIALES)
if cred:
    r = urequests.get(url + "/api/devices/%s/config" % cred.get("device_uid"),
                      headers={"X-Device-Token": str(cred.get("api_token"))})
    estado = r.status_code
    r.close()

    if estado == 200:
        print("[4/4] Esta PC ya conoce a la placa: OK")
        print("\n=== LISTO. Apreta Ctrl+D: la placa manda mediciones cada 30 s ===")
        raise SystemExit

    if estado not in (401, 403, 404):
        fin("La web contesto algo raro al preguntar por la placa (HTTP %s)." % estado,
            "Fijate que MySQL este en verde en XAMPP y volve a ejecutar esto.")

    # Otra PC = otra base de datos: no conoce las credenciales viejas.
    os.remove(config.ARCHIVO_CREDENCIALES)
    print("[4/4] Esta PC no conocia a la placa: borre credenciales.json para vincularla de nuevo.")
else:
    print("[4/4] La placa todavia no esta vinculada en esta PC.")

print("\n=== ULTIMO PASO ===")
print("  1. En la web (en la PC): Mis dispositivos -> Conectar. Deja esa pantalla abierta.")
print("  2. Aca en Thonny apreta Ctrl+D.")
print("  3. Tiene que aparecer 'Vinculado. UID: EDN-...' y despues las mediciones.")
