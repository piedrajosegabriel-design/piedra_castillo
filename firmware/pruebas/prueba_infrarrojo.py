"""
prueba_infrarrojo.py — Comprueba en la PC cuando la cadena IR emite y cuando no.

POR QUE EXISTE. El rele del aire tiene su propio minimo (30 s) y la trama IR
el suyo (60 s). Si el IR solo mirara el reloj, el rele podria mover el aire sin
que saliera trama. Esta prueba fija la regla: la misma orden espera, una orden
distinta sale siempre.

Corre en Python de escritorio, sin placa. No va a la ESP32.

    python firmware/pruebas/prueba_infrarrojo.py
"""

import os
import sys
import types

# machine solo existe en MicroPython. Se reemplaza por lo minimo para poder
# construir la cadena con emisor y receptor "armados".
maquina = types.ModuleType("machine")


class _Pin:
    IN = 0
    PULL_UP = 1
    IRQ_FALLING = 2

    def __init__(self, *args, **kwargs):
        pass

    def irq(self, **kwargs):
        pass


class _PWM:
    def __init__(self, pin):
        pass

    def freq(self, hz):
        pass

    def duty(self, valor):
        pass


maquina.Pin = _Pin
maquina.PWM = _PWM
sys.modules["machine"] = maquina

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), ".."))

import infrarrojo as ir  # noqa: E402


class CadenaDePrueba(ir.Infrarrojo):
    """
    La cadena real, salvo el tramo fisico: en vez de modular 38 kHz anota que
    orden salio, y la confirmacion la dicta la prueba. Asi se distingue una
    confirmacion nueva de la vieja que se devuelve sin emitir.
    """

    def __init__(self):
        super().__init__(pin_emisor=25, pin_receptor=26, minimo_entre_tramas=60)
        self.emitidas = []
        self.proxima_confirmacion = True

    def _emitir(self, orden):
        self.emitidas.append(orden)

    def _esperar_confirmacion(self):
        return self.proxima_confirmacion


def caso(numero, descripcion, cadena, orden, t, debe_emitir, confirmacion_esperada):
    antes = len(cadena.emitidas)
    resultado = cadena.enviar_orden(orden, ahora=t)
    emitio = len(cadena.emitidas) > antes

    bien = emitio == debe_emitir and resultado == confirmacion_esperada
    print("%s %d. %-44s emitio=%-5s devolvio=%s" % (
        "OK  " if bien else "FALLA", numero, descripcion, emitio, resultado))
    return bien


def main():
    cadena = CadenaDePrueba()
    resultados = []

    # La confirmacion de cada emision real es distinta, para que se note si
    # un caso devuelve la vieja o una nueva.
    cadena.proxima_confirmacion = True
    resultados.append(caso(1, "ENCENDER en t=0 (primera orden)",
                           cadena, ir.ORDEN_AIRE_ENCENDER, 0, True, True))

    cadena.proxima_confirmacion = False
    resultados.append(caso(2, "ENCENDER en t=10 (repetida, < 60 s)",
                           cadena, ir.ORDEN_AIRE_ENCENDER, 10, False, True))

    cadena.proxima_confirmacion = False
    resultados.append(caso(3, "APAGAR en t=15 (distinta, < 60 s)",
                           cadena, ir.ORDEN_AIRE_APAGAR, 15, True, False))

    cadena.proxima_confirmacion = True
    resultados.append(caso(4, "APAGAR en t=80 (repetida, 65 s despues)",
                           cadena, ir.ORDEN_AIRE_APAGAR, 80, True, True))

    print()
    print("Tramas emitidas:", ["0x%02X" % o for o in cadena.emitidas])
    print("%d de %d casos bien" % (sum(resultados), len(resultados)))

    return 0 if all(resultados) else 1


if __name__ == "__main__":
    sys.exit(main())
