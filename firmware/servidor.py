"""
servidor.py — El unico modulo que habla con la API de EdenAir.

Traduce las cuatro operaciones del contrato HTTP a funciones de Python.
No decide nada y no toca pines: solo manda y recibe.

EL CONTRATO (ver app/Config/Routes.php, grupo api/devices):

  POST api/devices/pair                        -> alta del equipo, da credenciales
  GET  api/devices/{uid}/config                -> con que umbrales decidir
  POST api/devices/{uid}/measurements          -> subir una medicion
  GET  api/devices/{uid}/commands/pending      -> ordenes manuales del usuario
  POST api/devices/{uid}/commands/{id}/executed-> confirmar que se aplico

Salvo `pair`, todas piden el header X-Device-Token.
"""

import json

import urequests

import config


class ErrorServidor(Exception):
    """El servidor no contesta, o contesta algo que no esperabamos."""


class SinVentana(Exception):
    """
    Nadie apreto "Conectar" en la web todavia.

    No es un error: el equipo tiene que reintentar mas tarde.
    """


class Servidor:
    def __init__(self, device_uid=None, api_token=None):
        self.device_uid = device_uid
        self.api_token = api_token

    # -----------------------------------------------------------------------
    # Interno
    # -----------------------------------------------------------------------
    def _url(self, camino):
        return config.SERVIDOR + camino

    def _headers(self, con_token=True):
        cabeceras = {"Content-Type": "application/json"}
        if con_token and self.api_token:
            cabeceras["X-Device-Token"] = self.api_token
        return cabeceras

    def _pedir(self, metodo, camino, cuerpo=None, con_token=True):
        """Hace la peticion y SIEMPRE cierra la respuesta.

        En MicroPython la memoria es poca: una respuesta sin cerrar deja el
        socket abierto y despues de unas horas el equipo se queda sin RAM.
        """
        respuesta = None
        try:
            if metodo == "GET":
                respuesta = urequests.get(self._url(camino), headers=self._headers(con_token))
            else:
                respuesta = urequests.post(
                    self._url(camino),
                    data=json.dumps(cuerpo or {}),
                    headers=self._headers(con_token),
                )

            estado = respuesta.status_code
            try:
                datos = respuesta.json()
            except (ValueError, OSError):
                datos = {}

            return estado, datos
        except OSError as e:
            raise ErrorServidor("No se pudo contactar al servidor: %s" % e)
        finally:
            if respuesta is not None:
                respuesta.close()

    # -----------------------------------------------------------------------
    # 1) Alta del equipo
    # -----------------------------------------------------------------------
    def vincular(self, mac, firmware="1.0.0"):
        """
        Se presenta con su MAC y pide credenciales.

        Devuelve (device_uid, api_token).
        Lanza SinVentana si el dueño todavia no apreto "Conectar" en la web.
        """
        estado, datos = self._pedir(
            "POST", "/api/devices/pair", {"mac": mac, "firmware": firmware}, con_token=False
        )

        if estado == 202:
            raise SinVentana(datos.get("message", "Nadie esta esperando este equipo"))

        if estado != 200 or "api_token" not in datos:
            raise ErrorServidor("Vinculacion rechazada (HTTP %s): %s" % (estado, datos.get("message", "")))

        self.device_uid = datos["device_uid"]
        self.api_token = datos["api_token"]

        return self.device_uid, self.api_token

    # -----------------------------------------------------------------------
    # 2) Configuracion: con que umbrales decidir
    # -----------------------------------------------------------------------
    def config(self):
        estado, datos = self._pedir("GET", "/api/devices/%s/config" % self.device_uid)

        if estado != 200:
            raise ErrorServidor("No se pudo bajar la configuracion (HTTP %s)" % estado)

        return datos

    # -----------------------------------------------------------------------
    # 3) Subir una medicion (con lo que el equipo decidio hacer)
    # -----------------------------------------------------------------------
    def enviar_medicion(self, medicion, actuadores, motivo):
        cuerpo = {
            "temperature": medicion["temperature"],
            "humidity": medicion["humidity"],
            "co2_ppm": medicion["co2_ppm"],
            "air_quality_index": medicion["air_quality_index"],
            # Que quedo encendido despues de decidir, y por que.
            "actuadores": actuadores,
            "motivo": motivo,
        }

        estado, datos = self._pedir(
            "POST", "/api/devices/%s/measurements" % self.device_uid, cuerpo
        )

        if estado == 422:
            # El servidor rechazo el dato por imposible. Es un problema del
            # sensor, no de la red: no tiene sentido reintentar igual.
            raise ErrorServidor("Medicion rechazada: %s" % datos.get("message", ""))

        if estado != 200:
            raise ErrorServidor("Error al subir la medicion (HTTP %s)" % estado)

        return datos

    # -----------------------------------------------------------------------
    # 4) Ordenes manuales del usuario
    # -----------------------------------------------------------------------
    def comandos_pendientes(self):
        estado, datos = self._pedir(
            "GET", "/api/devices/%s/commands/pending" % self.device_uid
        )

        if estado != 200:
            raise ErrorServidor("No se pudieron consultar los comandos (HTTP %s)" % estado)

        return datos.get("pending_commands", [])

    def confirmar_comando(self, comando_id):
        """Avisa que la orden ya se aplico fisicamente."""
        estado, _ = self._pedir(
            "POST", "/api/devices/%s/commands/%s/executed" % (self.device_uid, comando_id)
        )

        return estado == 200
