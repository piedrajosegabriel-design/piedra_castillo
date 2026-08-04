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
        self.i2c = I2C(
            0,
            sda=Pin(config.PIN_I2C_SDA),
            scl=Pin(config.PIN_I2C_SCL),
            freq=50000,
        )

        if DIRECCION not in self.i2c.scan():
            raise ErrorSensor(
                "No se detecta el SCD41 en I2C. Revisa el cableado y los pines "
                "PIN_I2C_SDA / PIN_I2C_SCL en config.py"
            )

        # Por las dudas, cortar una medicion anterior antes de arrancar.
        try:
            self.i2c.writeto(DIRECCION, CMD_PARAR)
            time.sleep_ms(500)
        except OSError:
            pass

        self.i2c.writeto(DIRECCION, CMD_ARRANCAR)
        time.sleep_ms(100)

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
        self.i2c.writeto(DIRECCION, CMD_LISTO)
        time.sleep_ms(2)
        crudo = self.i2c.readfrom(DIRECCION, 3)

        if self._crc8(crudo[0:2]) != crudo[2]:
            raise ErrorSensor("Checksum invalido al consultar si hay dato")

        # Los 11 bits bajos en cero significan "todavia no".
        return (struct.unpack(">H", crudo[0:2])[0] & 0x07FF) != 0

    def leer(self, espera_maxima=10):
        """
        Devuelve (co2_ppm, temperatura_c, humedad_pct).

        Espera hasta `espera_maxima` segundos a que haya un dato nuevo.
        Lanza ErrorSensor si no llega o si viene corrupto.
        """
        for _ in range(espera_maxima * 2):
            if self.hay_dato():
                break
            time.sleep_ms(500)
        else:
            raise ErrorSensor("El sensor no entrego datos a tiempo")

        self.i2c.writeto(DIRECCION, CMD_LEER)
        time.sleep_ms(2)
        crudo = self.i2c.readfrom(DIRECCION, 9)

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
