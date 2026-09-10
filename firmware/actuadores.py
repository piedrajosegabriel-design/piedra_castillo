"""
actuadores.py — El unico modulo que toca los pines de salida.

Recibe decisiones ya tomadas ("prende el aire acondicionado") y las convierte
en voltaje. No decide nada: eso es trabajo de reglas.py.

QUE MUEVE
  fan        -> rele del aire acondicionado (en la maqueta, el cooler)
  aromatizer -> rele del atomizador ultrasonico (humidificador)
  alert_led  -> LED rojo:  alerta de CO2 alto o calidad de aire mala
  green_led  -> LED verde: todo normal, monitoreo pasivo
  blue_led   -> LED azul:  orden de aire acondicionado activa

Los nombres `fan`, `aromatizer` y `alert_led` son los de la base de datos y la
API desde la primera version. Se mantienen a proposito aunque su etiqueta
visible haya cambiado: renombrarlos romperia el historial ya guardado.

SOPORTA EQUIPOS INCOMPLETOS. En config.py cada pin puede estar en None, lo que
significa "todavia no lo arme". Los que estan en None:

  - no se inicializan (no se toca ese pin)
  - no se informan al servidor

Asi el panel nunca muestra encendido algo que no existe. Podes arrancar solo
con el sensor y agregar el resto despues, sin tocar nada mas que config.py.

DOS PROTECCIONES QUE VIVEN ACA
1. Arranque limpio. Los reles de esta maqueta son ACTIVOS EN BAJO: se activan
   con 0. Un `Pin(n, Pin.OUT)` a secas deja la salida en 0 hasta que alguien
   escriba, y en esa ventana el rele pega un golpe solo. Por eso el nivel de
   apagado se pasa en el constructor: la salida nace apagada.
2. Tiempo minimo en cada estado. Un rele no puede conmutar cada dos segundos
   porque la lectura quede bailando sobre el umbral: se gasta el contacto y
   hace ruido. Cada rele tiene que quedarse quieto un rato antes de volver a
   moverse.
"""

import time

from machine import Pin

import config
import infrarrojo as ir

# Los que mueven potencia por rele (activos en bajo) y tienen tiempo minimo.
RELES = ("fan", "aromatizer")

# Los que son solo indicadores: encienden con 1 y pueden cambiar cuando sea.
LEDS = ("alert_led", "green_led", "blue_led")


