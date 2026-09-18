"""
config.py — Lo unico que se toca al programar una placa nueva.

Todo lo que cambia entre un equipo y otro, o entre tu casa y la escuela,
vive aca. El resto de los modulos no tiene ningun valor escrito adentro.

IMPORTANTE: los umbrales de temperatura, humedad, CO2 y calidad de aire NO
estan aca. Esos los manda el servidor (podes cambiarlos desde /panel/ambientes
sin reprogramar la placa). Aca solo esta lo que el equipo no puede averiguar
solo: donde queda el servidor, a que pin esta conectada cada cosa y los
tiempos que protegen al hardware.
"""

# ---------------------------------------------------------------------------
# SERVIDOR
# ---------------------------------------------------------------------------
# Direccion de la web EdenAir. Sin barra al final.
#
# ESTE ES SOLO EL VALOR DE FABRICA. El equipo prefiere el que se haya
# configurado desde el portal del celular (archivo servidor.json), asi que
# mudar el equipo a otra red NO obliga a abrir Thonny: se cambia desde el
# telefono, en "Opciones avanzadas" del portal.
#
# OJO: no sirve "localhost" ni "127.0.0.1". Para la ESP32, localhost es ella
# misma. Tenes que poner la IP de la computadora que corre XAMPP dentro de
# tu red WiFi (en Windows se averigua con `ipconfig`, campo IPv4).
SERVIDOR_DEFECTO = "http://192.168.2.130/piedra_castillo/public"


def servidor():
    """
    Direccion del servidor que hay que usar ahora.

    Primero la que configuro el usuario desde el portal; si no hay ninguna,
    la de fabrica. Se lee en cada llamada (son pocas y el archivo es diminuto)
    para que un cambio desde el portal tome efecto sin reiniciar.
    """
    try:
        import json
        with open(ARCHIVO_SERVIDOR) as f:
            guardada = str(json.load(f).get("url", "")).strip().rstrip("/")
            if guardada:
                return guardada
    except (OSError, ValueError, AttributeError):
        pass

    return SERVIDOR_DEFECTO

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
# MAPA DE PINES
# ---------------------------------------------------------------------------
# CUALQUIERA DE ESTOS PUEDE QUEDAR EN None. None significa "todavia no lo
# arme": el equipo no toca ese pin y no le informa al servidor nada sobre ese
# componente. Se puede arrancar solo con el SCD41 y sumar el resto de a poco.
#
# Lo unico que NO puede faltar es el SCD41: sin sensor el equipo no arranca.

# --- SCD41 (temperatura, humedad y CO2), por I2C, alimentado con 3V3 -------
PIN_I2C_SDA = 21
PIN_I2C_SCL = 22

# --- MQ-135 (calidad de aire), salida analogica por ADC --------------------
# El modulo se alimenta con 5 V porque tiene calefactor interno, pero su AO
# NUNCA va directo a un GPIO: entra por un divisor resistivo de 20 kohm entre
# AO y el nodo, y 10 kohm del nodo a GND. El GPIO 34 es solo-entrada.
PIN_MQ135 = 34

# --- Reles (activos en BAJO: 0 = encendido) --------------------------------
# IN1: cooler de 30 mm. En la maqueta gira como indicador de que la orden
#      llego; en una instalacion real, del otro lado hay un aire acondicionado
#      que si enfria. La logica del firmware es exactamente la misma.
# IN2: atomizador ultrasonico (humidificador; opcionalmente con esencia).
PIN_RELE_AIRE = None        # 26 cuando este conectado
PIN_RELE_ATOMIZADOR = None  # 27 cuando este conectado

# --- Cadena infrarroja -----------------------------------------------------
# El emisor (KY-005) manda la trama de 38 kHz por transistor; el receptor
# (VS1838B, alimentado con 3V3) la confirma. Recien con la orden emitida se
# energiza el rele del aire: es la misma cadena que usa un control remoto.
PIN_IR_EMISOR = None    # 25 cuando este conectado
PIN_IR_RECEPTOR = None  # 33 cuando este conectado

# --- LEDs de estado (con resistencia de 220-330 ohm) -----------------------
# Verde: todo normal, monitoreo pasivo.
# Rojo:  alerta (CO2 alto o calidad de aire mala).
# Azul:  orden de aire acondicionado activa.
PIN_LED_VERDE = None  # 14 cuando este conectado
PIN_LED_ROJO = None   # 16 cuando este conectado
PIN_LED_AZUL = None   # 17 cuando este conectado

# Si tus reles se activan con 0 en vez de 1 (los mas comunes son asi, y los de
# esta maqueta tambien), dejalo en True. Si el actuador funciona al reves de lo
# que esperas, es esto. NO aplica a los LEDs, que siempre encienden con 1.
RELES_INVERTIDOS = True

