# EdenAir — Cambio de lógica de control

**Fecha:** 8 de septiembre de 2026
**Alcance:** firmware del ESP32 (MicroPython) + web (CodeIgniter 4) + base de datos

---

## 0. El resumen en tres frases

EdenAir mide **cuatro** variables pero solo puede **actuar** sobre **dos**:
temperatura y humedad. Sobre el CO₂ y la calidad de aire únicamente **avisa**,
porque el sistema no renueva el aire: no hay extractor ni ventilación, así que
encender algo no cambiaría el número y esa decisión la toma la persona.

La lógica quedó escrita **como funcionaría el producto real**, no adaptada a lo
que la maqueta puede hacer. El cooler de 30 mm representa un aire acondicionado:
en la maqueta gira como indicador, en una casa del otro lado hay un equipo que
enfría de verdad. La cadena de decisión, los umbrales, las histéresis, los
tiempos y los avisos son los mismos en los dos casos.

No se agregó **ningún** parche de compensación: ni temporizadores para apagar el
cooler "porque igual no baja la temperatura", ni lecturas simuladas, ni modos
"demo". Si en la maqueta el aire queda encendido mientras el ambiente siga
caliente, está bien: es exactamente lo que haría el sistema real.

---

## 1. Archivos tocados

### Firmware (ESP32, MicroPython)

| Archivo | Qué se hizo | Resumen |
|---|---|---|
| `firmware/config.py` | modificado | Mapa de pines real (10 GPIO), calibración del MQ-135, tiempos de protección y ciclo de medición de 300 s → 8 s |
| `firmware/reglas.py` | modificado | Las tres reglas viejas se reemplazaron por la lógica nueva con histéresis, ciclos y avisos; ahora es un objeto `Decisor` con memoria |
| `firmware/actuadores.py` | modificado | Maneja 5 salidas (2 relés + 3 LEDs), arranca los relés apagados desde el constructor, respeta 30 s mínimos por estado y dispara la cadena infrarroja |
| `firmware/main.py` | modificado | Orquesta los dos sensores, el enmascarado del MQ-135 y el diagnóstico; cada tarea del ciclo tiene su propio reloj |
| `firmware/servidor.py` | modificado | `enviar_medicion()` ahora manda también el origen del índice de aire y el bloque `diagnostico` |
| `firmware/aire.py` | **creado** | Lectura del MQ-135 por ADC: warm-up, enmascarado y conversión a índice 0–100 |
| `firmware/infrarrojo.py` | **creado** | Emite la trama NEC de 38 kHz y espera la confirmación del receptor |
| `firmware/README.md` | modificado | Documentación al día: 9 archivos, mapa de pines, protecciones, cadena IR y contrato con el servidor |
| `firmware/sensor.py` | **sin tocar** | El manejo del SCD41 está resuelto a partir de bugs reales. No se tocó ni una línea |
| `firmware/red.py` | **sin tocar** | Todo el flujo de vinculación quedó igual |

### Web (CodeIgniter 4)

