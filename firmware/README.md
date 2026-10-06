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
| Leer temperatura, humedad y CO2 | ESP32 | `sensor.py` |
| Leer la calidad de aire | ESP32 | `aire.py` |
| **Decidir que actuador prender** | **ESP32** | **`reglas.py`** |
| Emitir la orden infrarroja | ESP32 | `infrarrojo.py` |
| Mover los reles y los LEDs | ESP32 | `actuadores.py` |
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
| `config.py` | Direccion del servidor, pines, tiempos, credenciales del AP | **Si**, es lo unico que se edita por placa |
| `main.py` | El ciclo principal. Orquesta, no calcula | Rara vez |
| `reglas.py` | **Las decisiones.** Puro: recibe numeros, devuelve decision | Si cambian las reglas |
| `sensor.py` | Lee el SCD41 por I2C (temperatura, humedad, CO2) | Si cambias de sensor |
| `aire.py` | Lee el MQ-135 por ADC (calidad de aire) | Si cambias de sensor |
| `infrarrojo.py` | Emite la trama de 38 kHz y espera su confirmacion | Si cambias el emisor o el receptor |
| `actuadores.py` | Mueve los pines de los reles y de los LEDs | Si cambias el hardware |
| `red.py` | WiFi + portal de configuracion | Casi nunca |
| `servidor.py` | Las llamadas HTTP a la API | Si cambia la API |

Son **nueve archivos**. Estan separados a proposito: podes cambiar de sensor sin
tocar las reglas, o afinar las reglas sin saber nada de I2C.

### La regla de oro de la logica

EdenAir mide **cuatro** variables pero solo puede **actuar** sobre **dos**:

| Variable | Que hace el equipo | Con que |
|---|---|---|
| Temperatura | **ACTUA** | Ordena el aire acondicionado, por infrarrojo |
| Humedad | **ACTUA** | Enciende el atomizador, y solo para SUBIRLA |
| CO2 | **AVISA** | LED rojo + mensaje en el panel |
| Calidad de aire | **AVISA** | LED rojo + mensaje en el panel |

El CO2 y la calidad de aire solo avisan porque el sistema **no renueva el aire**:
no hay extractor ni ventilacion. Encender algo no cambiaria el numero. Y no es
una limitacion de la maqueta: es una decision de producto. EdenAir no comanda
equipos que el usuario no tenga instalados, asi que en esos casos decide la
persona y el equipo se lo dice.

### La maqueta no define la logica

El cooler de 30 mm **representa** un aire acondicionado. En la maqueta gira como
indicador y no baja la temperatura; en una casa, del otro lado hay un aire
acondicionado que si enfria. **El codigo es el del producto real.** Por eso la
histeresis de apagado a 24 grados esta implementada aunque en la maqueta la
temperatura no baje sola: es la regla del producto.

Si en la maqueta el aire queda encendido porque la temperatura no baja, esta
bien: es exactamente lo que haria el sistema real mientras el ambiente siga
caliente. Para la demo se saca la fuente de calor y baja sola. **No hay ningun
temporizador de compensacion, ninguna lectura simulada y ningun modo "demo".**

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

### El mapa de pines completo

Cuando esta todo armado, `config.py` queda asi:

| GPIO | Componente | Notas |
|---|---|---|
| 21 | SDA — SCD41 | I2C, alimentado con **3V3** |
| 22 | SCL — SCD41 | I2C |
| 34 | MQ-135 (AO) | Solo entrada. **Siempre por el divisor 10k/10k** |
| 26 | Rele IN1 — aire acondicionado (cooler) | **Activo en bajo** |
| 27 | Rele IN2 — atomizador | **Activo en bajo** |
| 25 | Emisor IR (KY-005) | Trama de 38 kHz, por transistor |
| 33 | Receptor IR (VS1838B) | Alimentado con **3V3** |
| 14 | LED verde | Resistencia 220-330 ohm |
| 16 | LED rojo | Resistencia 220-330 ohm |
| 17 | LED azul | Resistencia 220-330 ohm |

Reglas electricas que **no** se negocian:

- **Los reles son activos en bajo.** El firmware los inicializa con
  `Pin(n, Pin.OUT, value=1)`, o sea apagados **en el mismo momento** en que el
  pin pasa a ser salida. Con `Pin(n, Pin.OUT)` a secas hay una ventana en la que
  la salida vale 0 y el rele pega un golpe solo al arrancar.
- **El SCD41 y el receptor IR van a 3V3**, nunca a 5 V.
- **El MQ-135 se alimenta con 5 V** (tiene calefactor interno), pero su `AO`
  **nunca** va directo a un GPIO: entra por el divisor resistivo.
