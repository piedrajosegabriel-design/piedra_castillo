"""
reglas.py — LA LOGICA DE CONTROL DEL EQUIPO.

Este es el modulo mas importante del firmware y el corazon de la separacion
de responsabilidades: aca, y solo aca, se decide cuando prender cada actuador.

ANTES esto vivia en el servidor (app/Services/AutomationService.php). El
equipo era un ejecutor tonto: medía, mandaba el dato, esperaba una orden por
HTTP y recien ahi accionaba. Eso tenia dos problemas:

  1. Sin internet el equipo no regulaba nada.
  2. Reaccionar a un CO2 alto dependia de la latencia de una peticion HTTP.

AHORA el equipo decide solo. El servidor le manda los NUMEROS (los umbrales
del ambiente, que el usuario edita desde /panel/ambientes) y el equipo aplica
la REGLA. Cambiar un rango sigue siendo cosa de la web; decidir es cosa del
equipo.

Este modulo es PURO: no toca pines, no toca la red, no lee sensores. Recibe
numeros y devuelve una decision. Eso lo hace facil de probar y de explicar.
"""


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
    Indice de calidad de aire, de 0 a 100 (mas alto es mejor).

    No es un sensor: es una cuenta. Arranca en 100 y descuenta puntos por
    cada desvio respecto del ambiente configurado.

    Es la misma formula que usa el servidor en MeasurementService, para que
    el numero que decide el equipo y el que muestra el panel coincidan.
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


def decidir(medicion, config):
    """
    Dada una medicion y la configuracion del servidor, decide que actuadores
    tienen que quedar encendidos.

    Devuelve (actuadores, motivo):
      actuadores -> {"fan": "on"|"off", "aromatizer": ..., "alert_led": ...}
      motivo     -> texto legible que se guarda en el historial del panel

    Las tres reglas son las mismas que tenia el servidor, para que el
    comportamiento no cambie al mudar la logica al equipo.
    """
    umbrales = config["umbrales"]
    margenes = config["margenes_alerta"]

    temp = medicion["temperature"]
    hum = medicion["humidity"]
    co2 = medicion["co2_ppm"]
    aire = medicion["air_quality_index"]

    motivos = []

    # -----------------------------------------------------------------------
    # REGLA 1 — Ventilador / aire.
    # Se enciende si CUALQUIERA de los tres valores supera el maximo.
    # -----------------------------------------------------------------------
    ventilador = (
        temp > umbrales["temp_max"]
        or hum > umbrales["hum_max"]
        or co2 > umbrales["co2_max"]
    )
    if ventilador:
        if temp > umbrales["temp_max"]:
            motivos.append("temperatura alta")
        if hum > umbrales["hum_max"]:
            motivos.append("humedad alta")
        if co2 > umbrales["co2_max"]:
            motivos.append("CO2 alto")

    # -----------------------------------------------------------------------
    # REGLA 2 — Aromatizador.
    # Se enciende cuando la calidad de aire baja del umbral configurado.
    # -----------------------------------------------------------------------
    aromatizador = aire < config["aire_aromatizador"]
    if aromatizador:
        motivos.append("calidad de aire baja")

    # -----------------------------------------------------------------------
    # REGLA 3 — LED de alerta.
    # Solo ante desvios GRAVES: no alcanza con salirse del rango, hay que
    # pasarse tambien del margen extra que manda el servidor.
    # -----------------------------------------------------------------------
    alerta = (
        temp > umbrales["temp_max"] + margenes["temp"]
        or temp < umbrales["temp_min"] - margenes["temp"]
        or hum > umbrales["hum_max"] + margenes["hum"]
        or hum < umbrales["hum_min"] - margenes["hum"]
        or co2 > umbrales["co2_max"] + margenes["co2"]
        or aire < margenes["aire"]
    )
    if alerta:
        motivos.append("desvio grave")

    actuadores = {
        "fan": "on" if ventilador else "off",
        "aromatizer": "on" if aromatizador else "off",
        "alert_led": "on" if alerta else "off",
    }

    motivo = ", ".join(motivos) if motivos else "ambiente en rango"

    return actuadores, motivo


def etiqueta_aire(indice):
    """Traduce el indice a texto, igual que el servidor."""
    if indice >= 85:
        return "Excelente"
    if indice >= 70:
        return "Buena"
    if indice >= 55:
        return "Aceptable"
    return "Mala"
