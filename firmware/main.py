"""
main.py — El programa principal del Eden Air.

MicroPython ejecuta este archivo solo, cada vez que la placa arranca.

QUE HACE ESTE ARCHIVO: orquestar. No mide, no decide, no habla HTTP: le pide
cada cosa al modulo que corresponde. Si algo no funciona, el problema esta en
ese modulo, no aca.

  red.py         -> conectarse al WiFi (y pedirlo por el portal si no lo tiene)
  servidor.py    -> hablar con la API de EdenAir
  sensor.py      -> leer el SCD41 (temperatura, humedad, CO2)
  aire.py        -> leer el MQ-135 (calidad de aire)
  reglas.py      -> DECIDIR que actuadores prender
  infrarrojo.py  -> emitir la orden del aire acondicionado
  actuadores.py  -> mover los reles y los LEDs
  config.py      -> los valores propios de esta placa

EL CICLO
  1. Conectarse al WiFi.
  2. Pedir credenciales al servidor (una sola vez en la vida del equipo).
  3. Bajar la configuracion: con que umbrales decidir.
  4. Para siempre:
       - medir (SCD41 + MQ-135)
       - decidir localmente y accionar
       - reportar la medicion, lo que hizo y como esta cada sensor
       - revisar si el usuario mando alguna orden manual
       - cada tanto, refrescar la configuracion

Lo importante: el paso "decidir y accionar" NO depende del servidor. Si se
corta internet, el equipo sigue regulando el ambiente igual; lo unico que
deja de hacer es reportar.
"""

import json
import time

import aire
import config
import red
import reglas
from actuadores import Actuadores
from infrarrojo import Infrarrojo
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
        print("Config: %s | aire ON >%.1f C / OFF <%.1f C | hum ON <%.0f %% / OFF >%.0f %% | CO2 aviso %s ppm | aire min %s | modo %s" % (
            nueva["ambiente"]["nombre"],
            nueva["umbrales"]["temp_max"], nueva["apagado"]["temp"],
            nueva["umbrales"]["hum_min"], nueva["apagado"]["hum"],
            nueva["umbrales"]["co2_max"],
            nueva["umbrales"]["aire_min"],
            nueva["modo"],
        ))
        return nueva
    except ErrorServidor as e:
        if anterior:
            print("No se pudo refrescar la config (%s). Sigo con la anterior." % e)
            return anterior
        raise


def sincronizar_tiempos(cfg, salidas, medidor, cadena):
    """
    Aplica los tiempos que mando el servidor a los objetos que ya estaban
    creados.

    Los objetos se arman antes de tener configuracion (el MQ-135 tiene que
    empezar a calentar apenas se enchufa la placa, no cuando aparece internet),
    asi que los valores de config.py son el arranque y estos son el ajuste.
    """
    tiempos = cfg.get("tiempos", {})

    salidas.minimo_estado = tiempos.get("rele_minimo", salidas.minimo_estado)
    medidor.warmup = tiempos.get("mq135_warmup", medidor.warmup)
    medidor.enmascarado = tiempos.get("mq135_enmascarado", medidor.enmascarado)
    cadena.minimo_entre_tramas = tiempos.get("ir_minimo", cadena.minimo_entre_tramas)
    cadena.espera_ms = tiempos.get("ir_espera_ms", cadena.espera_ms)


