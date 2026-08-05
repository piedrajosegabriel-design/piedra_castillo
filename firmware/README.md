# Firmware del Eden Air (MicroPython)

Codigo que corre **adentro de la ESP32**.

No tiene nada que ver con el PHP: son dos programas separados que se hablan
por HTTP. Esta carpeta se puede abrir en Thonny sin tocar el resto del
proyecto.

---

## QUIEN HACE QUE: Thonny NO es parte del producto

Es la distincion mas importante de este documento, y la que mas confusion
genera. Son dos roles distintos:

| | Quien | Con que | Cuando |
|---|---|---|---|
| **Grabar el firmware** | Vos, armando el equipo | Thonny + cable USB | **Una vez por placa**, antes de entregarla |
| **Conectar el equipo al WiFi** | **El cliente** | **Solo su celular** | Cada vez que cambia de red |

**El cliente nunca instala Thonny, nunca ve un cable de datos y nunca abre un
archivo `.py`.** Recibe la placa ya grabada, la enchufa, escanea el QR desde
la web y elige su WiFi en una pagina que se abre sola en su telefono.

Si en algun momento te encontras diciendo "el cliente tiene que abrir Thonny
para...", eso es un bug de diseño. Lo unico que antes obligaba a eso era la
direccion del servidor grabada en `config.py`; hoy se puede cambiar desde el
propio portal del celular, en **Opciones avanzadas**.

Todo lo que sigue en este README, salvo la seccion "Primer arranque", es
trabajo de **fabricacion**.

---

## El reparto de responsabilidades

Esta es la idea central, y conviene tenerla clara antes de tocar codigo:

| | Quien lo hace | Donde |
|---|---|---|
| Leer el sensor | ESP32 | `sensor.py` |
| **Decidir que actuador prender** | **ESP32** | **`reglas.py`** |
| Mover los reles | ESP32 | `actuadores.py` |
| Guardar el historial | Servidor | `MeasurementService.php` |
| Definir los umbrales | Servidor (los edita el usuario) | `/panel/ambientes` |
| Mostrar el dashboard | Servidor | `PanelService.php` |

**El servidor no decide nada.** Manda los numeros (los umbrales del ambiente);
el equipo aplica la regla. Por eso:

- Si se corta internet, el equipo **sigue regulando el ambiente**. Solo deja
  de reportar.
- Cambiar un rango desde la web **no requiere reprogramar la placa**: el
  equipo lo lee la proxima vez que pide su configuracion.

La unica excepcion es el **modo manual**: ahi el equipo no decide y se limita a
obedecer las ordenes que el usuario manda desde el dashboard.

---

## Los archivos

| Archivo | Que hace | Se toca? |
|---|---|---|
| `config.py` | Direccion del servidor, pines, credenciales del AP | **Si**, es lo unico que se edita por placa |
| `main.py` | El ciclo principal. Orquesta, no calcula | Rara vez |
| `reglas.py` | **Las decisiones.** Puro: recibe numeros, devuelve decision | Si cambian las reglas |
| `sensor.py` | Lee el SCD41 por I2C | Si cambias de sensor |
| `actuadores.py` | Mueve los pines de los reles | Si cambias el hardware |
| `red.py` | WiFi + portal de configuracion | Casi nunca |
| `servidor.py` | Las llamadas HTTP a la API | Si cambia la API |

Estan separados a proposito: podes cambiar de sensor sin tocar las reglas, o
afinar las reglas sin saber nada de I2C.

---

## El cableado minimo: ESP32 + SCD41

Con **solo el sensor** ya funciona todo el sistema: el equipo se vincula, mide y
el dashboard muestra datos reales. Los actuadores se agregan despues.

Son **4 cables** entre el SCD41 y la ESP32:

| SCD41 | ESP32 | Que es |
|---|---|---|
| `VIN` (o `VDD`) | **3V3** | Alimentacion |
| `GND` | **GND** | Masa |
| `SDA` | **GPIO 21** | Datos I2C |
| `SCL` | **GPIO 22** | Reloj I2C |

> **Usa 3V3, no 5V.** Aunque muchos modulos toleran 5 V en VIN, la ESP32
> trabaja a 3.3 V y asi no arriesgas nada.

Si tu modulo trae mas pines (`ADDR`, `INT`), dejalos sin conectar.

