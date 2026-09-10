"""
aire.py — Lectura del MQ-135 (calidad de aire).

El unico modulo que habla con el sensor analogico. Igual que sensor.py, SOLO
LEE: no decide nada, no prende nada. Devuelve un numero y en que estado esta
el sensor; que hacer con eso es problema de reglas.py.

TRES COSAS QUE ESTE MODULO RESUELVE SOLO, porque son del sensor y no de la
logica de control:

1. WARM-UP. El MQ-135 tiene un calefactor interno. Durante los primeros
   minutos su lectura es basura y no puede disparar ninguna alerta.

2. ENMASCARADO POR HUMIDIFICACION. El atomizador y el MQ-135 se pelean: la
   niebla es vapor de agua, pero el sensor la lee como contaminante. Si no se
   lo ignorara, el equipo diria "aire malo" justo cuando esta humidificando.
   Mientras el atomizador esta encendido, y un rato despues de apagarlo, se
   congela el ultimo valor bueno y no se lee de nuevo.

3. DEL ADC AL INDICE. El MQ-135 no da ppm confiables sin calibracion de
   laboratorio, asi que EdenAir nunca lo muestra en ppm: lo convierte a un
   indice de 0 a 100 (mas alto = mejor), el mismo air_quality_index que ya
   usaban la base de datos y el panel.

CABLEADO (ver config.py): el modulo se alimenta con 5 V (calefactor), pero su
salida analogica NUNCA va directo al GPIO. Va por un divisor 20k/10k, que baja
la tension a un tercio y la deja dentro de lo que tolera la ESP32.
"""

import time

from machine import ADC, Pin

import config

# Estados posibles del sensor. Los manda el equipo al servidor tal cual, y la
# web los traduce al mensaje que ve el usuario.
AUSENTE = "ausente"     # no esta conectado (PIN_MQ135 = None)
WARMUP = "warmup"       # calentando: todavia no se le puede creer
PAUSA = "pausa"         # enmascarado por el atomizador
OK = "ok"               # midiendo normal