| Archivo | Qué se hizo | Resumen |
|---|---|---|
| `app/Database/Migrations/2026-09-08-000001_ActualizarLogicaControl.php` | **creado** | Agrega 6 columnas a `spaces`, 1 a `measurements` y 5 a `device_states` |
| `app/Models/SpaceModel.php` | modificado | 6 campos nuevos en la lista blanca (umbrales de control) |
| `app/Models/MeasurementModel.php` | modificado | Campo `air_quality_source` en la lista blanca |
| `app/Models/DeviceStateModel.php` | modificado | 5 campos nuevos: los dos LEDs y el diagnóstico del equipo |
| `app/Services/EnvironmentPresetService.php` | modificado | Constante `CONTROL` con los valores de la lógica nueva y método `control()` |
| `app/Services/DeviceConfigService.php` | modificado | Manda al equipo los bloques `apagado`, `critico` y `tiempos`; se fueron las constantes viejas |
| `app/Services/MeasurementService.php` | modificado | Respeta el índice medido por el equipo y guarda de dónde salió |
| `app/Services/CommandService.php` | modificado | Guarda los dos LEDs nuevos y el diagnóstico, sin ensuciar el historial |
| `app/Services/PanelService.php` | modificado | Bloques `leds()` y `mensajes()`, reglas nuevas con etiqueta actúa/avisa, umbrales leídos del ambiente |
| `app/Services/DevicePairingService.php` | modificado | El estado inicial de un equipo recién vinculado incluye los campos nuevos |
| `app/Controllers/AmbientesController.php` | modificado | Lee y valida los 6 umbrales nuevos con `revisarRangos()` y `revisarControl()` |
| `app/Controllers/Api/DeviceApiController.php` | modificado | Documentación del contrato de `measurements` al día |
| `app/Views/panel.php` | modificado | Tira de mensajes, bloque de los 3 LEDs, reglas con chip actúa/avisa y origen del índice de aire |
| `app/Views/ambientes/editar.php` | modificado | Sección plegable "Ajustes avanzados" con los 6 umbrales nuevos |
| `app/Views/inicio.php` | modificado | Las 4 reglas de la portada reescritas; se fue todo texto que prometía ventilación |
| `app/Views/compra_mercadopago.php` | modificado | "Automatización de ventilación…" → "de aire acondicionado y humidificación" |
| `app/Views/portfolio.php` | modificado | Tres menciones corregidas: el sistema avisa, no ventila |
| `public/CSS/dashboard.css` | modificado | Estilos de mensajes, LEDs, chip actúa/avisa y ajustes avanzados |
| `public/JS/panel-vivo.js` | modificado | Refresca mensajes y LEDs; el panel pregunta cada 10 s en vez de 30 |
| `public/JS/eden-core-3d.js` | modificado | La métrica `ventilador` pasó a `aire_acondicionado` |
| `mysql_setup.sql` | modificado | El esquema de instalación limpia incluye las 12 columnas nuevas |
| `README.md` | modificado | Dos líneas de actuadores actualizadas |
| `CAMBIOS-LOGICA.md` | **creado** | Este documento |

---

## 2. La lógica nueva, en una tabla

| Variable | Enciende / avisa cuando | Se apaga / se levanta cuando | Qué actuador mueve | LED | ¿Actúa o avisa? |
|---|---|---|---|---|---|
| **Temperatura** | supera **26 °C** (máx. del ambiente) | baja de **24 °C** | Aire acondicionado, por infrarrojo | 🔵 Azul | **ACTÚA** |
| **Humedad baja** | baja de **40 %** (mín. del ambiente) | supera **48 %** | Atomizador ultrasónico, en ciclos 60 s ON / 120 s OFF | — | **ACTÚA** |
| **Humedad alta** | supera **60 %** (máx. del ambiente) | vuelve al rango | **ninguno** | — | **AVISA** |
| **CO₂** | supera **1000 ppm** | baja de **850 ppm** | **ninguno** | 🔴 Rojo | **AVISA** |
| **CO₂ crítico** | supera **1400 ppm** | baja de **1100 ppm** | **ninguno** | 🔴 Rojo | **AVISA** (más fuerte) |
| **Calidad de aire** | baja de **70/100** | supera **75/100** | **ninguno** | 🔴 Rojo | **AVISA** |
| **Todo en rango** | — | — | — | 🟢 Verde | monitoreo pasivo |

**Prioridad de decisión:** calidad de aire y CO₂ (avisar) → temperatura → humedad.

**Los valores son los de fábrica.** Todos salen del ambiente configurado y se
editan desde `/panel/ambientes` sin reprogramar la placa. Los umbrales de
apagado no se escriben a mano: el servidor los calcula restando (o sumando) la
histéresis, así siguen automáticamente al valor que edite el usuario.

### Por qué existe la histéresis

Una regla con un solo umbral oscila. Con "prender arriba de 26" a secas, a 26,1
grados el aire arranca, a 25,9 se corta, y el relé traquetea sin parar hasta
gastarse. Por eso cada regla tiene **dos** umbrales: uno para encender y otro,
más adentro, para apagar. Entre los dos, se sostiene lo que ya estaba.

---

## 3. Antes y después

