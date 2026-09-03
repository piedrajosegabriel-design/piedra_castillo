"""
main.py — El programa principal del Eden Air.

MicroPython ejecuta este archivo solo, cada vez que la placa arranca.

QUE HACE ESTE ARCHIVO: orquestar. No mide, no decide, no habla HTTP: le pide
cada cosa al modulo que corresponde. Si algo no funciona, el problema esta en
ese modulo, no aca.

  red.py        -> conectarse al WiFi (y pedirlo por el portal si no lo tiene)
  servidor.py   -> hablar con la API de EdenAir
  sensor.py     -> leer el SCD41
  reglas.py     -> DECIDIR que actuadores prender
  actuadores.py -> mover los reles
  config.py     -> los valores propios de esta placa

EL CICLO
  1. Conectarse al WiFi.
  2. Pedir credenciales al servidor (una sola vez en la vida del equipo).
  3. Bajar la configuracion: con que umbrales decidir.
  4. Para siempre:
       - medir
       - decidir localmente y accionar
       - reportar la medicion y lo que hizo
       - revisar si el usuario mando alguna orden manual
       - cada tanto, refrescar la configuracion

Lo importante: el paso "decidir y accionar" NO depende del servidor. Si se
corta internet, el equipo sigue regulando el ambiente igual; lo unico que
deja de hacer es reportar.
"""

import json
import time

import config
import red
import reglas
from actuadores import Actuadores
from sensor import SCD41, ErrorSensor
from servidor import ErrorServidor, Servidor, SinVentana


# ---------------------------------------------------------------------------
# Credenciales del equipo (device_uid + api_token)
# ---------------------------------------------------------------------------
def leer_credenciales():
    try:
        with open(config.ARCHIVO_CREDENCIALES) as f:
            datos = json.load(f)
            return datos.get("device_uid"), datos.get("api_token")
    except (OSError, ValueError):
        return None, None


def guardar_credenciales(device_uid, api_token):
    with open(config.ARCHIVO_CREDENCIALES, "w") as f:
        json.dump({"device_uid": device_uid, "api_token": api_token}, f)


# ---------------------------------------------------------------------------
# Alta del equipo
# ---------------------------------------------------------------------------
def avisar_reconexion(api, sesion):
    """
    Le dice al servidor que este equipo —que ya estaba vinculado— volvio a la
    red con un codigo de seguimiento nuevo.

    NO es critico: si falla, el equipo funciona igual y lo unico que pasa es
    que la pantalla del celular no se entera. Por eso son dos intentos y
    seguimos, en vez del reintento infinito del alta normal.
    """
    for intento in range(2):
        try:
            api.vincular(red.mac(), sesion=sesion)
            print("Aviso de reconexion enviado.")
            break
        except (ErrorServidor, SinVentana) as e:
            print("No se pudo avisar la reconexion (%s)." % e)
            if intento == 0:
                time.sleep(3)

    # Se descarta igual: sirvio o no sirvio, pero ya no vale para otra vez.
    red.olvidar_sesion()


def obtener_credenciales(api):
    """
    Consigue device_uid y api_token.

    Si ya los tiene guardados, los usa. Si no, se presenta al servidor con su
    MAC y espera a que alguien apriete "Conectar" en la web. Reintenta para
    siempre: el equipo puede estar enchufado antes de que el dueño entre.

    Junto con la MAC viaja el codigo de sesion que el portal le entrego al
    celular. Es lo que hace que el telefono, al tocar "Ver mi Eden Air", vea
    su equipo ya conectado sin tener que iniciar sesion.
    """
    device_uid, api_token = leer_credenciales()
    sesion = red.leer_sesion()

    if device_uid and api_token:
        api.device_uid = device_uid
        api.api_token = api_token
        print("Credenciales ya guardadas. UID:", device_uid)

        # Equipo que ya era de alguien y acaba de reconfigurar su WiFi (se
        # mudo, cambio de router). No necesita credenciales nuevas, pero hay
        # un celular esperando en la pantalla de seguimiento: avisamos.
        if sesion:
            avisar_reconexion(api, sesion)

        return

    mi_mac = red.mac()

    print("Equipo sin vincular. MAC:", mi_mac)
    print("Entra a EdenAir y apreta 'Conectar'.")

    while True:
        try:
            device_uid, api_token = api.vincular(mi_mac, sesion=sesion)
            guardar_credenciales(device_uid, api_token)

            # El codigo ya cumplio: el celular pudo ver el equipo. Dejarlo
            # guardado no aporta nada y lo reusariamos por error en la
            # proxima reconfiguracion de red.
            red.olvidar_sesion()

            print("Vinculado. UID:", device_uid)
            return
        except SinVentana:
            print("Todavia nadie apreto 'Conectar'. Reintento en 15 s.")
            time.sleep(15)
        except ErrorServidor as e:
            print("Error al vincular:", e)
            time.sleep(config.REINTENTO_RED)


# ---------------------------------------------------------------------------
# Configuracion
# ---------------------------------------------------------------------------
def bajar_config(api, anterior=None):
    """
    Trae los umbrales del servidor. Si falla, sigue con los que ya tenia:
    quedarse sin configuracion no puede dejar al equipo sin regular.
    """
    try:
        nueva = api.config()
        print("Config: %s | %.1f-%.1f C | %.0f-%.0f %% | max %s ppm | modo %s" % (
            nueva["ambiente"]["nombre"],
            nueva["umbrales"]["temp_min"], nueva["umbrales"]["temp_max"],
            nueva["umbrales"]["hum_min"], nueva["umbrales"]["hum_max"],
            nueva["umbrales"]["co2_max"],
            nueva["modo"],
        ))
        return nueva
    except ErrorServidor as e:
        if anterior:
            print("No se pudo refrescar la config (%s). Sigo con la anterior." % e)
            return anterior
        raise


