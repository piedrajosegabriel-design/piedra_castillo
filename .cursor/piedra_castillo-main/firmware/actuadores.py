"""
actuadores.py — El unico modulo que toca los pines de salida.

Recibe decisiones ya tomadas ("prende el ventilador") y las convierte en
voltaje. No decide nada: eso es trabajo de reglas.py.

SOPORTA EQUIPOS INCOMPLETOS. En config.py cada actuador puede estar en None,
lo que significa "todavia no lo arme". Los que estan en None:

  - no se inicializan (no se toca ese pin)
  - no se informan al servidor

Asi el panel nunca muestra un ventilador encendido que no existe. Podes
arrancar solo con el sensor y agregar actuadores despues, sin tocar nada mas
que config.py.
"""

from machine import Pin

import config


class Actuadores:
    def __init__(self):
        # Solo los que tienen un pin asignado en config.py.
        asignacion = {
            "fan": config.PIN_VENTILADOR,
            "aromatizer": config.PIN_AROMATIZADOR,
            "alert_led": config.PIN_LED_ALERTA,
        }

        self._pines = {}
        self.estado = {}

        for nombre, numero in asignacion.items():
            if numero is None:
                continue    # no esta conectado: se ignora por completo
            self._pines[nombre] = Pin(numero, Pin.OUT)
            self.estado[nombre] = "off"
            self._escribir(nombre, "off")

    # -----------------------------------------------------------------------
    # Consulta
    # -----------------------------------------------------------------------
    def hay_alguno(self):
        """False si el equipo es solo un sensor."""
        return len(self._pines) > 0

    def conectados(self):
        """Nombres de los actuadores realmente armados."""
        return list(self._pines.keys())

    # -----------------------------------------------------------------------
    # Escritura
    # -----------------------------------------------------------------------
    def _escribir(self, nombre, valor):
        """
        Traduce "on"/"off" al nivel electrico que espera el hardware.

        Muchos modulos de rele se activan con 0 en vez de 1. Si el ventilador
        arranca al reves de lo que esperas, no toques este codigo: cambia
        RELES_INVERTIDOS en config.py.
        """
        encendido = valor == "on"

        if config.RELES_INVERTIDOS:
            self._pines[nombre].value(0 if encendido else 1)
        else:
            self._pines[nombre].value(1 if encendido else 0)

    def aplicar(self, deseado):
        """
        Lleva los actuadores al estado pedido.

        Ignora en silencio los que no estan conectados. Solo toca los que
        cambian, y devuelve la lista de los que movio.

        deseado -> {"fan": "on", "aromatizer": "off", "alert_led": "off"}
        """
        cambios = []

        for nombre, valor in deseado.items():
            if nombre not in self._pines:
                continue    # no esta armado

            valor = "on" if valor == "on" else "off"

            if self.estado[nombre] == valor:
                continue

            self._escribir(nombre, valor)
            self.estado[nombre] = valor
            cambios.append(nombre)

        return cambios

    def apagar_todo(self):
        """Deja todo apagado. Se usa al arrancar y ante un error grave."""
        self.aplicar({nombre: "off" for nombre in self._pines})

    def como_dict(self):
        """
        Estado actual, para mandarlo al servidor.

        Si no hay ninguno conectado devuelve {} y el servidor no toca el
        estado de actuadores: no se inventa nada.
        """
        return dict(self.estado)
