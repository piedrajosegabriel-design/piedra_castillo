"""
reglas.py — LA LOGICA DE CONTROL DEL EQUIPO.

Este es el modulo mas importante del firmware y el corazon de la separacion
de responsabilidades: aca, y solo aca, se decide cuando prender cada actuador.

El servidor manda los NUMEROS (los umbrales del ambiente, que el usuario edita
desde /panel/ambientes) y el equipo aplica la REGLA. Cambiar un rango sigue
siendo cosa de la web; decidir es cosa del equipo. Por eso, si se corta
internet, el ambiente se sigue regulando igual.

Este modulo es PURO: no toca pines, no toca la red, no lee sensores, no mira
el reloj. Recibe numeros y devuelve una decision. Eso lo hace facil de probar
sin hardware y facil de explicar.

===========================================================================
LA REGLA DE ORO: EdenAir mide cuatro variables pero solo puede ACTUAR sobre
dos.
===========================================================================

  temperatura -> ACTUA: ordena el aire acondicionado (por infrarrojo)
  humedad     -> ACTUA: enciende el atomizador, y solo para SUBIRLA
  CO2         -> AVISA
  calidad     -> AVISA

CO2 y calidad de aire solo avisan porque el sistema no renueva el aire: no hay
extractor ni ventilacion. Encender algo no cambiaria el numero. Y no es una
limitacion de la maqueta, es una decision de producto: EdenAir no comanda
equipos que el usuario no tenga instalados. En esos casos decide la persona, y
el equipo se lo dice con el LED rojo y con un mensaje en el panel.

Del mismo modo, la humedad ALTA no enciende nada: el atomizador solo puede
agregar humedad, nunca sacarla. Se informa y listo.

===========================================================================
LA LOGICA SE ESCRIBE COMO EL PRODUCTO REAL, NO COMO LA MAQUETA
===========================================================================

El cooler de 30 mm de la maqueta representa un aire acondicionado: gira para
mostrar que la orden llego, pero no baja la temperatura. Eso NO cambia nada de
lo que hay aca. La histeresis de apagado existe porque en una casa el aire
enfria de verdad y hay que cortar antes de pasarse. En la maqueta, mientras la
temperatura siga alta el aire va a quedar encendido, y esta bien: es
exactamente lo que haria el sistema real. No hay ningun temporizador de
compensacion ni ningun modo "demo".

===========================================================================
POR QUE HAY MEMORIA (histeresis y ciclos)
===========================================================================

Una regla sin memoria oscila. Con "prender arriba de 26" a secas, a 26.1 grados
el aire arranca, a 25.9 se corta, y el rele traquetea sin parar. Por eso cada
regla tiene dos umbrales: uno para encender y otro, mas adentro, para apagar.
Entre los dos, se sostiene lo que ya estaba.

Y el atomizador ademas cicla (un rato encendido, un rato apagado) mientras la
humedad siga baja: si se dejara fijo, encharca el ambiente y vacia el deposito
en minutos.

Esa memoria es lo unico que obliga a que el decisor sea un objeto y no una
funcion suelta. Igual sigue siendo puro: se le pasa el momento actual como un
numero (`ahora`), no lo consulta.
"""

# ---------------------------------------------------------------------------
# Avisos: codigos que viajan al servidor. El texto que ve el usuario lo pone
# la web (PanelService), no el firmware: asi se puede reescribir un mensaje sin
# reprogramar la placa.
# ---------------------------------------------------------------------------
AVISO_AIRE_ACONDICIONADO = "aire_acondicionado"   # "Aire acondicionado activado"
AVISO_VENTILAR = "ventilar"                       # "Ventila el ambiente"
AVISO_CO2_CRITICO = "co2_critico"                 # CO2 peligrosamente alto
AVISO_HUMEDAD_ALTA = "humedad_alta"               # el equipo no puede bajarla
AVISO_HUMIDIFICANDO = "humidificando"             # atomizador en marcha
AVISO_IR_SIN_CONFIRMAR = "ir_sin_confirmar"       # lo agrega main.py
AVISO_AIRE_EN_PAUSA = "aire_en_pausa"             # lo agrega main.py
AVISO_AIRE_CALENTANDO = "aire_calentando"         # lo agrega main.py


def _redondear(valor):
    """
    Redondea como PHP, no como Python.

    Python (y MicroPython) usan "banker's rounding": round(22.5) da 22, porque
    desempatan hacia el par. PHP usa "half away from zero": da 23.

    Si no se corrige, el equipo y el servidor calculan indices distintos en los
    empates exactos y el panel muestra un numero que no es el que uso el equipo
    para decidir. Pasaba de verdad: humedad 70 % daba 78 aca y 77 en el
    servidor.

    int() trunca hacia cero, asi que sumar/restar 0.5 antes da el
    comportamiento de PHP.
    """
    return int(valor + 0.5) if valor >= 0 else int(valor - 0.5)