| # | Antes | Ahora | Por qué |
|---|---|---|---|
| 1 | El ventilador se prendía por temperatura **o** humedad alta **o** CO₂ alto | El aire acondicionado se prende **solo por temperatura** | No hay ventilación: ni la humedad alta ni el CO₂ pueden prender nada útil |
| 2 | El aromatizador se prendía cuando **bajaba la calidad de aire** | El atomizador se prende **solo por humedad baja** | El atomizador **empeora** la lectura del MQ-135: el sensor lee el vapor como contaminante y el sistema se realimentaría solo, quedando encendido sin parar |
| 3 | Un único `alert_led` que se prendía por "desvío grave" | **Tres LEDs**: verde (normal), azul (orden de aire) y rojo (alerta) | Un solo LED no distingue "está trabajando" de "hacé algo vos" |
| 4 | El relé del aire se activaba directo | Primero se **emite una trama infrarroja de 38 kHz**, el receptor la confirma, y recién ahí se activa el relé | Es la cadena real: un aire acondicionado se comanda por control remoto, no cortándole la corriente |
| 5 | La calidad de aire se **calculaba** con una fórmula sobre temperatura, humedad y CO₂ | La **mide el MQ-135** por ADC; la fórmula quedó como respaldo | Había un sensor físico en la maqueta que el código ignoraba por completo |
| 6 | El servidor podía recalcular el índice de aire | El servidor **respeta** el valor que manda el equipo y guarda de dónde salió | Si el panel mostrara un número distinto del que movió los actuadores, no se entendería nada |
| 7 | Umbral único: se prendía y apagaba en el mismo número | **Histéresis** en las cuatro reglas | Sin margen, el relé traquetea y se gasta |
| 8 | El atomizador quedaba encendido fijo | **Ciclo de 60 s encendido / 120 s apagado** | A full encharca el ambiente y vacía el depósito en minutos; además el agua tarda en mezclarse con el aire |
| 9 | El MQ-135 no existía en el código | Además de leerlo: **warm-up de 300 s** y **enmascarado mientras atomiza + 90 s** | Los primeros 5 minutos su lectura es basura, y la niebla del atomizador la ensucia |
| 10 | Medición cada **300 s** (5 minutos) | Medición cada **8 s** | En una feria hay que poder ver la reacción en vivo, no dentro de cinco minutos |
| 11 | El ciclo principal dormía lo que durara el intervalo de comandos (15 s) | Cada tarea tiene **su propio reloj**; la vuelta dura 1 s | Pedir mediciones cada 8 s no servía de nada si el ciclo dormía 15 |
| 12 | `Pin(n, Pin.OUT)` y después escribir "apagado" | `Pin(n, Pin.OUT, value=1)`: nace apagado | Había una ventana en la que la salida valía 0 y el relé (activo en bajo) pegaba un golpe solo al enchufar |
| 13 | Los relés podían conmutar en cualquier momento | **30 s mínimos** en cada estado | Protege el contacto del relé y evita el traqueteo |
| 14 | `PIN_VENTILADOR`, `PIN_AROMATIZADOR`, `PIN_LED_ALERTA`, los tres en `None` | Mapa de **10 pines reales**, cualquiera anulable con `None` | Los pines viejos no correspondían a nada del hardware armado |
| 15 | El panel decía "Encender ventilación" y "Encender aromatizador" | Cada regla lleva un chip **actúa** o **avisa** | El panel no puede prometer algo que el equipo no puede hacer |
| 16 | El panel no explicaba nada | Tira de **mensajes** del equipo, traducidos desde códigos | Un número rojo sin instrucción no le sirve a nadie |
| 17 | Los umbrales editables eran 5 | Son **11**: se sumaron las 4 histéresis, la calidad de aire mínima y el CO₂ crítico | Los números con los que decide el equipo tienen que poder verse y cambiarse |
| 18 | El panel preguntaba cada 30 s | Cada **10 s** | Con mediciones cada 8 s, mirando cada medio minuto se perdían tres de cada cuatro |

---

## 4. Lo que se eliminó, y por qué

