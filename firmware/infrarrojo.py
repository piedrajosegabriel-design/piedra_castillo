"""
infrarrojo.py — La cadena infrarroja que ordena el aire acondicionado.

POR QUE EXISTE. EdenAir no le manda corriente a un aire acondicionado: le
manda una ORDEN, igual que el control remoto que tenes en la mesa. El equipo
emite una trama infrarroja modulada a 38 kHz, el receptor la capta, y recien
ahi se energiza el rele.

En la maqueta del otro lado hay un cooler de 30 mm que gira para mostrar que
la orden llego y se ejecuto. En una casa, del otro lado hay un aire
acondicionado que se enciende. El firmware es identico en los dos casos: lo
unico que cambia es quien recibe la trama.

QUE HACE ESTE MODULO: emitir la trama y decir si el receptor la confirmo. No
decide cuando emitirla (eso es de reglas.py) ni mueve el rele (eso es de
actuadores.py).

LA CONFIRMACION NO ES UNA CONDICION. Si el receptor no contesta dentro del
plazo, el equipo enciende el aire igual y lo informa como "orden enviada, sin
confirmacion IR". Una demostracion no se puede caer porque un LED infrarrojo
quedo mal apuntado; pero el dato viaja al panel, porque es justamente el que
delata que la cadena esta fallando.

FORMATO DE LA TRAMA: NEC, el mismo protocolo que usa la mayoria de los
controles remotos hogareños. Es una eleccion practica: cualquier receptor
comun lo entiende y se puede verificar con un decodificador de los que ya
existen.
"""

import time

from machine import PWM, Pin

import config

# Portadora del protocolo NEC. Los receptores tipo VS1838B estan filtrados a
# esta frecuencia: una trama a otra frecuencia simplemente no la ven.
PORTADORA_HZ = 38000

# Identificador del emisor y codigos de orden. Son arbitrarios: alcanza con
# que el receptor del otro lado espere estos mismos.
#
# Son dos ordenes distintas y no un unico boton que alterna, igual que en los
# controles remotos serios: asi el estado del aire nunca queda "al reves" si se
# pierde una trama.
DIRECCION_EDENAIR = 0x0E
ORDEN_AIRE_ENCENDER = 0xA1
ORDEN_AIRE_APAGAR = 0xA2

# Tiempos del protocolo NEC, en microsegundos.
_CABECERA_MARCA = 9000
_CABECERA_ESPACIO = 4500
_PULSO = 560
_ESPACIO_CERO = 560
_ESPACIO_UNO = 1690


