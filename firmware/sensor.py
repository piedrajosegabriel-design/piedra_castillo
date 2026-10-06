"""
sensor.py — Lectura del SCD41 (temperatura, humedad y CO2).

El unico modulo que habla I2C con el sensor. Devuelve numeros crudos; no
calcula el indice de aire ni decide nada.

Sobre el SCD41: mide CO2 de verdad (sensor NDIR), no lo estima. Trabaja en
modo "periodic measurement": se le da la orden de arrancar una vez y despues
entrega un dato nuevo cada 5 segundos aproximadamente.
"""

import struct
import time

from machine import I2C, Pin

import config

DIRECCION = 0x62  # direccion I2C fija del SCD41

CMD_ARRANCAR = b"\x21\xb1"      # start_periodic_measurement
CMD_LISTO = b"\xe4\xb8"         # get_data_ready_status
CMD_LEER = b"\xec\x05"          # read_measurement
CMD_PARAR = b"\x3f\x86"         # stop_periodic_measurement


class ErrorSensor(Exception):
    """El sensor no contesta o devuelve datos con checksum invalido."""


class SCD41:
    def __init__(self):
        # Lo deja en False; rearrancar() lo pone en True. Se declara igual aca
        # para que el atributo exista siempre, sin depender del orden de las
        # llamadas.
        self._descartar_primera = False
        self._bus_caido = False

        self._abrir_bus()

        # Arranque del modo periodico. Es la misma maniobra que hace falta si el
        # sensor se sale del modo mas adelante, asi que vive en un solo lugar.
        self.rearrancar()

    # -----------------------------------------------------------------------
    # Bus I2C
    # -----------------------------------------------------------------------
    def _abrir_bus(self):
        """Crea el bus I2C y confirma que el SCD41 este del otro lado."""
        self.i2c = I2C(
            0,
            sda=Pin(config.PIN_I2C_SDA),
            scl=Pin(config.PIN_I2C_SCL),
            freq=50000,
        )

        encontrados = self.i2c.scan()

        if DIRECCION not in encontrados:
            if encontrados:
                detalle = "En el bus hay otra cosa (%s) pero no el SCD41 (0x62)." % (
                    ", ".join("0x%02X" % d for d in encontrados))
            else:
                detalle = "No contesta nada en el bus: cable suelto, SDA/SCL al reves o sin 3V3/GND."

            raise ErrorSensor(
                "No se detecta el SCD41 (SDA=GPIO%s, SCL=GPIO%s). %s"
                % (config.PIN_I2C_SDA, config.PIN_I2C_SCL, detalle)
            )

        self._bus_caido = False

    def _escribir(self, comando):
        try:
            self.i2c.writeto(DIRECCION, comando)
        except OSError as e:
            self._caido(e)

    def _leer_bytes(self, cantidad):
        try:
            return self.i2c.readfrom(DIRECCION, cantidad)
        except OSError as e:
            self._caido(e)

    def _caido(self, e):
        """
        El SCD41 dejo de contestar a mitad de camino.

        En la ESP32 eso llega como OSError [Errno 19] ENODEV (o ETIMEDOUT). Antes
        escapaba como un error cualquiera: main() no lo reconocia como falla de
        sensor y el equipo quedaba conectado al WiFi sin medir nunca, repitiendo
        "Error inesperado: [Errno 19]". Ahora se traduce a ErrorSensor y la
        proxima lectura vuelve a abrir el bus: si fue un falso contacto
        momentaneo, el equipo se recupera solo.
        """
        self._bus_caido = True
        raise ErrorSensor(
            "El SCD41 no contesta por I2C (%s). Suele ser un cable flojo en "
            "SDA (GPIO%s), SCL (GPIO%s), 3V3 o GND." % (e, config.PIN_I2C_SDA, config.PIN_I2C_SCL)
        )

    # -----------------------------------------------------------------------
    # Checksum
    # -----------------------------------------------------------------------
    @staticmethod
    def _crc8(datos):
        """
        CRC-8 con polinomio 0x31, como pide la hoja de datos de Sensirion.
        Cada par de bytes viene con su checksum: si no coincide, el dato
        llego corrupto y hay que descartarlo (no usarlo igual).
        """
        crc = 0xFF
        for byte in datos:
            crc ^= byte
            for _ in range(8):
                if crc & 0x80:
                    crc = ((crc << 1) ^ 0x31) & 0xFF
                else:
                    crc = (crc << 1) & 0xFF
        return crc

    # -----------------------------------------------------------------------
    # Lectura
    # -----------------------------------------------------------------------
    def hay_dato(self):
        """¿El sensor tiene una medicion nueva lista?"""
        self._escribir(CMD_LISTO)
        time.sleep_ms(2)
        crudo = self._leer_bytes(3)

        if self._crc8(crudo[0:2]) != crudo[2]:
            raise ErrorSensor("Checksum invalido al consultar si hay dato")

        # Los 11 bits bajos en cero significan "todavia no".
        return (struct.unpack(">H", crudo[0:2])[0] & 0x07FF) != 0

    def _esperar_dato(self, espera_maxima):
        """True si aparecio un dato nuevo dentro del plazo."""
        for _ in range(espera_maxima * 2):
            if self.hay_dato():
                return True
            time.sleep_ms(500)
        return False

    def rearrancar(self):
        """
        Vuelve a poner el sensor en modo periodico.

        POR QUE EXISTE (esto paso de verdad, el 11/08/2026):
        el SCD41 puede salirse del modo periodico despues del arranque — por un
        bajon de tension, un glitch en el bus, cualquier cosa. Cuando pasa, el
        chip SIGUE contestando por I2C y con checksum valido, pero
        get_data_ready_status devuelve siempre 0x0: nunca hay dato nuevo.

        El arranque se mandaba UNA sola vez, en __init__. Asi que si el sensor
        se salia del modo, el equipo quedaba fallando en silencio para siempre:
        WiFi bien, comandos bien, last_seen_at fresco... y ni una medicion.
        Estuvo asi 13 horas sin que nada lo delatara.
        """
        try:
            self.i2c.writeto(DIRECCION, CMD_PARAR)
        except OSError:
            pass

        # 1000 ms, no 500. La hoja de datos dice que stop_periodic_measurement
        # tarda 500 ms, y el codigo esperaba exactamente eso: sin margen. Medido
        # en la placa (11/08/2026), esa espera justa FALLA de forma reproducible:
        #
        #     solo ARRANCAR .................. dato en 4.5 s
        #     PARAR + 500 ms + ARRANCAR ...... nunca entrego dato
        #     PARAR + 1000 ms + ARRANCAR ..... dato en 4.5 s
        #
        # Con 500 ms el sensor todavia esta procesando el "parar" y se come el
        # "arrancar": queda detenido, contestando por I2C pero sin medir nunca.
        time.sleep_ms(1000)

        self._escribir(CMD_ARRANCAR)
        time.sleep_ms(100)

        # La hoja de datos avisa que la PRIMERA lectura despues de arrancar
        # puede venir con valores imposibles: recien arrancado se vio un CO2 de
        # 56 ppm, que el servidor rechaza con HTTP 422 por estar fuera del rango
        # fisico. Se marca para descartarla, tanto en el arranque inicial como
        # en una recuperacion.
        self._descartar_primera = True

    def _descartar_si_hace_falta(self, espera_maxima):
        """Consume y tira la primera lectura posterior a un arranque."""
        if not self._descartar_primera:
            return

        self._descartar_primera = False

        if self._esperar_dato(espera_maxima):
            self._escribir(CMD_LEER)
            time.sleep_ms(2)
            self._leer_bytes(9)

    def leer(self, espera_maxima=10):
        """
        Devuelve (co2_ppm, temperatura_c, humedad_pct).

        Espera hasta `espera_maxima` segundos a que haya un dato nuevo. Si no
        llega, asume que el sensor se salio del modo periodico, lo rearranca y
        vuelve a esperar UNA vez. Recien si tampoco asi, lanza ErrorSensor.

        Si la lectura anterior se corto por I2C, primero vuelve a abrir el bus y
        a arrancar el sensor: asi un falso contacto no lo deja muerto para siempre.
        """
        if self._bus_caido:
            print("Reconectando el SCD41...")
            self._abrir_bus()
            self.rearrancar()

        self._descartar_si_hace_falta(espera_maxima)

        if not self._esperar_dato(espera_maxima):
            print("El sensor no entrego datos. Rearrancando modo periodico...")
            self.rearrancar()
            self._descartar_si_hace_falta(espera_maxima)

            if not self._esperar_dato(espera_maxima):
                raise ErrorSensor("El sensor no entrego datos ni despues de rearrancarlo")

        self._escribir(CMD_LEER)
        time.sleep_ms(2)
        crudo = self._leer_bytes(9)

        # Tres grupos de [dato_alto, dato_bajo, crc].
        for i in (0, 3, 6):
            if self._crc8(crudo[i:i + 2]) != crudo[i + 2]:
                raise ErrorSensor("Checksum invalido en la medicion")

        co2 = struct.unpack(">H", crudo[0:2])[0]
        temp_cruda = struct.unpack(">H", crudo[3:5])[0]
        hum_cruda = struct.unpack(">H", crudo[6:8])[0]

        # Formulas de conversion de la hoja de datos.
        temperatura = -45 + 175 * temp_cruda / 65535
        humedad = 100 * hum_cruda / 65535

        return co2, round(temperatura, 1), round(humedad, 1)