# ---------------------------------------------------------------------------
# Medicion
# ---------------------------------------------------------------------------
def medir(sensor, medidor, salidas, cfg, ahora):
    """
    Junta en un solo diccionario todo lo que se midio en este ciclo.

    Devuelve (medicion, estado_aire). Lanza ErrorSensor si el SCD41 no
    contesta: sin el no hay medicion posible.

    DE DONDE SALE LA CALIDAD DE AIRE
      - del MQ-135, si esta conectado y ya termino de calentar  -> "sensor"
      - de la formula de respaldo de reglas.py, si no           -> "calculado"

    Mientras el atomizador esta echando niebla (y un rato despues) el MQ-135
    queda enmascarado: devuelve congelado su ultimo valor bueno y avisa que
    esta en pausa. Es vapor de agua, no contaminacion, y sin esto el equipo
    diria "aire malo" justo cuando esta humidificando.
    """
    co2, temperatura, humedad = sensor.leer()

    indice, estado_aire = medidor.leer(ahora, atomizando=salidas.encendido("aromatizer"))

    if indice is None:
        indice = reglas.calcular_indice_aire(temperatura, humedad, co2, cfg["umbrales"])
        origen = "calculado"
    else:
        origen = "sensor"

    return {
        "temperature": temperatura,
        "humidity": humedad,
        "co2_ppm": co2,
        "air_quality_index": indice,
        "air_quality_source": origen,
        # Solo una lectura fresca del MQ-135 puede levantar o bajar una alerta
        # de calidad de aire. Congelada o calculada, no.
        "aire_confiable": estado_aire == aire.OK,
    }, estado_aire


def avisos_de_los_sensores(estado_aire, salidas, avisos):
    """
    Suma a los avisos de reglas.py los que no son decisiones sino diagnostico
    del hardware. Son los que le explican al usuario por que el panel esta
    mostrando lo que muestra.
    """
    if estado_aire == aire.WARMUP:
        avisos.append(reglas.AVISO_AIRE_CALENTANDO)
    elif estado_aire == aire.PAUSA:
        avisos.append(reglas.AVISO_AIRE_EN_PAUSA)

    # La orden salio pero el receptor no la confirmo. El aire se encendio
    # igual: esto no bloquea nada, es un dato de diagnostico.
    if salidas.encendido("fan") and salidas.ir_confirmado is False:
        avisos.append(reglas.AVISO_IR_SIN_CONFIRMAR)

    return avisos