- La ESP32 se alimenta por USB; el resto (MQ-135, reles, cooler, atomizador)
  desde la fuente externa de 5 V / 3 A, con **GND comun en estrella**.

### Armando por partes

Cualquier pin de `config.py` puede quedar en `None`:

```python
PIN_RELE_AIRE = None
PIN_MQ135 = None
PIN_IR_EMISOR = None
PIN_LED_VERDE = None
```

`None` significa "todavia no lo arme". El equipo **no toca ese pin** y **no le
informa nada al servidor sobre ese componente**, asi el panel no muestra
encendido algo que no existe. Con **solo el SCD41** el equipo ya se vincula,
mide y reporta. En la consola vas a ver:

```
Sin actuadores conectados: el equipo solo mide y reporta.
Sin MQ-135: la calidad de aire se calcula con la formula de respaldo.
```

Cuando armes uno, le pones su numero de pin y volves a subir `config.py`. Nada
mas cambia.

### Los dos sensores

| | SCD41 | MQ-135 |
|---|---|---|
| Mide | Temperatura, humedad y CO2 | Calidad de aire |
| Como | I2C, sensor NDIR real | ADC, resistencia quimica |
| Alimentacion | 3V3 | 5 V (calefactor) |
| Listo en | Segundos | **5 minutos de warm-up** |
| Se muestra como | Grados, % y ppm | Indice 0-100, **nunca en ppm** |

El MQ-135 no da ppm confiables sin calibracion de laboratorio, asi que EdenAir
lo convierte a un indice relativo de 0 a 100 con su etiqueta (Excelente, Buena,
Aceptable, Mala). Los dos extremos de esa conversion se calibran una vez por
placa con `MQ135_CRUDO_LIMPIO` y `MQ135_CRUDO_SUCIO` en `config.py`.

**El MQ-135 y el atomizador se pelean.** La niebla del atomizador es vapor de
agua, pero el sensor la lee como contaminante: sin proteccion, el equipo diria
"aire malo" justo cuando esta humidificando, y como el atomizador se enciende
por humedad baja quedaria realimentandose. Por eso, mientras el atomizador esta
encendido **y 90 segundos despues**, se ignora al MQ-135: se congela el ultimo
valor bueno y no se dispara ninguna alerta de aire. Fisicamente ademas van en
paredes opuestas y el MQ con visera, pero el enmascarado por software es la
proteccion real.

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

Despues revisa los pines (`PIN_RELE_AIRE`, `PIN_MQ135`, `PIN_I2C_SDA`, etc.)
segun como tengas armado el circuito, y deja en `None` lo que todavia no
armaste.

---

## Subir el codigo con Thonny

