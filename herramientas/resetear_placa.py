"""
resetear_placa.py — Deja el Eden Air como recien salido de fabrica.

Borra de la placa los tres archivos que guarda sola y la reinicia. Despues de
correrlo, el equipo vuelve a levantar el portal EdenAir-Setup y se puede
vincular otra vez con el QR, cuantas veces haga falta.

  wifi.json          la red WiFi de casa y su clave
  credenciales.json  el device_uid y el api_token que dio el servidor
  servidor.json      la direccion de la web, si se cambio desde el portal
  sesion.json        el codigo de un solo uso del portal

NO toca ningun .py: el firmware queda intacto.

COMO SE USA
  Doble clic en resetear_placa.cmd, o desde la terminal:
      <python-con-pyserial> herramientas/resetear_placa.py [COM17]

  Si no se le pasa el puerto, lo busca solo. Necesita un Python con pyserial;
  el que trae Thonny ya lo tiene, y el .cmd de al lado lo encuentra solo.

OJO: cerra Thonny antes. Mientras Thonny este abierto ocupa el puerto y esto
no se va a poder conectar.
"""
import sys
import time

try:
    import serial
    from serial.tools import list_ports
except ImportError:
    print("Falta pyserial. Corre este script con el Python de Thonny")
    print("(o instalalo con: pip install pyserial).")
    sys.exit(1)


ARCHIVOS = ("wifi.json", "credenciales.json", "servidor.json", "sesion.json")


def buscar_puerto():
    """El primer puerto que parezca una ESP32 (CH340, CP210x, FTDI)."""
    for p in list_ports.comports():
        descripcion = (p.description or "") + (p.manufacturer or "")
        if any(c in descripcion for c in ("CH340", "CP210", "FTDI", "USB-SERIAL")):
            return p.device
    return None


def limpiar(crudo):
    """Saca del texto lo que es protocolo del REPL y no le sirve a nadie."""
    texto = crudo.decode("utf-8", "replace").split("\x04")[0]
    if texto.startswith("OK"):
        texto = texto[2:]
    return texto.strip()


def leer_hasta(puerto, marca, limite=8.0):
    fin = time.time() + limite
    buf = b""
    while time.time() < fin:
        buf += puerto.read(puerto.in_waiting or 1)
        if marca in buf:
            return buf
    return buf


def main():
    com = sys.argv[1] if len(sys.argv) > 1 else buscar_puerto()
    if not com:
        print("No encontre ninguna placa conectada.")
        print("Fijate que el cable USB este enchufado, o pasale el puerto:")
        print("    resetear_placa.py COM17")
        return 1

    print("Placa en", com)

    s = serial.Serial()
    s.port = com
    s.baudrate = 115200
    s.timeout = 0.3
    # Sin esto, abrir el puerto reinicia la placa por el circuito de auto-reset.
    s.dtr = False
    s.rts = False

    try:
        s.open()
    except serial.SerialException as e:
        print("No pude abrir", com, "->", e)
        print("Si tenes Thonny abierto, cerralo y proba de nuevo.")
        return 1

    with s:
        # Cortar el programa que este corriendo y entrar al REPL crudo.
        for _ in range(4):
            s.write(b"\x03")
            time.sleep(0.25)
        s.reset_input_buffer()

        s.write(b"\x01")
        if b"raw REPL" not in leer_hasta(s, b"raw REPL"):
            print("La placa no respondio. Desenchufala, volve a enchufarla")
            print("y proba de nuevo.")
            return 1
        leer_hasta(s, b">", 2.0)

        codigo = (
            "import os\n"
            "for a in %r:\n"
            "    try:\n"
            "        os.remove(a)\n"
            "        print('  borrado:', a)\n"
            "    except OSError:\n"
            "        print('  no estaba:', a)\n"
        ) % (ARCHIVOS,)

        s.write(codigo.encode() + b"\x04")
        salida = leer_hasta(s, b"\x04>", 15.0)
        print(limpiar(salida))

        # Reiniciar para que levante el portal de una.
        s.write(b"import machine\nmachine.reset()\x04")
        time.sleep(3)
        arranque = leer_hasta(s, b"EdenAir-Setup", 15.0)
        for linea in limpiar(arranque).splitlines():
            if linea.startswith(("===", "Punto de acceso", "Redes encontradas")):
                print(linea)

    print()
    print("Listo: la placa quedo en cero y ya esta publicando EdenAir-Setup.")
    print("Entra a la web, apreta 'Conectar' y escanea el QR con el celular.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