| Qué se eliminó | Dónde vivía | Por qué |
|---|---|---|
| Regla "ventilador por humedad alta o CO₂ alto" | `reglas.py` | No hay con qué ventilar. Prender el cooler no baja el CO₂ |
| Regla "aromatizador por calidad de aire baja" | `reglas.py` | Está conceptualmente al revés: el atomizador empeora la lectura del MQ-135, no la mejora |
| Regla "LED de alerta por desvío grave" (un solo LED, márgenes extra) | `reglas.py` | Reemplazada por los tres LEDs con significados distintos |
| Constantes `MARGEN_ALERTA_TEMP`, `MARGEN_ALERTA_HUM`, `MARGEN_ALERTA_CO2` | `DeviceConfigService.php` | Eran los márgenes del LED único. La severidad ahora sale de los umbrales del ambiente |
| Constante `AIRE_AROMATIZADOR` (60) | `DeviceConfigService.php` | Era el umbral de la regla invertida |
| Constante `AIRE_ALERTA` (45) | `DeviceConfigService.php` | Reemplazada por `min_air_quality`, editable por ambiente |
| Constantes `MARGEN_CO2`, `AIRE_MALO`, `AIRE_REGULAR` | `PanelService.php` | El panel pintaba con números propios que podían no coincidir con los del equipo. Ahora lee los del ambiente |
| Pines `PIN_VENTILADOR`, `PIN_AROMATIZADOR`, `PIN_LED_ALERTA` | `config.py` | Reemplazados por el mapa real de 10 pines |
| Textos de "ventilación", "renovar el aire" y "el aromatizador mejora la calidad de aire" | `inicio.php`, `portfolio.php`, `compra_mercadopago.php`, `PanelService.php`, `README.md` | El sistema no hace eso. Prometerlo en la web es mentir |

**Nada se eliminó de lo que ya funcionaba.** Siguen intactos: la arquitectura de
módulos, la decisión local (el equipo regula sin internet), los umbrales que
manda la web, el modo automático/manual, todo `sensor.py` (incluido
`rearrancar()`, la espera de 1000 ms y el descarte de la primera lectura),
`_redondear()`, todo el flujo de vinculación y los nombres internos `fan`,
`aromatizer` y `alert_led` en la base y la API.

### Los nombres internos que NO se tocaron

| Nombre interno | Etiqueta visible ahora |
|---|---|
| `fan` / `fan_state` | Aire acondicionado |
| `aromatizer` / `aromatizer_state` | Humidificador / atomizador |
| `alert_led` / `alert_led_state` | Luz de alerta (LED rojo) |

Renombrarlos habría roto el historial ya guardado en `device_commands` y las
migraciones. Lo que cambió es su lógica y cómo se los llama en pantalla.

---

## 5. Lo que se agregó de cero

| Qué | Dónde | Detalle |
|---|---|---|
| **MQ-135** | `firmware/aire.py` | ADC en GPIO 34, `ATTN_11DB`, 12 bits, promedio de 16 muestras; conversión a índice 0–100; nunca se muestra en ppm |
| **Warm-up del MQ-135** | `firmware/aire.py` | 300 s desde el encendido. Durante ese rato no dispara alertas y la web muestra "Sensor de aire calentando" |
| **Enmascarado del MQ-135** | `firmware/aire.py` | Mientras el atomizador está encendido y 90 s después, se congela el último valor válido. La web muestra "Medición de aire en pausa por humidificación" |
| **Índice medido vs calculado** | `aire.py` + `MeasurementService` | La columna `air_quality_source` guarda si el número lo midió el sensor o lo estimó la fórmula. El panel lo aclara debajo del índice |
| **Cadena infrarroja** | `firmware/infrarrojo.py` | Trama NEC de 38 kHz por PWM en GPIO 25; receptor en GPIO 33 con interrupción; dos códigos de orden (encender / apagar) |
| **Confirmación IR con límite** | `infrarrojo.py` + `actuadores.py` | Espera hasta 1 s. Si confirma, `ir_confirmado = true`. Si no, **prende igual** y reporta `false`; la web muestra "Orden enviada, sin confirmación IR" |
| **Límite de tramas IR** | `infrarrojo.py` | Máximo una cada 60 s |
| **Tres LEDs** | `config.py`, `actuadores.py`, `reglas.py` | Verde GPIO 14, rojo GPIO 16, azul GPIO 17 |
| **Histéresis en las 4 reglas** | `reglas.py` + `DeviceConfigService` | El servidor manda los umbrales de apagado ya calculados |
| **Ciclo del atomizador** | `reglas.py` | 60 s ON / 120 s OFF mientras la humedad siga baja |
| **Tiempo mínimo del relé** | `actuadores.py` | 30 s en cada estado antes de volver a conmutar |
| **Arranque limpio de los relés** | `actuadores.py` | `Pin(n, Pin.OUT, value=1)`: nacen apagados |
| **Sistema de avisos** | `reglas.py` → `PanelService::AVISOS` | El firmware manda **códigos**, la web pone el texto. Se puede reescribir un mensaje sin reprogramar la placa |
| **Bloque de mensajes en el panel** | `panel.php`, `dashboard.css`, `panel-vivo.js` | Tira de avisos que se actualiza sola |
| **Bloque de los 3 LEDs en el panel** | `panel.php`, `dashboard.css`, `panel-vivo.js` | Para que la maqueta y la pantalla digan lo mismo |
| **6 umbrales editables nuevos** | `spaces` + formulario | Calidad de aire mínima, 4 histéresis y CO₂ crítico, en "Ajustes avanzados" |
| **Validación de coherencia** | `AmbientesController::revisarControl()` | Impide que el punto de apagado quede fuera del rango, lo que dejaría al actuador encendido para siempre |