# ---------------------------------------------------------------------------
# Ordenes manuales del usuario
# ---------------------------------------------------------------------------
def atender_comandos(api, salidas):
    """
    Aplica las ordenes que el usuario mando desde el dashboard y las confirma.

    Solo tienen efecto en modo manual; en automatico manda reglas.py.

    Una orden se confirma UNICAMENTE si se pudo cumplir de verdad. Si pide un
    actuador que no esta conectado, o si el rele todavia esta cumpliendo su
    tiempo minimo, se deja pendiente: confirmarla haria que el panel mostrara
    encendido algo que no lo esta. El servidor no acumula mas de una pendiente
    por actuador, asi que no se llena la cola.
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

        if salidas.estado.get(tipo) != valor:
            print("Orden demorada:", tipo, "espera su tiempo minimo de rele.")
            continue

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

    # La cadena infrarroja y los actuadores se arman juntos: la orden del aire
    # acondicionado sale por infrarrojo y recien despues mueve el rele.
    cadena = Infrarrojo(config.PIN_IR_EMISOR, config.PIN_IR_RECEPTOR)
    salidas = Actuadores(cadena)
    salidas.apagar_todo()

    # El MQ-135 empieza a calentar AHORA, no cuando aparezca internet: son
    # cinco minutos y no tiene sentido desperdiciarlos esperando al WiFi.
    medidor = aire.MedidorAire(config.PIN_MQ135)

    if salidas.hay_alguno():
        print("Actuadores conectados:", ", ".join(salidas.conectados()))
    else:
        print("Sin actuadores conectados: el equipo solo mide y reporta.")
        print("Cuando armes uno, ponele su pin en config.py.")

    if medidor.presente():
        print("MQ-135 conectado. Calentando %s s antes de creerle." % medidor.warmup)
    else:
        print("Sin MQ-135: la calidad de aire se calcula con la formula de respaldo.")

    if not cadena.hay_emisor():
        print("Sin emisor infrarrojo: el rele del aire se activa directo.")
    elif not cadena.hay_receptor():
        print("Sin receptor infrarrojo: se emite la trama pero nadie la confirma.")

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

    sincronizar_tiempos(cfg, salidas, medidor, cadena)

    decisor = reglas.Decisor()

    ultima_config = time.time()
    ultima_medicion = 0
    ultimos_comandos = 0

    # 4) Ciclo principal.
    #
    # Cada tarea tiene su propio reloj y la vuelta es corta. Antes la vuelta
    # duraba lo que el intervalo de comandos, asi que ese numero terminaba
    # marcando tambien el ritmo de las mediciones: pedir mediciones cada 8 s
    # no servia de nada si el ciclo dormia 15.
    while True:
        try:
            ahora = time.time()
            intervalos = cfg.get("intervalos", {})

            # ---- Medir y decidir ----
            if ahora - ultima_medicion >= intervalos.get("medicion", config.INTERVALO_MEDICION):
                ultima_medicion = ahora

                try:
                    medicion, estado_aire = medir(sensor, medidor, salidas, cfg, ahora)
                except ErrorSensor as e:
                    print("Lectura fallida:", e)
                    time.sleep(5)
                    continue

                print("%.1f C  %.1f %%  %s ppm  aire %s/100 %s (%s)" % (
                    medicion["temperature"], medicion["humidity"], medicion["co2_ppm"],
                    medicion["air_quality_index"],
                    reglas.etiqueta_aire(medicion["air_quality_index"]),
                    medicion["air_quality_source"] if estado_aire == aire.OK else estado_aire,
                ))

                # ESTA ES LA DECISION, Y ES LOCAL.
                # Tres casos:
                #   - sin actuadores armados -> no hay nada que decidir
                #   - modo manual            -> manda el usuario, el equipo no decide
                #   - modo automatico        -> el equipo decide y acciona
                avisos = []

                if not salidas.hay_alguno():
                    motivo = "Solo sensor: sin actuadores conectados."
                elif cfg.get("modo") == "automatic":
                    deseado, avisos, motivo = decisor.decidir(medicion, cfg, ahora)
                    cambios = salidas.aplicar(deseado, ahora)
                    if cambios:
                        print("  -> cambia:", ", ".join(cambios), "|", motivo)
                    if salidas.demorados:
                        print("  -> espera tiempo minimo de rele:", ", ".join(salidas.demorados))
                else:
                    motivo = "Modo manual: manda el usuario."

                avisos = avisos_de_los_sensores(estado_aire, salidas, avisos)

                # Recien ahora se avisa al servidor. Si falla, no importa:
                # el ambiente ya quedo regulado.
                try:
                    api.enviar_medicion(medicion, salidas.como_dict(), motivo, {
                        "estado_aire": estado_aire,
                        "ir_confirmado": salidas.ir_confirmado,
                        "avisos": avisos,
                    })
                except ErrorServidor as e:
                    print("  (no se pudo reportar: %s)" % e)

            # ---- Ordenes manuales ----
            if ahora - ultimos_comandos >= intervalos.get("comandos", config.INTERVALO_COMANDOS):
                ultimos_comandos = ahora
                atender_comandos(api, salidas)

            # ---- Refrescar configuracion ----
            if ahora - ultima_config >= intervalos.get("config", config.INTERVALO_CONFIG):
                ultima_config = ahora
                cfg = bajar_config(api, cfg)
                sincronizar_tiempos(cfg, salidas, medidor, cadena)

            # ---- Reconectar si se cayo el WiFi ----
            if not red.hay_internet():
                print("WiFi caido. Reconectando...")
                red.asegurar_conexion()

            time.sleep(config.INTERVALO_CICLO)

        except Exception as e:
            # Nada puede tumbar el ciclo: si el equipo se apaga, el ambiente
            # queda sin regular.
            print("Error inesperado:", e)
            time.sleep(config.REINTENTO_RED)


main()