def calcular_indice_aire(temperatura, humedad, co2, umbrales):
    """
    Indice de calidad de aire de RESPALDO, de 0 a 100 (mas alto es mejor).

    ESTO NO ES UN SENSOR: es una cuenta. Arranca en 100 y descuenta puntos por
    cada desvio respecto del ambiente configurado.

    Desde que existe el MQ-135, el indice que vale es el MEDIDO (ver aire.py).
    Esta formula quedo como respaldo para dos casos concretos:

      - el MQ-135 no esta conectado (PIN_MQ135 = None)
      - esta calentando y todavia no se le puede creer

    Asi el equipo sigue mostrando algo razonable sin el sensor, igual que
    funciona sin actuadores. El servidor recibe junto al numero de donde
    salio ("sensor" o "calculado") y el panel lo aclara.

    Es la misma formula que usa el servidor en MeasurementService, para que el
    numero coincida en los dos lados.
    """
    puntaje = 100
    centro_temp = (umbrales["temp_min"] + umbrales["temp_max"]) / 2

    # Cuanto mas lejos del centro del rango, peor.
    puntaje -= _redondear(abs(temperatura - centro_temp) * 6)

    if humedad > umbrales["hum_max"]:
        puntaje -= _redondear((humedad - umbrales["hum_max"]) * 1.5)

    if humedad < umbrales["hum_min"]:
        puntaje -= _redondear((umbrales["hum_min"] - humedad) * 1.3)

    if co2 > umbrales["co2_max"]:
        puntaje -= _redondear((co2 - umbrales["co2_max"]) / 12)

    # Encerrar entre 0 y 100.
    return max(0, min(100, int(puntaje)))


def etiqueta_aire(indice):
    """Traduce el indice a texto, igual que el servidor."""
    if indice is None:
        return "Sin dato"
    if indice >= 85:
        return "Excelente"
    if indice >= 70:
        return "Buena"
    if indice >= 55:
        return "Aceptable"
    return "Mala"