---

## 6. Mapa de pines final

| GPIO | Componente | Notas |
|---|---|---|
| **21** | SDA — SCD41 | I2C. Alimentado con **3V3** |
| **22** | SCL — SCD41 | I2C |
| **34** | MQ-135 (AO) | Solo entrada. **Siempre** por divisor 20 kΩ / 10 kΩ |
| **26** | Relé IN1 — aire acondicionado (cooler) | **Activo en bajo** |
| **27** | Relé IN2 — atomizador | **Activo en bajo** |
| **25** | Emisor IR (KY-005) | Trama de 38 kHz, por transistor |
| **33** | Receptor IR (VS1838B) | Alimentado con **3V3** |
| **14** | LED verde | Resistencia 220–330 Ω |
| **16** | LED rojo | Resistencia 220–330 Ω |
| **17** | LED azul | Resistencia 220–330 Ω |

**Reglas eléctricas que el código respeta:**

- Los relés son **activos en bajo** y se inicializan apagados desde el propio
  constructor (`Pin(n, Pin.OUT, value=1)`), para que no peguen un golpe al arrancar.
- El **SCD41 y el receptor IR van a 3V3**, nunca a 5 V.
- El **MQ-135 se alimenta con 5 V** (tiene calefactor), pero su `AO` **nunca**
  va directo a un GPIO.
- La ESP32 se alimenta por USB; el resto desde la fuente externa de 5 V / 3 A,
  con **GND común en estrella**.
- Se mantiene el flag `RELES_INVERTIDOS`.
- **Cualquier pin puede quedar en `None`**: significa "todavía no lo armé". El
  equipo no toca ese pin ni le informa nada al servidor sobre ese componente.

---

## 7. Qué quedó pendiente o necesita una decisión tuya

### 1. Calibrar el MQ-135 (obligatorio antes de la feria)

En `config.py` quedaron dos valores **de referencia**, no medidos en tu placa:

```python
MQ135_CRUDO_LIMPIO = 700    # aire limpio  -> índice 100
MQ135_CRUDO_SUCIO = 2800    # aire viciado -> índice 0
```

Sin calibrar, el índice puede quedar clavado en 0 o en 100. Cómo hacerlo está en
el punto 8.

### 2. Los presets por tipo de ambiente

El pedido decía "los valores de base: 26 °C, 40 %, 1000 ppm". Los apliqué al
preset **hogar** (que además es el de respaldo cuando llega un tipo desconocido)
y a **personalizable**. **No** aplané los otros tipos, porque tener rangos
distintos por ambiente es una función que ya andaba y que se ve en el catálogo:

| Tipo | Temp. máx. | Hum. mín. | CO₂ máx. |
|---|---|---|---|
| Oficina | 25 °C | 40 % | 900 ppm |
| Aula | 24 °C | 40 % | 1000 ppm |
| **Hogar** | **26 °C** | **40 %** | **1000 ppm** |
| Dormitorio | 24 °C | 40 % | 900 ppm |
| Personalizable | 26 °C | 40 % | 1000 ppm |

