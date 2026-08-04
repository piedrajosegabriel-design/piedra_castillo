"""
config.py — Lo unico que se toca al programar una placa nueva.

Todo lo que cambia entre un equipo y otro, o entre tu casa y la escuela,
vive aca. El resto de los modulos no tiene ningun valor escrito adentro.

IMPORTANTE: los umbrales de temperatura, humedad y CO2 NO estan aca.
Esos los manda el servidor (podes cambiarlos desde /panel/ambientes sin
reprogramar la placa). Aca solo esta lo que el equipo no puede averiguar
solo: donde queda el servidor y a que pin esta conectada cada cosa.
"""

# ---------------------------------------------------------------------------
# SERVIDOR
# ---------------------------------------------------------------------------
# Direccion de la web EdenAir. Sin barra al final.
#
# OJO: no sirve "localhost" ni "127.0.0.1". Para la ESP32, localhost es ella
# misma. Tenes que poner la IP de la computadora que corre XAMPP dentro de
# tu red WiFi (en Windows se averigua con `ipconfig`, campo IPv4).
SERVIDOR = "http://192.168.1.194/piedra_castillo/public"

# ---------------------------------------------------------------------------
# PUNTO DE ACCESO DE CONFIGURACION
# ---------------------------------------------------------------------------
# La red que crea el equipo cuando todavia no tiene WiFi.
#
# TIENEN QUE COINCIDIR EXACTAMENTE con lo que la web mete adentro del QR
# (DevicePairingService::AP_SSID_DEFECTO). Si cambias uno, cambia el otro:
# si no, el celular escanea el QR y busca una red que no existe.
AP_SSID = "EdenAir-Setup"
AP_PASSWORD = "edenair.setup"

# ---------------------------------------------------------------------------
# PINES DEL SENSOR
# ---------------------------------------------------------------------------
# SCD41 por I2C (temperatura, humedad y CO2).
PIN_I2C_SDA = 21
PIN_I2C_SCL = 22

# ---------------------------------------------------------------------------
# QUE ACTUADORES TENES CONECTADOS DE VERDAD
# ---------------------------------------------------------------------------
# Poné en None los que todavia NO armaste. El equipo no va a tocar ese pin
# ni le va a decir al servidor que ese actuador esta encendido.
#
# ARRANCAR SOLO CON EL SENSOR ES VALIDO: dejá los tres en None y el equipo
# mide y reporta igual. El panel va a mostrar las mediciones reales y los
# actuadores en gris, sin inventar nada.
#
# Cuando conectes uno, le pones su numero de pin y volves a subir este archivo.
PIN_VENTILADOR = None      # ej: 25
PIN_AROMATIZADOR = None    # ej: 26
PIN_LED_ALERTA = None      # ej: 27

# Si tus reles se activan con 0 en vez de 1 (los mas comunes son asi),
# poné True. Si el actuador funciona al reves de lo que esperas, es esto.
RELES_INVERTIDOS = True

# ---------------------------------------------------------------------------
# TIEMPOS POR DEFECTO (segundos)
# ---------------------------------------------------------------------------
# El servidor manda los suyos en la configuracion; estos se usan mientras
# todavia no la pudo descargar.
INTERVALO_MEDICION = 300
INTERVALO_COMANDOS = 15
INTERVALO_CONFIG = 3600

# Cuanto esperar antes de reintentar cuando el servidor no contesta.
REINTENTO_RED = 15

# ---------------------------------------------------------------------------
# ARCHIVOS DONDE EL EQUIPO GUARDA LO SUYO
# ---------------------------------------------------------------------------
# Se escriben solos, no hay que tocarlos. Si borras credenciales.json el
# equipo vuelve a arrancar como si fuera nuevo.
ARCHIVO_WIFI = "wifi.json"
ARCHIVO_CREDENCIALES = "credenciales.json"