class Infrarrojo:
    def __init__(self, pin_emisor=None, pin_receptor=None,
                 minimo_entre_tramas=None, espera_ms=None):
        self.minimo_entre_tramas = (
            config.IR_MINIMO_ENTRE_TRAMAS if minimo_entre_tramas is None else minimo_entre_tramas
        )
        self.espera_ms = (
            config.IR_ESPERA_CONFIRMACION_MS if espera_ms is None else espera_ms
        )

        self._pwm = None
        self._receptor = None

        # Cuenta de flancos vistos por el receptor. La incrementa la
        # interrupcion; se pone en cero justo antes de emitir.
        self._pulsos = 0

        # Cuando se emitio la ultima trama y como salio. Se conservan para el
        # limite de una trama cada minuto: si no toca reemitir, se informa la
        # ultima confirmacion conocida en vez de inventar una nueva.
        self._ultima_trama = 0
        self._ultima_confirmacion = None

        # Que orden llevaba la ultima trama. El limite de tiempo es contra la
        # insistencia, no contra los cambios: repetir "encender" a un aire que
        # ya lo recibio no aporta nada, pero pasar de encender a apagar es
        # informacion nueva y tiene que viajar. Sin esto el rele (que tiene su
        # propio reloj, mas corto) podia mover el aire sin que saliera trama.
        # Arranca en None para que la primera orden despues de prender la
        # placa salga siempre.
        self._ultima_orden = None

        if pin_emisor is not None:
            self._pwm = PWM(Pin(pin_emisor))
            self._pwm.freq(PORTADORA_HZ)
            self._portadora(False)

        if pin_receptor is not None:
            # El VS1838B tiene salida en colector abierto con pull-up interno:
            # queda en alto en reposo y baja cuando detecta la portadora.
            self._receptor = Pin(pin_receptor, Pin.IN, Pin.PULL_UP)
            self._receptor.irq(trigger=Pin.IRQ_FALLING, handler=self._contar_pulso)

    # -----------------------------------------------------------------------
    # Consulta
    # -----------------------------------------------------------------------
    def hay_emisor(self):
        return self._pwm is not None

    def hay_receptor(self):
        return self._receptor is not None

    def puede_emitir(self, orden=None, ahora=None):
        """
        True si la orden es distinta de la ultima emitida, o si es la misma y
        ya paso el tiempo minimo desde la ultima trama.

        El limite existe para no bombardear el ambiente con tramas: un aire
        acondicionado real tampoco necesita que le repitan la orden cada ocho
        segundos, y un receptor saturado empieza a perder tramas. Pero solo
        frena repeticiones: un cambio de orden nunca espera, porque si
        esperara el rele conmutaria sin que el aire se entere.

        Sin orden solo se mira el reloj, que es la respuesta prudente cuando
        no se sabe que se va a mandar.
        """
        if not self.hay_emisor():
            return False

        if orden is not None and orden != self._ultima_orden:
            return True

        ahora = time.time() if ahora is None else ahora

        return (ahora - self._ultima_trama) >= self.minimo_entre_tramas

    # -----------------------------------------------------------------------
    # Emision
    # -----------------------------------------------------------------------
    def enviar_orden(self, orden=ORDEN_AIRE_ENCENDER, ahora=None):
        """
        Emite la trama y espera la confirmacion del receptor.

        Devuelve:
          True  -> el receptor confirmo que la trama salio al aire
          False -> se emitio pero nadie la confirmo dentro del plazo
          None  -> no habia nada que confirmar (sin emisor o sin receptor
                   armados), o es la misma orden que la anterior, todavia no
                   se cumplio el minimo entre tramas y se mantiene la ultima
                   confirmacion conocida
        """
        ahora = time.time() if ahora is None else ahora

        if not self.hay_emisor():
            return None

        if not self.puede_emitir(orden, ahora):
            return self._ultima_confirmacion

        self._pulsos = 0
        self._emitir(orden)
        self._ultima_trama = ahora
        self._ultima_orden = orden

        if not self.hay_receptor():
            self._ultima_confirmacion = None
            return None

        self._ultima_confirmacion = self._esperar_confirmacion()

        return self._ultima_confirmacion

    def _esperar_confirmacion(self):
        """
        True si el receptor vio algo dentro del plazo.

        En la practica los pulsos llegan durante la propia emision (la trama
        dura unos 70 ms), asi que casi siempre sale por el `return True` del
        primer control. El plazo completo es el margen para cuando el receptor
        esta lejos o mal apuntado.
        """
        limite = time.ticks_add(time.ticks_ms(), self.espera_ms)

        while time.ticks_diff(limite, time.ticks_ms()) > 0:
            if self._pulsos > 0:
                return True
            time.sleep_ms(5)

        return self._pulsos > 0

    # -----------------------------------------------------------------------
    # Trama NEC
    # -----------------------------------------------------------------------
    def _emitir(self, orden):
        """Cabecera + direccion + orden (cada una con su complemento) + cierre."""
        self._marca(_CABECERA_MARCA)
        self._espacio(_CABECERA_ESPACIO)

        self._byte(DIRECCION_EDENAIR)
        self._byte(~DIRECCION_EDENAIR & 0xFF)
        self._byte(orden & 0xFF)
        self._byte(~orden & 0xFF)

        # Pulso de cierre: marca el final del ultimo bit.
        self._marca(_PULSO)
        self._portadora(False)

    def _byte(self, valor):
        """
        Ocho bits, del menos significativo al mas significativo (asi es NEC).

        Todos los bits arrancan con el mismo pulso; lo que los distingue es el
        silencio que viene despues: corto es 0, largo es 1.
        """
        for i in range(8):
            self._marca(_PULSO)
            self._espacio(_ESPACIO_UNO if valor & (1 << i) else _ESPACIO_CERO)

    def _marca(self, microsegundos):
        """Portadora encendida: el receptor ve "señal"."""
        self._portadora(True)
        time.sleep_us(microsegundos)

    def _espacio(self, microsegundos):
        """Portadora apagada: el receptor ve "silencio"."""
        self._portadora(False)
        time.sleep_us(microsegundos)

    def _portadora(self, encendida):
        """
        Prende o apaga la portadora de 38 kHz.

        Se usa el 50 % del ciclo cuando esta encendida. MicroPython cambio la
        forma de expresar el ciclo entre versiones (duty de 0-1023 y duty_u16
        de 0-65535), asi que se prueban las dos.
        """
        try:
            self._pwm.duty(512 if encendida else 0)
        except AttributeError:
            self._pwm.duty_u16(32768 if encendida else 0)

    # -----------------------------------------------------------------------
    # Receptor
    # -----------------------------------------------------------------------
    def _contar_pulso(self, pin):
        """
        Interrupcion del receptor. Tiene que ser cortisima: se ejecuta en medio
        de cualquier otra cosa que este haciendo la placa, asi que lo unico que
        hace es sumar uno.
        """
        self._pulsos += 1


"""
GLOSARIO DE ESTE ARCHIVO

- Infrarrojo(pin_emisor, pin_receptor) -> la cadena; cualquiera de los dos
  puede ser None ("no lo arme todavia")
- hay_emisor() / hay_receptor()  -> que partes estan realmente conectadas
- puede_emitir(orden, ahora)     -> orden nueva: siempre; repetida: si ya
                                   paso el minimo entre tramas
- enviar_orden(orden, ahora)     -> emite y confirma: True / False / None
- _emitir(orden)                 -> arma la trama NEC completa
- _byte(valor)                   -> ocho bits, del menos al mas significativo
- _marca() / _espacio()          -> portadora encendida / apagada
- _portadora(encendida)          -> el PWM de 38 kHz al 50 %
- _contar_pulso(pin)             -> interrupcion del receptor: solo suma uno

Conceptos:
- PWM        -> señal cuadrada; aca genera los 38 kHz que el receptor filtra
- irq()      -> "avisame cuando este pin cambie", sin quedarse esperando
- NEC        -> el protocolo de controles remotos que se usa como trama
"""