**Ojo con esto para la demo:** tu equipo de prueba está en un ambiente de tipo
**Aula**, así que el aire acondicionado le enciende a 24 °C y corta a 22 °C, no
a 26/24. Si querés que la demo use 26/24, cambiá el tipo a **Hogar** desde
`/panel/ambientes` (o editá los números a mano). **Decime si preferís que todos
los tipos usen 26/40/1000 y los uniformo.**

### 3. El receptor IR confirma, no decodifica

El receptor detecta que hubo una portadora de 38 kHz y con eso confirma. **No
decodifica** la trama NEC para verificar que el código sea exactamente el de
EdenAir. Para la maqueta alcanza y sobra (demuestra la cadena completa), pero si
querés que el jurado vea el código decodificado hay que sumar un decodificador.
Decime si lo querés.

### 4. Falta el archivo `.env`

En la carpeta del proyecto **no hay `.env`** (solo `.env.example`), así que la
web no puede conectarse a la base por sí sola. Para correr la migración pasé las
credenciales como variables de entorno del proceso, sin tocar el proyecto. Antes
de la feria necesitás recrear tu `.env` a partir de `.env.example` con
`database.default.database = tesina_esp32`.

### 5. El firmware no se probó en la placa física

La lógica de `reglas.py` la probé corriéndola en Python de escritorio (histéresis
de temperatura, histéresis y crítico de CO₂, congelado de la calidad de aire y
ciclo 60/120 del atomizador: todo dio lo esperado). La cadena web la probé de
punta a punta contra tu base real. **Lo que no pude probar es el hardware**:
la trama IR, el ADC del MQ-135 y los relés necesitan la placa enchufada.

### 6. Los LEDs verde y azul no son controlables a mano

Desde el panel, en modo manual, se siguen pudiendo prender y apagar el aire
acondicionado, el humidificador y la luz de alerta. Los LEDs verde y azul son
**indicadores**: los maneja el equipo. Si querés poder forzarlos a mano para una
demo, se puede agregar.

### 7. Documentos históricos sin tocar

`HITO_1_BACKEND_Y_BASE_DE_DATOS.md`, `HITO_2_PAGINA_Y_EXPERIENCIA.md` y
`services.md` describen el estado del proyecto en su momento y los dejé como
están. Si querés que reflejen la lógica nueva, decime.

---

## 8. Cómo probar cada cosa

### 8.1 Preparación

1. Subí a la placa **los 9 archivos** `.py` de `firmware/` (antes eran 7: se
   agregaron `aire.py` e `infrarrojo.py`). Si falta uno, la placa arranca y falla
   con `ImportError`.
2. Abrí la consola de Thonny: cada 8 segundos vas a ver una línea como
   `27.4 C  36.2 %  1180 ppm  aire 62/100 Aceptable (sensor)`.
3. Abrí el panel en `/panel`. Se actualiza solo cada 10 segundos.

### 8.2 Temperatura → aire acondicionado + LED azul + infrarrojo

**Para que suba:** acercá un secador de pelo (aire caliente, a 30 cm, unos 20
segundos) o la mano ahuecada sobre el SCD41. También sirve una lámpara
incandescente cerca.

**Qué tiene que pasar, en este orden:**

1. Al pasar el máximo del ambiente, el ESP32 emite la trama IR (no se ve, es
   infrarrojo — **si querés mostrarlo, miralo con la cámara del celular: el LED
   emisor se ve violeta destellando**).
2. El receptor confirma.
3. Arranca el cooler y se enciende el **LED azul**.
4. El panel muestra **"Aire acondicionado activado"** y la tarjeta de
   Temperatura se pone en amarillo o rojo.

**Para probar la histéresis:** sacá la fuente de calor. El cooler **no** se apaga
al bajar de 26: sigue encendido hasta que baje de **24**. Eso es lo correcto y es
la demostración de que la histéresis existe.

> **Importante para el jurado:** en la maqueta el cooler no enfría el ambiente,
> así que la temperatura baja sola por convección, despacio. Si tarda, ventilá la
> caja a mano. **No hay ningún temporizador que lo apague**, a propósito: el
> código es el del producto real, donde un aire acondicionado sí enfría.

### 8.3 Confirmación infrarroja