1. **Ver → Archivos** (para tener el panel de archivos)
2. Arriba vas a ver tu PC; abajo, **Dispositivo MicroPython**
3. Navega en el panel de arriba hasta esta carpeta `firmware/`
4. Selecciona **los 9 archivos `.py`**
5. Click derecho → **Subir a /**

Deberian quedar los 9 en el panel del dispositivo.

> **Importante:** hay que subir los 9, no solo `main.py`. Si falta uno, la
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
cada 30 segundos:              cada 15 segundos:
  leer el SCD41                  consultar ordenes del usuario
  leer el MQ-135                 aplicarlas y confirmarlas
  DECIDIR (reglas.py)
  emitir la orden IR           cada hora:
  mover reles y LEDs             refrescar los umbrales
  reportar al servidor
```

Fijate el orden: **primero acciona, despues reporta**. Si el servidor no
contesta, el ambiente igual quedo regulado.

Eran 5 minutos. En una feria de ciencias hay que poder **ver la reaccion en
vivo**: si el ciclo tarda cinco minutos, el jurado se va antes de que el cooler
arranque. Treinta segundos alcanzan para verla y no llenan la base: 2.880
mediciones por dia, contra 10.800 si fuera cada 8. Menos de 5 no tiene
sentido: el SCD41 no entrega un dato nuevo antes.

### Las protecciones

Ninguna de estas es un parche para la maqueta: todas existen en el producto real.

| Proteccion | Cuanto | Para que |
|---|---|---|
| Tiempo minimo del rele | 30 s | Que no traquetee cuando el valor queda justo en el umbral |
| Limite de tramas IR | La misma orden, 1 cada 60 s. Una orden distinta sale siempre | No saturar el receptor ni el ambiente, sin que el rele se mueva nunca sin trama |
| Espera de confirmacion IR | hasta 1 s | Saber si la orden llego, sin colgar el ciclo |
| Warm-up del MQ-135 | 300 s | Su lectura no sirve hasta que el calefactor este listo |
| Enmascarado del MQ-135 | mientras atomiza + 90 s | La niebla ensucia la lectura |
| Ciclo del atomizador | 60 s ON / 120 s OFF | No encharcar ni vaciar el deposito |

Prioridad de decisiones: **calidad de aire y CO2 (avisar) → temperatura →
humedad**.

### La cadena infrarroja

La orden del aire acondicionado **no** sale por el cable del rele: sale por
infrarrojo, igual que con un control remoto.

```
ESP32 --(trama NEC 38 kHz)--> receptor VS1838B --> rele --> cooler + LED azul
```

El limite de 60 s entre tramas **solo frena repeticiones**. Repetir
"encender" a un aire que ya lo recibio no aporta nada, asi que eso espera.
Pero pasar de encender a apagar (o al reves) es informacion nueva y sale en el
momento, sin mirar el reloj. Si no fuera asi, el rele (que puede conmutar cada
30 s) moveria el aire sin que haya salido trama, y el aire dejaria de estar
comandado por infrarrojo.

Despues de emitir, el firmware espera **hasta 1 segundo** la confirmacion del
receptor:

- **Confirma** → prende el aire y reporta `ir_confirmado = true`.
- **No confirma** → **prende el aire igual** y reporta `ir_confirmado = false`.
  El panel muestra *"Orden enviada, sin confirmacion IR"*.

La confirmacion **no es una condicion**: una demostracion no se puede caer
porque un LED infrarrojo quedo mal apuntado. Pero el dato viaja al panel,
porque es justamente el que delata que la cadena esta fallando.

---

## El contrato con el servidor

Cinco llamadas. Estan en `servidor.py`, una funcion por cada una.

| Metodo | Ruta | Para que |
|---|---|---|
| POST | `/api/devices/pair` | Darse de alta. Devuelve `device_uid` y `api_token`. **200** = vinculado, **202** = todavia nadie apreto "Conectar". Lleva tambien `session`: el codigo que el portal ya le dio al celular |
| GET | `/api/devices/{uid}/config` | Con que umbrales decidir, y en que modo esta |
| POST | `/api/devices/{uid}/measurements` | Subir la medicion, que actuadores quedaron encendidos y el diagnostico del equipo |
| GET | `/api/devices/{uid}/commands/pending` | Ordenes manuales del usuario |
| POST | `/api/devices/{uid}/commands/{id}/executed` | Confirmar que se aplico una orden |

Todas menos `pair` llevan el header `X-Device-Token`.

### Que manda el equipo en cada medicion

```json
{
  "temperature": 27.4,
  "humidity": 36.2,
  "co2_ppm": 1180,
  "air_quality_index": 62,
  "air_quality_source": "sensor",
  "actuadores": {
    "fan": "on", "aromatizer": "on", "alert_led": "on",
    "green_led": "off", "blue_led": "on"
  },
  "motivo": "calidad de aire mala, CO2 alto, temperatura alta, humedad baja",
  "diagnostico": {
    "estado_aire": "ok",
    "ir_confirmado": false,
    "avisos": ["ventilar", "aire_acondicionado", "ir_sin_confirmar", "humidificando"]
  }
}
```

Dos cosas que conviene entender de este JSON:

- **`fan`, `aromatizer` y `alert_led` no cambiaron de nombre.** Son los nombres
  internos desde la primera version y siguen igual aunque su etiqueta visible
  sea otra (`fan` = aire acondicionado, `aromatizer` = humidificador). Cambiarlos
  romperia todo el historial ya guardado.
- **Los `avisos` son codigos, no frases.** El texto que ve la persona lo pone la
  web (`PanelService::AVISOS`), asi se puede reescribir un mensaje sin
  reprogramar la placa.

Y que baja el equipo en su configuracion (`GET .../config`): `umbrales` (donde
ENCIENDE cada regla), `apagado` (donde APAGA: la histeresis ya calculada por el
servidor), `critico` (el CO2 que ya es grave), `tiempos` (las protecciones) e
`intervalos`.

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

cfg = {
    "umbrales": {"temp_min": 20.0, "temp_max": 26.0, "hum_min": 40.0,
                 "hum_max": 60.0, "co2_max": 1000, "aire_min": 70},
    "apagado":  {"temp": 24.0, "hum": 48.0, "co2": 850, "aire": 75},
    "critico":  {"co2": 1400, "co2_salida": 1100},
    "tiempos":  {"atomizador_on": 60, "atomizador_off": 120},
}

d = reglas.Decisor()

# Ambiente caliente y seco, con el aire cargado.
medicion = {"temperature": 27.0, "humidity": 36.0, "co2_ppm": 1200,
            "air_quality_index": 62, "aire_confiable": True}

print(d.decidir(medicion, cfg, ahora=0))
# ({'fan': 'on', 'aromatizer': 'on', 'alert_led': 'on',
#   'green_led': 'off', 'blue_led': 'on'},
#  ['ventilar', 'aire_acondicionado', 'humidificando'],
#  'calidad de aire mala, CO2 alto, temperatura alta, humedad baja')

# La misma temperatura, un rato despues: 25 grados NO apaga el aire.
# Esa es la histeresis: enciende a 26, corta recien abajo de 24.
medicion["temperature"] = 25.0
print(d.decidir(medicion, cfg, ahora=10)[0]["fan"])   # -> 'on'

medicion["temperature"] = 23.5
print(d.decidir(medicion, cfg, ahora=20)[0]["fan"])   # -> 'off'
```

Esa es la ventaja de tenerlo separado: la parte que mas importa se puede
probar sin hardware. `Decisor` es un objeto y no una funcion suelta porque
tiene que **recordar** en que estado quedo cada regla (la histeresis) y en que
momento del ciclo esta el atomizador. Igual sigue siendo puro: el momento
actual se le pasa como un numero (`ahora`), no lo consulta.

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
| `ImportError: no module named 'reglas'` | Subiste solo `main.py`. Hay que subir los 9 |
| `ImportError: no module named 'aire'` o `'infrarrojo'` | Son los dos archivos nuevos. Subilos tambien |
| `No se detecta el SCD41 en I2C` | Cableado, o los pines de `config.py` no coinciden |
| `No se pudo contactar al servidor` | La IP del servidor esta mal, o Apache apagado, o la PC en otra red. **Se corrige desde el portal**, no hace falta Thonny |
| Se queda en `Todavia nadie apreto 'Conectar'` | Correcto: entra a la web y apreta Conectar. Reintenta solo cada 15 s |
| El rele funciona al reves | Cambia `RELES_INVERTIDOS` en `config.py` |
| Un rele pega un golpe al enchufar la placa | No deberia pasar: se inicializa apagado desde el constructor. Si pasa, el rele no es activo en bajo -> `RELES_INVERTIDOS = False` |
| Aprieto un boton del panel y no pasa nada | O ese actuador esta en `None` en `config.py`, o el rele todavia esta cumpliendo sus 30 s de tiempo minimo. La orden queda pendiente a proposito: el equipo no confirma algo que no hizo |
| El panel dice `Sensor de aire calentando` y no cambia | Normal durante los primeros 5 minutos. Si sigue, el MQ-135 no esta llegando al GPIO 34 |
| La calidad de aire se queda clavada en 0 o en 100 | Falta el divisor 10k/10k, o hay que recalibrar `MQ135_CRUDO_LIMPIO` / `MQ135_CRUDO_SUCIO` con lo que devuelve `medidor.crudo()` |
| El panel dice `Orden enviada, sin confirmacion IR` | El emisor y el receptor no se ven. Enfrentalos, sacale lo que tengan en el medio, revisa que el receptor este a **3V3** |
| El cooler queda encendido y no se apaga | **Correcto** si la temperatura no bajo de 24 grados. Saca la fuente de calor y espera: no hay ningun apagado por tiempo, a proposito |

---

## Si cambias una regla

Las reglas viven en `reglas.py`, pero **los numeros los manda el servidor**.
Segun que quieras cambiar:

- **Un umbral de un ambiente** (que el aula tolere hasta 28 °C, o que avise con
  la calidad de aire debajo de 65): desde la web, en `/panel/ambientes`. Ahi se
  editan tambien las histeresis y el CO2 critico, en *Ajustes avanzados*.
  No se toca el firmware.
- **Un tiempo o una proteccion** (el minimo del rele, el warm-up del MQ-135, el
  ciclo del atomizador): en `app/Services/DeviceConfigService.php`, las
  constantes de arriba. Tampoco se toca el firmware: viajan en el bloque
  `tiempos` de la configuracion.
- **Un mensaje del panel**: en `app/Services/PanelService.php`, la constante
  `AVISOS`. El firmware manda codigos, no frases.
- **La regla en si** (que la humedad alta encienda algo, que el CO2 accione en
  vez de avisar): ahi si, `reglas.py`, y hay que volver a subir el archivo.

> La formula de `calcular_indice_aire()` ya **no** es la fuente normal del
> indice: desde que hay MQ-135, el numero que vale es el medido y el servidor lo
> guarda tal cual. La formula quedo como respaldo para cuando el sensor no esta
> o esta calentando. Si igual la cambias, cambiala tambien en
> `MeasurementService::calcularIndiceAire()`. Las dos tienen que dar el mismo
> numero: una decide y la otra es la que se muestra en el panel. Estan
> verificadas como identicas, incluido el redondeo (ver `_redondear`).