class Actuadores:
    def __init__(self, cadena_ir=None, minimo_estado=None):
        # La cadena infrarroja del aire acondicionado. Se inyecta desde main.py
        # para que este modulo se pueda probar sin ella.
        self._ir = cadena_ir

        self.minimo_estado = (
            config.RELE_MINIMO_ESTADO if minimo_estado is None else minimo_estado
        )

        # Como salio la ultima orden infrarroja:
        #   True  -> el receptor la confirmo
        #   False -> se emitio y nadie la confirmo
        #   None  -> todavia no se mando ninguna, o no hay cadena IR armada
        self.ir_confirmado = None

        # Reles que pidieron cambiar en la ultima decision pero todavia no
        # cumplieron su tiempo minimo. Sirve para que main.py lo pueda avisar
        # por consola en vez de que parezca que la orden se perdio.
        self.demorados = []

        # Solo los que tienen un pin asignado en config.py.
        asignacion = {
            "fan": config.PIN_RELE_AIRE,
            "aromatizer": config.PIN_RELE_ATOMIZADOR,
            "alert_led": config.PIN_LED_ROJO,
            "green_led": config.PIN_LED_VERDE,
            "blue_led": config.PIN_LED_AZUL,
        }

        self._pines = {}
        self.estado = {}
        self._desde = {}

        ahora = time.time()

        for nombre, numero in asignacion.items():
            if numero is None:
                continue    # no esta conectado: se ignora por completo

            # value= en el constructor, no una escritura despues: el pin nace
            # apagado en vez de pasar por un instante en 0 (ver arriba).
            self._pines[nombre] = Pin(numero, Pin.OUT, value=self._nivel(nombre, "off"))
            self.estado[nombre] = "off"
            self._desde[nombre] = ahora

    # -----------------------------------------------------------------------
    # Consulta
    # -----------------------------------------------------------------------
    def hay_alguno(self):
        """False si el equipo es solo un sensor."""
        return len(self._pines) > 0

    def conectados(self):
        """Nombres de los actuadores realmente armados."""
        return list(self._pines.keys())

    def encendido(self, nombre):
        """True si ese actuador esta prendido ahora (False si no esta armado)."""
        return self.estado.get(nombre) == "on"

    # -----------------------------------------------------------------------
    # Escritura
    # -----------------------------------------------------------------------
    def _nivel(self, nombre, valor):
        """
        Traduce "on"/"off" al nivel electrico que espera cada componente.

        Los reles de esta maqueta se activan con 0 (RELES_INVERTIDOS). Los LEDs
        van directo: 1 enciende. Si un rele funciona al reves de lo que esperas,
        no toques este codigo: cambia RELES_INVERTIDOS en config.py.
        """
        encendido = valor == "on"

        if nombre in RELES and config.RELES_INVERTIDOS:
            return 0 if encendido else 1

        return 1 if encendido else 0

    def _escribir(self, nombre, valor):
        self._pines[nombre].value(self._nivel(nombre, valor))

    def _puede_conmutar(self, nombre, ahora):
        """
        Los LEDs cambian cuando haga falta; los reles, no antes de tiempo.

        Devolver False no pierde la orden: la decision se vuelve a tomar en la
        siguiente medicion, y si la condicion sigue dada el rele se mueve
        entonces.
        """
        if nombre not in RELES:
            return True

        return (ahora - self._desde[nombre]) >= self.minimo_estado

    def _ordenar_por_infrarrojo(self, valor, ahora):
        """
        La orden del aire acondicionado no viaja por el cable del rele: viaja
        por infrarrojo, igual que la de un control remoto. Recien despues de
        emitirla se energiza el rele.

        Si el receptor no confirma dentro del plazo, el aire se enciende igual
        y queda registrado que la confirmacion no llego (self.ir_confirmado).
        Una demostracion no se cae porque un LED infrarrojo quedo mal apuntado,
        pero el dato se informa porque es el que delata la falla.
        """
        if self._ir is None:
            return

        orden = ir.ORDEN_AIRE_ENCENDER if valor == "on" else ir.ORDEN_AIRE_APAGAR

        self.ir_confirmado = self._ir.enviar_orden(orden, ahora)

    def aplicar(self, deseado, ahora=None):
        """
        Lleva los actuadores al estado pedido.

        Ignora en silencio los que no estan conectados y los reles que todavia
        no cumplieron su tiempo minimo. Solo toca los que cambian, y devuelve
        la lista de los que movio.

        deseado -> {"fan": "on", "aromatizer": "off", "alert_led": "off", ...}
        """
        ahora = time.time() if ahora is None else ahora

        cambios = []
        self.demorados = []

        for nombre, valor in deseado.items():
            if nombre not in self._pines:
                continue    # no esta armado

            valor = "on" if valor == "on" else "off"

            if self.estado[nombre] == valor:
                continue

            if not self._puede_conmutar(nombre, ahora):
                self.demorados.append(nombre)
                continue

            if nombre == "fan":
                self._ordenar_por_infrarrojo(valor, ahora)

            self._escribir(nombre, valor)
            self.estado[nombre] = valor
            self._desde[nombre] = ahora
            cambios.append(nombre)

        return cambios

    def apagar_todo(self):
        """
        Deja todo apagado. Se usa al arrancar y ante un error grave.

        Salta el tiempo minimo a proposito: es una parada de emergencia, no una
        regulacion.
        """
        for nombre in self._pines:
            self._escribir(nombre, "off")
            self.estado[nombre] = "off"

    def como_dict(self):
        """
        Estado actual, para mandarlo al servidor.

        Si no hay ninguno conectado devuelve {} y el servidor no toca el
        estado de actuadores: no se inventa nada.
        """
        return dict(self.estado)


"""
GLOSARIO DE ESTE ARCHIVO

- Actuadores(cadena_ir, minimo_estado) -> arma solo los pines que no estan en
  None; los deja apagados desde el propio constructor
- hay_alguno() / conectados()   -> que hay realmente armado en esta placa
- encendido(nombre)             -> si ese actuador esta prendido ahora
- aplicar(deseado, ahora)       -> mueve lo que cambia; devuelve que movio
- apagar_todo()                 -> parada de emergencia, sin tiempo minimo
- como_dict()                   -> el estado para el reporte al servidor
- ir_confirmado                 -> True / False / None de la ultima orden IR
- demorados                     -> reles que pidieron cambiar pero todavia no
                                   cumplieron su tiempo minimo

Privados:
- _nivel(nombre, valor)         -> "on"/"off" al nivel electrico de cada tipo
- _puede_conmutar(nombre, ahora)-> tiempo minimo de los reles
- _ordenar_por_infrarrojo()     -> emite la trama antes de mover el rele
"""