**Para probar que confirma:** con todo enfrentado, hacé subir la temperatura. El
panel **no** muestra ningún aviso de IR: eso significa que confirmó.

**Para probar que NO confirma (y que igual funciona):** tapá el receptor
VS1838B con un dedo o con cinta opaca, o dalo vuelta, y hacé subir la
temperatura de nuevo.

- El cooler **arranca igual** (la confirmación no es una condición).
- El panel muestra **"Orden enviada, sin confirmación IR"** en amarillo.

Esta es una prueba muy buena para mostrar: demuestra que el sistema es honesto
sobre su propio estado en vez de esconder la falla.

> Acordate del límite: **una trama cada 60 segundos**. Si prendés y apagás muy
> seguido, el relé cambia igual pero no se emite una trama nueva.

### 8.4 Humedad → atomizador (ciclos)

**Para que baje:** en un ambiente seco ya suele estar por debajo de 40 %. Si no,
poné el equipo cerca de una estufa o un secador (aire caliente = humedad relativa
más baja).

**Qué tiene que pasar:**

1. Al bajar del mínimo, el atomizador arranca y empieza a echar niebla.
2. A los **60 segundos se apaga solo**, aunque la humedad siga baja.
3. A los **120 segundos vuelve a arrancar**. Y así.
4. Cuando la humedad supera **48 %**, se apaga y no vuelve.