# ---------------------------------------------------------------------------
# Ordenes manuales del usuario
# ---------------------------------------------------------------------------
def atender_comandos(api, salidas):
    """
    Aplica las ordenes que el usuario mando desde el dashboard y las confirma.

    Solo tienen efecto en modo manual; en automatico manda reglas.py.

    Una orden se confirma UNICAMENTE si se pudo cumplir de verdad. Si pide un
    actuador que no esta conectado, se deja pendiente: confirmarla haria que el
    panel mostrara encendido algo que no existe. El servidor no acumula mas de
    una pendiente por actuador, asi que no se llena la cola.
    """
    try:
        pendientes = api.comandos_pendientes()
    except ErrorServidor:
        return

    for comando in pendientes:
        tipo = comando.get("command_type")
        valor = comando.get("target_value")

        if tipo not in ("fan", "aromatizer", "alert_led"):
            continue

        if tipo not in salidas.conectados():
            print("Orden ignorada: no hay", tipo, "conectado en esta placa.")
            continue

        salidas.aplicar({tipo: valor})
        print("Orden del usuario:", tipo, "->", valor)

        try:
            api.confirmar_comando(comando["id"])
        except ErrorServidor:
            pass


# ---------------------------------------------------------------------------
# Programa principal
# ---------------------------------------------------------------------------
def main():
    print("\n=== Eden Air ===")

    salidas = Actuadores()
    salidas.apagar_todo()

    if salidas.hay_alguno():
        print("Actuadores conectados:", ", ".join(salidas.conectados()))
    else:
        print("Sin actuadores conectados: el equipo solo mide y reporta.")
        print("Cuando armes uno, ponele su pin en config.py.")

    # 1) WiFi. No devuelve hasta estar conectado (abre el portal si hace falta).
    red.asegurar_conexion()

    # 2) Sensor.
    try:
        sensor = SCD41()
        print("Sensor SCD41 listo.")
    except ErrorSensor as e:
        print("ERROR DE SENSOR:", e)
        print("El equipo no puede funcionar sin sensor. Revisa el cableado.")
        return

    # 3) Credenciales y configuracion.
    api = Servidor()
    obtener_credenciales(api)

    cfg = None
    while cfg is None:
        try:
            cfg = bajar_config(api)
        except ErrorServidor as e:
            print("Sin configuracion todavia (%s). Reintento." % e)
            time.sleep(config.REINTENTO_RED)

    ultima_config = time.time()
    ultima_medicion = 0

    # 4) Ciclo principal.
    while True:
        try:
            ahora = time.time()
            intervalos = cfg.get("intervalos", {})

            # ---- Medir y decidir ----
            if ahora - ultima_medicion >= intervalos.get("medicion", config.INTERVALO_MEDICION):
                ultima_medicion = ahora

                try:
                    co2, temperatura, humedad = sensor.leer()
                except ErrorSensor as e:
                    print("Lectura fallida:", e)
                    time.sleep(5)
                    continue

                medicion = {
                    "temperature": temperatura,
                    "humidity": humedad,
                    "co2_ppm": co2,
                }
                medicion["air_quality_index"] = reglas.calcular_indice_aire(
                    temperatura, humedad, co2, cfg["umbrales"]
                )

                print("%.1f C  %.1f %%  %s ppm  aire %s (%s)" % (
                    temperatura, humedad, co2,
                    medicion["air_quality_index"],
                    reglas.etiqueta_aire(medicion["air_quality_index"]),
                ))

                # ESTA ES LA DECISION, Y ES LOCAL.
                # Tres casos:
                #   - sin actuadores armados -> no hay nada que decidir
                #   - modo manual            -> manda el usuario, el equipo no decide
                #   - modo automatico        -> el equipo decide y acciona
                if not salidas.hay_alguno():
                    motivo = "Solo sensor: sin actuadores conectados."
                elif cfg.get("modo") == "automatic":
                    deseado, motivo = reglas.decidir(medicion, cfg)
                    cambios = salidas.aplicar(deseado)
                    if cambios:
                        print("  -> cambia:", ", ".join(cambios), "|", motivo)
                else:
                    motivo = "Modo manual: manda el usuario."

                # Recien ahora se avisa al servidor. Si falla, no importa:
                # el ambiente ya quedo regulado.
                try:
                    api.enviar_medicion(medicion, salidas.como_dict(), motivo)
                except ErrorServidor as e:
                    print("  (no se pudo reportar: %s)" % e)

            # ---- Ordenes manuales ----
            atender_comandos(api, salidas)

            # ---- Refrescar configuracion ----
            if ahora - ultima_config >= intervalos.get("config", config.INTERVALO_CONFIG):
                ultima_config = ahora
                cfg = bajar_config(api, cfg)

            # ---- Reconectar si se cayo el WiFi ----
            if not red.hay_internet():
                print("WiFi caido. Reconectando...")
                red.asegurar_conexion()

            time.sleep(intervalos.get("comandos", config.INTERVALO_COMANDOS))

        except Exception as e:
            # Nada puede tumbar el ciclo: si el equipo se apaga, el ambiente
            # queda sin regular.
            print("Error inesperado:", e)
            time.sleep(config.REINTENTO_RED)


main()