El cable USB va de la ESP32 a la computadora: sirve para alimentarla **y** para
programarla desde Thonny. No hace falta fuente aparte.

### Sin actuadores todavia

En `config.py` los tres actuadores vienen en `None`:

```python
PIN_VENTILADOR = None
PIN_AROMATIZADOR = None
PIN_LED_ALERTA = None
```

Eso significa "no los arme". El equipo **no toca esos pines** y **no le dice al
servidor que estan encendidos**, asi el panel no muestra un ventilador que no
existe. En la consola vas a ver:

```
Sin actuadores conectados: el equipo solo mide y reporta.
```

Cuando armes uno, le pones su numero de pin y volves a subir `config.py`. Nada
mas cambia.

---

## Preparar la ESP32 (una sola vez por placa)

### 1. Instalar MicroPython

Si la placa nunca tuvo MicroPython:

1. Baja el `.bin` para ESP32 de <https://micropython.org/download/esp32/>
2. En Thonny: **Herramientas → Opciones → Interprete**
3. Elegi **MicroPython (ESP32)** y tu puerto COM
4. Abajo a la derecha, **Instalar o actualizar MicroPython**
5. Elegi el `.bin` que bajaste y dale **Instalar**

Cuando termine, en la consola de Thonny (abajo) tiene que aparecer `>>>`.

### 2. Instalar la libreria de HTTP

`servidor.py` usa `urequests`, que no viene incluida:

**Herramientas → Administrar paquetes** → buscar `urequests` → **Instalar**

### 3. Configurar `config.py`

Es el unico archivo que hay que editar. Lo importante:

```python
SERVIDOR_DEFECTO = "http://192.168.1.100/piedra_castillo/public"
```

> **Ojo con esto.** No sirve `localhost` ni `127.0.0.1`: para la ESP32,
> localhost es ella misma. Tenes que poner la **IP de la computadora que corre
> XAMPP** dentro de tu red WiFi. En Windows la sacas con `ipconfig` (campo
> "Direccion IPv4", algo como `192.168.1.x`).
>
> Esa computadora tiene que estar **en la misma red WiFi** que la ESP32, y con
> Apache prendido.

Es el **valor de fabrica**, no una condena: si despues la IP cambia, se
corrige desde el portal del celular (*Opciones avanzadas*) y queda guardada en
`servidor.json`. No hay que volver a abrir Thonny por un cambio de red.

Despues revisa los pines (`PIN_VENTILADOR`, `PIN_I2C_SDA`, etc.) segun como
tengas armado el circuito.

---

## Subir el codigo con Thonny