**Cronometralo:** el ciclo 60/120 es fácil de mostrar con un reloj y es de las
cosas que mejor se explican ("no lo dejamos fijo porque encharca el ambiente y
vacía el depósito en minutos").

**Para probar la humedad alta:** echale vapor encima hasta pasar el 60 %. El
panel avisa **"Humedad alta. EdenAir solo puede subirla, así que únicamente lo
informa"** y **no enciende nada**. Esa es la demostración de que el sistema no
promete lo que no puede hacer.

### 8.5 Enmascarado del MQ-135 (la prueba más difícil de ver, y la más interesante)

1. Esperá a que el atomizador arranque (punto 8.4).
2. Mirá el panel: debajo del índice de calidad de aire dice **"último valor
   válido · medición en pausa"** y aparece el mensaje **"Medición de aire en
   pausa por humidificación"**.
3. El número del índice **se queda quieto**, aunque la niebla esté pasando por
   delante del sensor.
4. Apagá el atomizador (o esperá a que termine su ciclo) y cronometrá: recién a
   los **90 segundos** el índice vuelve a moverse y el mensaje desaparece.

**Cómo explicarlo:** "el sensor de aire y el humidificador se pelean, porque el
sensor lee el vapor de agua como si fuera contaminación. Si no lo ignoráramos, el
equipo diría que el aire está mal justo cuando está humidificando — y como el
humidificador se enciende por humedad baja, se realimentaría solo."

### 8.6 Warm-up del MQ-135

Reiniciá la placa y mirá el panel en el primer minuto:

- Mensaje: **"Sensor de aire calentando"**.
- Debajo del índice: **"estimado · el sensor está calentando"**.
- El índice que se muestra es el de la fórmula de respaldo, no el del sensor.

A los **5 minutos** el mensaje desaparece y el texto pasa a **"medido por el
sensor de aire"**. Ahí se puede mostrar la diferencia entre un número medido y
uno estimado.

### 8.7 CO₂ → solo avisa

**Para que suba:** soplá suavemente cerca del SCD41 durante 10–15 segundos, o
poné el equipo en un espacio chico y cerrado con gente. El aire exhalado ronda
las 40.000 ppm, así que sube rapidísimo.

**Qué tiene que pasar:**

1. Al pasar 1000 ppm: se enciende el **LED rojo** y el panel muestra **"Ventilá
   el ambiente"**.
2. **Ningún actuador se enciende.** Ese es el punto de la prueba.
3. Si seguís soplando y pasa 1400 ppm, el mensaje pasa a **"CO₂ crítico: ventilá
   el ambiente ahora"**.
4. Al ventilar, el aviso **no** se levanta al bajar de 1000: espera a bajar de
   **850**. (Y el crítico se levanta por debajo de 1100.)

**Cómo explicarlo:** "el equipo no tiene extractor, así que no puede renovar el
aire. Lo honesto es avisarte, no simular que hace algo."

### 8.8 Calidad de aire → solo avisa

**Para que baje:** destapá un marcador permanente o un poco de alcohol y
acercalo al MQ-135 unos segundos. (Con la visera puesta, acercalo por el frente.)

**Qué tiene que pasar:** al bajar de 70/100 se enciende el **LED rojo** y aparece
**"Ventilá el ambiente"**. Igual que con el CO₂, **no se enciende ningún
actuador**. El aviso se levanta recién al superar **75/100**.

**Para calibrar** (punto 7.1): con el equipo 10 minutos en aire limpio, agregá
temporalmente un `print(medidor.crudo())` en `main.py` y anotá el número → ese va
en `MQ135_CRUDO_LIMPIO`. Repetí con el marcador cerca → ese va en
`MQ135_CRUDO_SUCIO`.

### 8.9 Los tres LEDs

| Situación | Verde | Azul | Rojo |
|---|---|---|---|
| Todo en rango | 🟢 | — | — |
| Calor (aire encendido) | — | 🔵 | — |
| CO₂ alto o aire malo | — | — | 🔴 |
| Calor **y** CO₂ alto | — | 🔵 | 🔴 |

El verde se apaga apenas hay algo pasando: si hay azul o rojo, no hay verde.

**Verificación cruzada:** mirá la maqueta y después el panel, en la sección
**Control → Estado del equipo**. Tienen que decir exactamente lo mismo. Si no
coinciden, es que el equipo no está reportando.

### 8.10 Protección del relé (30 segundos)

Poné el equipo en **modo manual** desde el panel y apretá el interruptor del aire
acondicionado dos veces seguidas, rápido. La segunda orden **no** se aplica al
instante: queda pendiente y se ejecuta cuando pasan los 30 segundos. En la
consola de Thonny vas a ver `Orden demorada: fan espera su tiempo minimo de rele`.

El panel **no** miente diciendo que se aplicó: la orden queda pendiente hasta que
el equipo la cumple de verdad.

### 8.11 Que sigue funcionando sin internet

Con el equipo regulando, **apagá el WiFi del router** (o apagá Apache).

- El cooler y el atomizador **siguen funcionando** con las reglas.
- Los LEDs siguen respondiendo.
- Lo único que se pierde es el reporte: el panel deja de actualizarse y en la
  consola vas a ver `(no se pudo reportar: ...)`.

Al volver el WiFi, el equipo se reconecta solo y sigue reportando. **Esta es la
demostración de que la decisión es local**, y es probablemente lo que más va a
valorar un jurado técnico.

### 8.12 Que los umbrales se cambian sin reprogramar

1. Andá a `/panel/ambientes` → editar tu ambiente.
2. Bajá la temperatura máxima a un valor por debajo de la temperatura actual del
   cuarto (por ejemplo 20 °C) y guardá.
3. Esperá a que el equipo refresque su configuración (una hora, o reiniciá la
   placa para que la pida al arrancar).
4. El aire acondicionado arranca **sin haber tocado una línea de código**.

En **Ajustes avanzados** de esa misma pantalla están las histéresis, la calidad
de aire mínima y el CO₂ crítico. Si ponés un número incoherente (por ejemplo una
histéresis de temperatura tan grande que el corte quede por debajo del mínimo del
ambiente), el formulario lo rechaza y te explica por qué.

---

## 9. Cómo contarlo en 60 segundos

> EdenAir mide cuatro cosas: temperatura, humedad, CO₂ y calidad de aire. Pero
> solo puede actuar sobre dos.
>
> Si hace calor, le manda una orden por infrarrojo al aire acondicionado —igual
> que un control remoto— y espera la confirmación. Si el aire está seco, enciende
> el humidificador, y lo hace por ciclos para no encharcar el ambiente.
>
> Con el CO₂ y la calidad de aire no puede hacer nada: no tiene un extractor, no
> puede renovar el aire. Entonces no simula que hace algo: prende el LED rojo y
> te dice "ventilá el ambiente". La decisión es tuya.
>
> Y todo eso lo decide **el equipo**, no el servidor. Si se corta internet, sigue
> regulando el ambiente igual. Lo único que deja de hacer es reportar.