class Decisor:
    """
    Guarda lo minimo que hace falta recordar entre una medicion y la siguiente:
    en que estado quedo cada regla y en que momento del ciclo esta el
    atomizador. Nada mas.
    """

    def __init__(self):
        # Reglas que ACTUAN.
        self.aire_acondicionado = False
        self.atomizador_pedido = False      # la humedad sigue baja
        self.atomizador_fase_on = False     # en que mitad del ciclo esta
        self.atomizador_desde = 0           # cuando empezo esa mitad

        # Reglas que solo AVISAN.
        self.alerta_co2 = False
        self.alerta_aire = False
        self.co2_critico = False

    # -----------------------------------------------------------------------
    # Decision
    # -----------------------------------------------------------------------
    def decidir(self, medicion, config, ahora):
        """
        Dada una medicion y la configuracion del servidor, decide que tiene que
        quedar encendido.

        medicion -> {"temperature", "humidity", "co2_ppm",
                     "air_quality_index" (puede ser None),
                     "aire_confiable" (bool)}
        config   -> lo que bajo del servidor (umbrales, apagado, critico, ...)
        ahora    -> segundos, para los ciclos y nada mas

        Devuelve (actuadores, avisos, motivo):
          actuadores -> {"fan", "aromatizer", "alert_led", "green_led", "blue_led"}
          avisos     -> lista de codigos para que la web arme sus mensajes
          motivo     -> texto legible que queda en el historial del panel

        EL ORDEN IMPORTA: primero lo que hay que avisar (calidad de aire y
        CO2), despues temperatura, y por ultimo humedad. Es el orden de
        prioridad del sistema y tambien el orden en que se lee el motivo.
        """
        umbrales = config["umbrales"]
        apagado = config["apagado"]
        critico = config.get("critico", {})
        tiempos = config.get("tiempos", {})

        temp = medicion["temperature"]
        hum = medicion["humidity"]
        co2 = medicion["co2_ppm"]
        aire = medicion.get("air_quality_index")
        aire_confiable = medicion.get("aire_confiable", True)

        motivos = []
        avisos = []

        # -------------------------------------------------------------------
        # 1) CALIDAD DE AIRE — solo avisa.
        # Si el MQ-135 esta calentando o enmascarado por el atomizador, la
        # alerta queda como estaba: no se levanta ni se baja con un dato que
        # sabemos que no sirve.
        # -------------------------------------------------------------------
        if aire_confiable and aire is not None:
            if aire < umbrales["aire_min"]:
                self.alerta_aire = True
            elif aire > apagado["aire"]:
                self.alerta_aire = False

        if self.alerta_aire:
            motivos.append("calidad de aire mala")

        # -------------------------------------------------------------------
        # 2) CO2 — solo avisa. Ningun actuador puede bajarlo: hay que abrir.
        # -------------------------------------------------------------------
        if co2 > umbrales["co2_max"]:
            self.alerta_co2 = True
        elif co2 < apagado["co2"]:
            self.alerta_co2 = False

        if co2 > critico.get("co2", 1400):
            self.co2_critico = True
        elif co2 < critico.get("co2_salida", 1100):
            self.co2_critico = False

        if self.alerta_co2:
            motivos.append("CO2 critico" if self.co2_critico else "CO2 alto")

        if self.alerta_aire or self.alerta_co2:
            avisos.append(AVISO_VENTILAR)

        if self.co2_critico:
            avisos.append(AVISO_CO2_CRITICO)

        # -------------------------------------------------------------------
        # 3) TEMPERATURA — ordena el aire acondicionado.
        # Enciende sobre el maximo del ambiente y corta por debajo del umbral
        # de apagado (2 grados mas adentro). Entre medio, sostiene.
        # -------------------------------------------------------------------
        if temp > umbrales["temp_max"]:
            self.aire_acondicionado = True
        elif temp < apagado["temp"]:
            self.aire_acondicionado = False

        if self.aire_acondicionado:
            motivos.append("temperatura alta")
            avisos.append(AVISO_AIRE_ACONDICIONADO)

        # -------------------------------------------------------------------
        # 4) HUMEDAD — enciende el atomizador, y solo para SUBIRLA.
        # -------------------------------------------------------------------
        atomizador = self._decidir_atomizador(hum, umbrales, apagado, tiempos, ahora)

        if self.atomizador_pedido:
            motivos.append("humedad baja")

        if atomizador:
            avisos.append(AVISO_HUMIDIFICANDO)

        # Humedad ALTA: el equipo no tiene con que bajarla. Se informa nomas.
        if hum > umbrales["hum_max"]:
            motivos.append("humedad alta (sin accion posible)")
            avisos.append(AVISO_HUMEDAD_ALTA)

        # -------------------------------------------------------------------
        # 5) LOS TRES LEDs — el resumen visual de todo lo anterior.
        # -------------------------------------------------------------------
        rojo = self.alerta_aire or self.alerta_co2
        azul = self.aire_acondicionado
        verde = not rojo and not azul

        actuadores = {
            "fan": "on" if self.aire_acondicionado else "off",
            "aromatizer": "on" if atomizador else "off",
            "alert_led": "on" if rojo else "off",
            "green_led": "on" if verde else "off",
            "blue_led": "on" if azul else "off",
        }

        motivo = ", ".join(motivos) if motivos else "ambiente en rango"

        return actuadores, avisos, motivo

    # -----------------------------------------------------------------------
    # El ciclo del atomizador
    # -----------------------------------------------------------------------
    def _decidir_atomizador(self, hum, umbrales, apagado, tiempos, ahora):
        """
        Devuelve True si el atomizador tiene que estar echando niebla AHORA.

        Son dos cosas encadenadas:

        1. LA DEMANDA. Se enciende cuando la humedad baja del minimo del
           ambiente y no se apaga hasta que supera el umbral de apagado (unos
           puntos mas arriba). Es la histeresis.

        2. EL CICLO. Mientras haya demanda, alterna un rato encendido y un rato
           apagado. Un atomizador ultrasonico a full encharca el ambiente,
           moja la mesa y vacia el deposito en minutos; ademas el agua tarda en
           mezclarse con el aire, asi que la pausa es la que deja que la
           humedad realmente suba antes de volver a echar.

        La esencia aromatizante, si el usuario carga una, no cambia nada de
        esto: el rele se activa igual. EdenAir no intenta regular olores solo,
        porque el MQ-135 no distingue un mal olor del vapor del propio perfume
        y quedaria realimentandose para siempre.
        """
        if hum < umbrales["hum_min"]:
            if not self.atomizador_pedido:
                # Arranca la demanda: empieza echando niebla.
                self.atomizador_pedido = True
                self.atomizador_fase_on = True
                self.atomizador_desde = ahora
        elif hum > apagado["hum"]:
            self.atomizador_pedido = False
            self.atomizador_fase_on = False

        if not self.atomizador_pedido:
            return False

        duracion = (
            tiempos.get("atomizador_on", 60)
            if self.atomizador_fase_on
            else tiempos.get("atomizador_off", 120)
        )

        if (ahora - self.atomizador_desde) >= duracion:
            self.atomizador_fase_on = not self.atomizador_fase_on
            self.atomizador_desde = ahora

        return self.atomizador_fase_on


"""
GLOSARIO DE ESTE ARCHIVO

- calcular_indice_aire(t, h, co2, umbrales) -> indice 0-100 de RESPALDO, para
  cuando el MQ-135 no esta o esta calentando
- etiqueta_aire(indice)  -> 'Excelente' | 'Buena' | 'Aceptable' | 'Mala'
- _redondear(valor)      -> redondeo estilo PHP, para que equipo y servidor
                            den el mismo numero en los empates exactos

- Decisor()              -> guarda la memoria de las reglas
- Decisor.decidir(medicion, config, ahora)
                         -> (actuadores, avisos, motivo)
- Decisor._decidir_atomizador(...) -> histeresis + ciclo 60/120 del atomizador

Conceptos:
- histeresis  -> dos umbrales, uno para encender y otro para apagar, para que
                 la salida no oscile cuando el valor queda justo en el limite
- avisos      -> codigos ("ventilar", "co2_critico"...) que la web traduce al
                 mensaje que ve la persona
"""