# ---------------------------------------------------------------------------
# CALIBRACION DEL MQ-135
# ---------------------------------------------------------------------------
# El MQ-135 no entrega ppm confiables sin una calibracion de laboratorio, asi
# que EdenAir NUNCA lo muestra en ppm: lo convierte a un indice relativo de
# 0 a 100 (mas alto = mejor aire), compatible con el air_quality_index que ya
# usan la base de datos y el panel.
#
# Estos dos numeros son los extremos de esa conversion, en cuentas crudas del
# ADC (0-4095) despues del divisor. Se ajustan una vez por placa:
#   - dejar el equipo en aire limpio 10 min -> ese valor va en CRUDO_LIMPIO
#   - acercar un marcador o alcohol         -> ese valor va en CRUDO_SUCIO
MQ135_CRUDO_LIMPIO = 700    # aire limpio  -> indice 100
MQ135_CRUDO_SUCIO = 2800    # aire viciado -> indice 0

# Cuantas lecturas del ADC se promedian por medicion, para filtrar ruido.
MQ135_MUESTRAS = 16

# ---------------------------------------------------------------------------
# TIEMPOS POR DEFECTO (segundos)
# ---------------------------------------------------------------------------
# El servidor manda los suyos en la configuracion; estos se usan mientras
# todavia no la pudo descargar.

# Cada cuanto medir y decidir. El que manda es el del servidor
# (DeviceConfigService::INTERVALO_MEDICION); este vale hasta bajarlo. 30 s
# todavia deja ver la reaccion en vivo sin llenar la base: 2.880 filas por dia
# en vez de 10.800. No bajar de 5: el SCD41 no da un dato nuevo antes.
INTERVALO_MEDICION = 30

# Cada cuanto preguntar por ordenes manuales del usuario y refrescar umbrales.
INTERVALO_COMANDOS = 15
INTERVALO_CONFIG = 3600

# Cada cuanto da una vuelta el ciclo principal. Es corto para que cada tarea
# arranque cerca de su horario; no es el ritmo de ninguna de ellas.
INTERVALO_CICLO = 1

# Cuanto esperar antes de reintentar cuando el servidor no contesta.
REINTENTO_RED = 15

# ---------------------------------------------------------------------------
# PROTECCIONES DEL HARDWARE (segundos, salvo donde diga ms)
# ---------------------------------------------------------------------------
# Estos numeros protegen componentes fisicos, asi que viven en el firmware y
# no en la web: no tiene sentido que se puedan romper desde el navegador. El
# servidor igual puede pisarlos mandando otros en el bloque "tiempos".

# Tiempo minimo que un rele tiene que quedarse en un estado antes de volver a
# conmutar. Evita el traqueteo cuando el valor queda justo sobre el umbral.
RELE_MINIMO_ESTADO = 30

# La trama IR no se emite mas de una vez cada tanto...
IR_MINIMO_ENTRE_TRAMAS = 60
# ...y despues de emitirla se espera como mucho esto (en MILISEGUNDOS) a que
# el receptor la confirme. Si no confirma, el rele se activa igual y el
# servidor se entera de que la confirmacion no llego.
IR_ESPERA_CONFIRMACION_MS = 1000

# El MQ-135 tiene calefactor: recien despues de este rato su lectura sirve.
MQ135_WARMUP = 300

# Mientras el atomizador esta encendido, y este rato despues de apagarlo, se
# ignora al MQ-135: la niebla le hace marcar "aire malo" cuando en realidad
# es vapor de agua.
MQ135_ENMASCARADO = 90

# Ciclo del atomizador mientras la humedad siga por debajo del minimo.
# Evita encharcar el ambiente y vaciar el deposito en minutos.
ATOMIZADOR_CICLO_ON = 60
ATOMIZADOR_CICLO_OFF = 120

# ---------------------------------------------------------------------------
# ARCHIVOS DONDE EL EQUIPO GUARDA LO SUYO
# ---------------------------------------------------------------------------
# Se escriben solos, no hay que tocarlos. Si borras credenciales.json el
# equipo vuelve a arrancar como si fuera nuevo.
ARCHIVO_WIFI = "wifi.json"
ARCHIVO_CREDENCIALES = "credenciales.json"

# Lo escribe el portal si el usuario cambia la direccion del servidor desde
# "Opciones avanzadas". Si no existe, se usa SERVIDOR_DEFECTO.
ARCHIVO_SERVIDOR = "servidor.json"

# Codigo de un solo uso que el portal le entrega al celular para que pueda
# seguir la vinculacion desde la web. Se escribe al configurar el WiFi y se
# borra apenas el equipo queda vinculado. Sobrevive a un reinicio a proposito:
# si la placa se reinicia entre el portal y el alta, el celular igual encuentra
# su equipo en vez de quedarse esperando para siempre.
ARCHIVO_SESION = "sesion.json"