1. **Ver → Archivos** (para tener el panel de archivos)
2. Arriba vas a ver tu PC; abajo, **Dispositivo MicroPython**
3. Navega en el panel de arriba hasta esta carpeta `firmware/`
4. Selecciona **los 7 archivos `.py`**
5. Click derecho → **Subir a /**

Deberian quedar los 7 en el panel del dispositivo.

> **Importante:** hay que subir los 7, no solo `main.py`. Si falta uno, la
> placa arranca y falla con `ImportError: no module named ...`.

6. Apreta el boton de **reset** de la placa (o Ctrl+D en la consola de Thonny)

En la consola tendria que aparecer:

```
=== Eden Air ===
Conectando a MiWiFi ...
Conectado. IP: 192.168.1.55
Sensor SCD41 listo.
Equipo sin vincular. MAC: A1:B2:C3:D4:E5:F6
Entra a EdenAir y apreta 'Conectar'.
```

---

## Primer arranque: lo que hace EL CLIENTE

**Esta es la unica seccion que le importa a quien compra el equipo.** Todo se
hace desde el celular: no hay que instalar nada ni conectar ningun cable a la
computadora.

1. **Enchufa la placa.** Como no tiene WiFi guardado, crea su propia red:
   `EdenAir-Setup`.
2. **En la web**, entra a *Mis dispositivos → Conectar dispositivo* y aprieta
   **Conectar**. Aparece un QR.
3. **Escanea el QR con la camara del celular.** El telefono entra solo a
   `EdenAir-Setup` y se abre la pagina que sirve `red.py`.
4. **Elige su WiFi de casa y pone la clave.** La lista viene ordenada por
   señal y con candado en las protegidas; el boton **Ver** deja leer la clave
   mientras la escribe.
5. **La placa prueba la clave.** Si anda, guarda `wifi.json` y sigue. Si esta
   mal, **vuelve a levantar `EdenAir-Setup`** y al reconectarse el cliente
   encuentra un cartel rojo que le dice exactamente que paso.
6. **El portal le da un boton "Ver mi Eden Air"** que apunta a
   `/vinculacion/seguir?s=CODIGO`. Cuando el celular vuelve solo a su WiFi
   normal, el cliente lo toca y cae en una pantalla que le muestra su equipo
   ya conectado, **sin iniciar sesion**.
7. **La placa llama sola** a `POST /api/devices/pair` con su MAC y ese mismo
   codigo. Como hay una ventana abierta, el servidor la da de alta y le
   devuelve sus credenciales, que quedan en `credenciales.json`.
8. **Las dos pantallas lo detectan**: la de la computadora y la del celular.

De ahi en mas la placa arranca directo: lee `wifi.json` y `credenciales.json`
y se pone a medir. El celular no vuelve a hacer falta.

### Por que el codigo lo inventa la placa y no la web

Es la pregunta que siempre aparece: *¿por que el QR no lleva el link y listo?*

Un QR con una URL abre el navegador, pero **no puede cambiar de red WiFi al
telefono**. Y el formulario de configuracion tiene que vivir en la ESP32,
porque es el unico que puede recibir la clave del WiFi de la casa. Para llegar
a el, el celular tiene que estar antes en `EdenAir-Setup`, y lo unico que hace
eso solo es un QR de tipo `WIFI:`.

Ademas, cuando la web dibuja el QR todavia no existe ningun canal hacia la
placa: no esta en ninguna red. Asi que el dato no puede ir web -> placa.

Por eso viaja al reves: **la placa inventa el codigo** y lo reparte por dos
caminos que se encuentran en el servidor.

    ESP32 ──(boton del portal)──> celular ──> /vinculacion/seguir?s=CODIGO
      │                                                  ↑
      └──(POST /api/devices/pair, session)──> servidor ──┘

### Si el equipo se muda a otra red

El cliente repite los pasos 1 a 5. Para que la placa vuelva a abrir su portal
hay que borrarle el WiFi viejo; como no queremos obligarlo a usar Thonny, la
placa lo hace sola: si la red guardada ya no responde, **reabre el portal
automaticamente** (ver `asegurar_conexion()`).

### Si cambio la computadora donde corre EdenAir

En el portal, **Opciones avanzadas → Direccion del servidor**. Se guarda en
`servidor.json` y pisa el `SERVIDOR_DEFECTO` de `config.py`. Sin Thonny.

### Empezar de cero (fabricacion)

Para dejar una placa como recien salida, borra desde Thonny los archivos
`wifi.json`, `credenciales.json` y `servidor.json` del dispositivo.

---

## El ciclo, ya funcionando

```
cada 5 minutos:              cada 15 segundos:
  leer el SCD41                consultar ordenes del usuario
  calcular el indice           aplicarlas y confirmarlas
  DECIDIR (reglas.py)
  mover los reles            cada hora:
  reportar al servidor         refrescar los umbrales
```

Fijate el orden: **primero acciona, despues reporta**. Si el servidor no
contesta, el ambiente igual quedo regulado.

---

## El contrato con el servidor

Cinco llamadas. Estan en `servidor.py`, una funcion por cada una.

| Metodo | Ruta | Para que |
|---|---|---|
| POST | `/api/devices/pair` | Darse de alta. Devuelve `device_uid` y `api_token`. **200** = vinculado, **202** = todavia nadie apreto "Conectar". Lleva tambien `session`: el codigo que el portal ya le dio al celular |
| GET | `/api/devices/{uid}/config` | Con que umbrales decidir, y en que modo esta |
| POST | `/api/devices/{uid}/measurements` | Subir la medicion y que actuadores quedaron encendidos |
| GET | `/api/devices/{uid}/commands/pending` | Ordenes manuales del usuario |
| POST | `/api/devices/{uid}/commands/{id}/executed` | Confirmar que se aplico una orden |

Todas menos `pair` llevan el header `X-Device-Token`.

### Probar la API sin la placa

Podes simular al equipo desde la terminal de tu PC:

```bash
curl -X POST http://localhost/piedra_castillo/public/api/devices/pair -H "Content-Type: application/json" -d "{\"mac\":\"AA:BB:CC:11:22:33\",\"firmware\":\"1.0.0\"}"
```

Con el `api_token` que te devuelve podes pedir la configuracion:

```bash
curl http://localhost/piedra_castillo/public/api/devices/TU_UID/config -H "X-Device-Token: TU_TOKEN"
```

---

## Probar las reglas sin la placa

`reglas.py` es **puro**: no importa `machine` ni `network`, asi que corre en
cualquier Python de escritorio. Se puede probar sin ESP32:

```python
import reglas

umbrales = {"temp_min": 18.0, "temp_max": 24.0,
            "hum_min": 40.0, "hum_max": 55.0, "co2_max": 900}

cfg = {"umbrales": umbrales,
       "margenes_alerta": {"temp": 2.0, "hum": 8.0, "co2": 250, "aire": 45},
       "aire_aromatizador": 60}

medicion = {"temperature": 27.0, "humidity": 47.0, "co2_ppm": 600}
medicion["air_quality_index"] = reglas.calcular_indice_aire(27.0, 47.0, 600, umbrales)

print(reglas.decidir(medicion, cfg))
# ({'fan': 'on', 'aromatizer': 'off', 'alert_led': 'on'}, 'temperatura alta, desvio grave')
```

Esa es la ventaja de tenerlo separado: la parte que mas importa se puede
probar sin hardware.

---

## Problemas frecuentes

### Los que puede resolver el cliente solo

| Sintoma | Que hacer |
|---|---|
| La pagina del portal no se abre sola | Abrir el navegador y entrar a `http://192.168.4.1`. Si tiene **DNS privado** activado en Android, desactivarlo: bloquea el portal |
| "Esta red no tiene internet" y el celular se sale | Es normal, esa red es el equipo. Elegir "mantener conexion" y apagar datos moviles un minuto |
| Cartel rojo: `La contraseña no es correcta` | Volver a escribirla usando el boton **Ver**. Mayusculas y minusculas importan |
| Cartel rojo: `No se encontro la red` | La red esta apagada o lejos. Acercar el equipo al router |
| Su red no aparece en la lista | Elegir **✎ Otra red** y escribir el nombre a mano (redes ocultas) |
| El celular no encuentra `EdenAir-Setup` | La placa ya esta conectada a un WiFi. Si ese WiFi ya no existe, la placa reabre el portal sola en menos de un minuto |

### Los de fabricacion (necesitan Thonny)

| Sintoma | Causa probable |
|---|---|
| `ImportError: no module named 'urequests'` | Falta instalar la libreria (paso 2) |
| `ImportError: no module named 'reglas'` | Subiste solo `main.py`. Hay que subir los 7 |
| `No se detecta el SCD41 en I2C` | Cableado, o los pines de `config.py` no coinciden |
| `No se pudo contactar al servidor` | La IP del servidor esta mal, o Apache apagado, o la PC en otra red. **Se corrige desde el portal**, no hace falta Thonny |
| Se queda en `Todavia nadie apreto 'Conectar'` | Correcto: entra a la web y apreta Conectar. Reintenta solo cada 15 s |
| El actuador funciona al reves | Cambia `RELES_INVERTIDOS` en `config.py` |
| Aprieto un boton del panel y no pasa nada | Ese actuador esta en `None` en `config.py`. La orden queda pendiente a proposito: el equipo no confirma algo que no hizo |

---

## Si cambias una regla

Las reglas viven en `reglas.py`, pero **los numeros los manda el servidor**.
Segun que quieras cambiar:

- **Un umbral de un ambiente** (que el aula tolere hasta 26 °C):
  desde la web, en `/panel/ambientes`. No se toca el firmware.
- **Un margen de alerta o el umbral del aromatizador**:
  en `app/Services/DeviceConfigService.php`, las constantes de arriba.
  Tampoco se toca el firmware.
- **La regla en si** (que el aromatizador dependa del CO2 y no del indice):
  ahi si, `reglas.py`, y hay que volver a subir el archivo.

> Si cambias la formula del indice de aire en `reglas.py`, cambiala tambien en
> `MeasurementService::calcularIndiceAire()`. Las dos tienen que dar el mismo
> numero: una decide y la otra es la que se muestra en el panel. Estan
> verificadas como identicas, incluido el redondeo (ver `_redondear`).