class MedidorAire:
    def __init__(self, pin=None, warmup=None, enmascarado=None, muestras=None,
                 crudo_limpio=None, crudo_sucio=None):
        self.pin = pin
        self.warmup = config.MQ135_WARMUP if warmup is None else warmup
        self.enmascarado = config.MQ135_ENMASCARADO if enmascarado is None else enmascarado
        self.muestras = config.MQ135_MUESTRAS if muestras is None else muestras
        self.crudo_limpio = config.MQ135_CRUDO_LIMPIO if crudo_limpio is None else crudo_limpio
        self.crudo_sucio = config.MQ135_CRUDO_SUCIO if crudo_sucio is None else crudo_sucio

        # Momento del encendido: desde aca se cuenta el warm-up.
        self._arranque = time.time()

        # Ultimo indice valido. Es lo que se muestra congelado mientras el
        # atomizador enmascara la medicion.
        self._ultimo = None

        # Hasta cuando dura el enmascarado. Se recalcula en cada lectura en la
        # que el atomizador este encendido.
        self._pausa_hasta = 0

        self._adc = None

        if pin is None:
            return

        self._adc = ADC(Pin(pin))

        # 11 dB: el rango de entrada mas ancho de la ESP32 (~0 a 3.1 V), que es
        # el que necesita el divisor. Sin esto satura enseguida y el indice se
        # queda clavado en 0.
        self._adc.atten(ADC.ATTN_11DB)

        # 12 bits (0-4095). En algunos builds de MicroPython este metodo ya no
        # existe porque 12 bits es el unico ancho posible: si no esta, es que
        # ya estaba en 12 bits.
        try:
            self._adc.width(ADC.WIDTH_12BIT)
        except AttributeError:
            pass

    # -----------------------------------------------------------------------
    # Consulta
    # -----------------------------------------------------------------------
    def presente(self):
        """False si el MQ-135 no esta armado (PIN_MQ135 = None en config.py)."""
        return self._adc is not None

    def calentando(self, ahora=None):
        """True mientras el calefactor no haya cumplido su tiempo."""
        if not self.presente():
            return False

        ahora = time.time() if ahora is None else ahora

        return (ahora - self._arranque) < self.warmup

    def segundos_de_warmup(self, ahora=None):
        """Cuanto falta para que la lectura sea confiable (0 si ya lo es)."""
        if not self.presente():
            return 0

        ahora = time.time() if ahora is None else ahora
        falta = self.warmup - (ahora - self._arranque)

        return int(falta) if falta > 0 else 0

    # -----------------------------------------------------------------------
    # Lectura cruda
    # -----------------------------------------------------------------------
    def crudo(self):
        """
        Promedio de varias lecturas del ADC (0-4095).

        Se promedia porque una sola lectura del MQ-135 salta varias decenas de
        cuentas por ruido electrico, y eso se veria como un indice temblando en
        el panel. Sirve tambien para calibrar: con este numero se ajustan
        MQ135_CRUDO_LIMPIO y MQ135_CRUDO_SUCIO en config.py.
        """
        if not self.presente():
            return None

        total = 0
        for _ in range(self.muestras):
            total += self._adc.read()
            time.sleep_ms(2)

        return total // self.muestras

    # -----------------------------------------------------------------------
    # Lectura util
    # -----------------------------------------------------------------------
    def leer(self, ahora=None, atomizando=False):
        """
        Devuelve (indice, estado).

        indice -> 0 a 100 (mas alto es mejor), o None si todavia no hay ninguno
                  confiable. Durante el enmascarado se devuelve congelado el
                  ultimo valido.
        estado -> AUSENTE / WARMUP / PAUSA / OK

        `atomizando` es simplemente "el atomizador esta encendido ahora". El
        modulo se encarga solo de estirar la pausa los segundos que hagan falta
        despues de que se apague.
        """
        ahora = time.time() if ahora is None else ahora

        if not self.presente():
            return None, AUSENTE

        # Mientras haya niebla, y un rato despues, no se le cree al sensor.
        if atomizando:
            self._pausa_hasta = ahora + self.enmascarado

        if self.calentando(ahora):
            return None, WARMUP

        if ahora < self._pausa_hasta:
            return self._ultimo, PAUSA

        self._ultimo = self._a_indice(self.crudo())

        return self._ultimo, OK

    # -----------------------------------------------------------------------
    # Conversion
    # -----------------------------------------------------------------------
    def _a_indice(self, crudo):
        """
        Cuentas del ADC -> indice 0-100.

        Es una regla de tres entre los dos extremos calibrados: cuanto mas
        tension entrega el MQ-135, mas contaminante hay, y peor es el indice.
        """
        if crudo is None:
            return None

        if self.crudo_sucio <= self.crudo_limpio:
            return 100     # calibracion invalida: no ensuciamos el dato

        proporcion = (crudo - self.crudo_limpio) / (self.crudo_sucio - self.crudo_limpio)
        indice = int(100 - proporcion * 100 + 0.5)

        return max(0, min(100, indice))


"""
GLOSARIO DE ESTE ARCHIVO

- MedidorAire(pin, ...)      -> el sensor; con pin=None queda "no armado"
- presente()                 -> False si el MQ-135 no esta conectado
- calentando(ahora)          -> True durante los primeros MQ135_WARMUP segundos
- segundos_de_warmup(ahora)  -> cuanto falta para que se le pueda creer
- crudo()                    -> promedio del ADC (0-4095); sirve para calibrar
- leer(ahora, atomizando)    -> (indice 0-100 o None, estado)
- _a_indice(crudo)           -> regla de tres entre los dos extremos calibrados

Estados: AUSENTE (no armado), WARMUP (calentando), PAUSA (enmascarado por el
atomizador, valor congelado), OK (midiendo normal).
"""
