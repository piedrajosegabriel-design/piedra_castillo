# services.md — Capa de Servicios de EdenAir

Documento técnico de la **capa de Servicios** (`app/Services/`). Acá vive la
**lógica de negocio del servidor**: la validación de las mediciones, los
cálculos, el armado de datos para el panel y el alta de dispositivos.

> **Lo que NO está acá:** las reglas que deciden cuándo prender un actuador.
> Esas viven en el firmware del ESP32 (`firmware/reglas.py`), no en el PHP.
> Ver el mapa de la sección 2.

Está escrito para **estudiarlo, explicarlo y defenderlo** servicio por servicio,
método por método y variable por variable. Complementa a
`HITO_1_BACKEND_Y_BASE_DE_DATOS.md` (arquitectura + base de datos) y a
`HITO_2_PAGINA_Y_EXPERIENCIA.md` (vistas y experiencia).

> **Idea clave (recordatorio de arquitectura).** El controlador **no** hace
> cálculos pesados ni arma HTML a mano: le **pide datos a un servicio**, el
> servicio usa **modelos** para leer/escribir en MySQL, y la **vista** solo
> recorre esos datos y los dibuja.

> **Cómo estudiar los services en el código.** Cada archivo de
> `app/Services/` empieza con un **encabezado** (QUÉ HACE / SE RELACIONA CON),
> está dividido en **secciones comentadas** por tema y termina con un
> **GLOSARIO** de todos sus métodos. Este documento da la visión de conjunto;
> el encabezado y el glosario de cada archivo te acompañan mientras leés el
> código.

## Índice

1. [Qué es un "service" en este proyecto](#1-qué-es-un-service-en-este-proyecto)
2. [Mapa general (los 6 servicios)](#2-mapa-general-los-6-servicios) · conexiones · quién instancia a quién · el viaje de una medición
3. [EnvironmentPresetService](#3-environmentpresetservice) — presets de ambiente
4. [CommandService](#4-commandservice) — cola de comandos y estado
5. [DeviceConfigService](#5-deviceconfigservice) — configuración que baja el ESP32
6. [MeasurementService](#6-measurementservice) — mediciones reales del hardware
7. [DevicePairingService](#7-devicepairingservice) — conexión del equipo por QR
8. [PanelService](#8-panelservice) — armado del dashboard
9. [Resumen de conexiones](#9-resumen-de-conexiones-servicio--modelos)
10. [Notas finales](#10-notas-finales)

---

## 1. Qué es un "service" en este proyecto

En CodeIgniter 4 existe un archivo `app/Config/Services.php` para registrar
servicios del framework. **En EdenAir no usamos ese registro**: nuestros
servicios son **clases PHP normales** dentro de `app/Services/`, namespace
`App\Services`, que se instancian con `new` cuando hacen falta (en los
controladores, en el seeder o entre ellos mismos).

Cada service:

- Tiene un **constructor** que crea las dependencias que necesita (modelos u
  otros services). No hay inyección de dependencias automática: se instancia a
  mano dentro del `__construct()`.
- Expone **métodos públicos** que los controladores llaman.
- Guarda métodos **privados** de apoyo (cálculos internos, formateo, helpers).

---

## 2. Mapa general (los 6 servicios)

| # | Servicio | Responsabilidad en una frase |
|---|---|---|
| 1 | **EnvironmentPresetService** | Perfiles ambientales (oficina, aula, hogar…) y sus umbrales base. |
| 2 | **CommandService** | Comandos a actuadores y estado del dispositivo (modo, ventilador, aromatizador, LED). |
| 3 | **DeviceConfigService** | Arma los umbrales que el ESP32 baja para poder decidir por su cuenta. |
| 4 | **MeasurementService** | Valida y guarda las mediciones **reales** del hardware, y calcula el índice de aire. |
| 5 | **DevicePairingService** | Conexión de un equipo nuevo por **QR**, sin código de activación. |
| 6 | **PanelService** | Arma **todos** los datos que la vista del panel necesita para dibujar. |

> **Ojo con la documentación vieja.** Hasta el Hito 2 existían también
> `SimulationService` (mediciones inventadas) y `DeviceProvisioningService`
> (alta automática silenciosa). Los dos **se eliminaron**: el panel ahora
> muestra solo datos medidos de verdad, y ningún dispositivo se crea sin que el
> usuario lo conecte. También se eliminó `DeviceClaimService`, reemplazado por
> `DevicePairingService`.

> **Y ojo con esto, que es lo más importante de entender.** También se eliminó
> `AutomationService`. **El servidor ya no decide cuándo prender un actuador:**
> esa lógica se mudó al firmware del ESP32 (`firmware/reglas.py`). Lo que el
> servidor hace ahora es mandarle los **números** (los umbrales del ambiente,
> que el usuario edita en `/panel/ambientes`) mediante `DeviceConfigService`, y
> **registrar** lo que el equipo informa que hizo.
>
> ```
>   servidor  →  "para este ambiente: 18–24 °C, 40–55 %, 900 ppm"
>   ESP32     →  mide, compara, acciona, y recién después reporta
> ```
>
> Motivo: así el equipo sigue regulando el ambiente aunque se corte internet, y
> no depende de la latencia de una petición HTTP para reaccionar.

### 2.1. Cómo se conectan entre sí

```
                       EnvironmentPresetService
                        (perfiles / umbrales base)
                     ▲            ▲            ▲
        ┌────────────┘            │            └────────────┐
        │                         │                         │
 DevicePairingService     DeviceConfigService          PanelService
        │                         │                         │
        │ (usa QrCode             │ (le dice al ESP32       │
        │  para el QR)            │  con qué números        │
        │                         │  decidir)               │
        │                         ▼                         ▼
        │      MeasurementService ────────────────►  CommandService
        │                │                              ▲       ▲
        ▼                ▼                              │       │
 DispositivosController  DeviceApiController ───────────┘       │
                                              PanelController ──┘
```

- **EnvironmentPresetService** es la base: no depende de nadie, lo usan varios.
- **CommandService** es el "núcleo de estado": guarda las órdenes del usuario y
  el estado que reporta el equipo. Lo usan `MeasurementService`,
  `DeviceConfigService`, `PanelService` y dos controladores.
- **DeviceConfigService** no escribe nada: solo arma el paquete de umbrales que
  el equipo descarga.
- **MeasurementService** valida y guarda. **Ya no dispara automatización**: eso
  ahora pasa dentro del ESP32, antes de que la medición llegue al servidor.
- **DevicePairingService** es el único camino de alta de un dispositivo.

### 2.2. Quién instancia a cada servicio

| Servicio | Instanciado por |
|---|---|
| EnvironmentPresetService | `PanelService`, `DevicePairingService`, `DeviceConfigService`, `AmbientesController` |
| CommandService | `PanelService`, `MeasurementService`, `DeviceConfigService`, `PanelController`, `DeviceApiController` |
| DeviceConfigService | `DeviceApiController` |
| MeasurementService | `DeviceApiController` |
| DevicePairingService | `DispositivosController`, `DeviceApiController` |
| PanelService | `PanelController` |

### 2.3. El viaje de una medición (los services trabajando juntos)

El mejor ejemplo para entender cómo colaboran es seguir **una medición**. Fijate
dónde está la línea que separa el equipo del servidor: **cuando la medición
llega al PHP, la decisión ya fue tomada**.

```
╔══════════════ ADENTRO DEL ESP32 (firmware/, MicroPython) ══════════════╗
║                                                                        ║
║  sensor.py       lee el SCD41 → 24.6 °C, 58 %, 812 ppm                ║
║        │                                                               ║
║        ▼                                                               ║
║  reglas.py       calcula el índice de aire                            ║
║                  y DECIDE con los umbrales que bajó del servidor:      ║
║                    · REGLA 1: temp/hum/CO₂ altos → fan on             ║
║                    · REGLA 2: aire < 60          → aromatizer on      ║
║                    · REGLA 3: desvío grave       → alert_led on       ║
║        │                                                               ║
║        ▼                                                               ║
║  actuadores.py   mueve los relés  ← EL AMBIENTE YA QUEDÓ REGULADO     ║
║        │                                                               ║
║        ▼                                                               ║
║  servidor.py     recién ahora avisa                                    ║
╚════════════════════════════════╤═══════════════════════════════════════╝
                                 │  POST .../measurements
                                 │  { temperatura…, actuadores:{fan:"on"},
                                 │    motivo:"CO2 alto" }
╔════════════════════════════════▼═══════════════════════════════════════╗
║                        ADENTRO DEL SERVIDOR (PHP)                      ║
║                                                                        ║
║  DeviceApiController::storeMeasurement()                               ║
║        │                                                               ║
║        ▼                                                               ║
║  MeasurementService::registrar()                                       ║
║     1. EXIGE los tres valores y que sean físicamente posibles          ║
║        (si falta uno → HTTP 422; no inventa nada)                      ║
║     2. INSERTA en `measurements`                                       ║
║     3. pasa los actuadores reportados ─────┐                          ║
║                                            ▼                          ║
║              CommandService::registrarEstadoReportado()               ║
║                 · actualiza `device_states` (fan_state = on)          ║
║                 · anota en el historial SOLO lo que cambió            ║
║        │                                                               ║
║        ▼                                                               ║
║  PanelService lee ese estado y el dashboard muestra el actuador        ║
║  encendido y el porqué (last_reason).                                  ║
╚════════════════════════════════════════════════════════════════════════╝
```

**El camino inverso (orden manual), que es el único donde el servidor manda:**

```
El usuario aprieta "prender ventilador" en el dashboard (modo manual)
        │
        ▼
CommandService::encolarComandoManual()  → INSERT 'pending'
        │                                  (el panel TODAVÍA no lo muestra)
        ▼
El ESP32 pregunta   GET  .../commands/pending   → recibe la orden
El ESP32 la aplica  (actuadores.py)
El ESP32 confirma   POST .../commands/N/executed
        │
        ▼
CommandService::markCommandAsExecuted()
   · comando → 'executed' + actualiza `device_states`
        │
        ▼
Recién ahora el dashboard muestra el ventilador encendido.
```

> Antes el servidor marcaba la orden como ejecutada al instante, porque no había
> hardware real que esperar. Con un equipo de verdad eso sería mentir: el panel
> mostraría el ventilador encendido antes de que el ESP32 se enterara.

---

## 3. EnvironmentPresetService

**Archivo:** `app/Services/EnvironmentPresetService.php`

### Qué hace
Define los **perfiles ambientales** disponibles (oficina, aula, hogar,
dormitorio, personalizable) con sus **umbrales** de temperatura, humedad y CO₂.
Es la fuente de verdad de "qué se considera rango ideal" para cada tipo de
espacio. No toca la base de datos: trabaja sobre una constante interna.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `PRESETS` | `const array` (privada) | Catálogo de perfiles. Cada perfil trae `label`, `description`, `min_temperature`, `max_temperature`, `min_humidity`, `max_humidity`, `max_co2`. |

Perfiles definidos:

| key | label | Temp (°C) | Humedad (%) | CO₂ máx (ppm) |
|---|---|---|---|---|
| `oficina` | Oficina | 21.0 – 25.0 | 40 – 60 | 900 |
| `aula` | Aula | 20.0 – 24.0 | 40 – 60 | 1000 |
| `hogar` | Hogar | 20.0 – 26.0 | 35 – 60 | 1000 |
| `dormitorio` | Dormitorio | 18.0 – 24.0 | 40 – 55 | 900 |
| `personalizable` | Personalizable | 20.0 – 25.0 | 40 – 60 | 1000 |

> `hogar` funciona como **fallback**: si piden un tipo que no existe, se devuelve hogar.

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `getPresets()` | `(): array` | Devuelve todos los perfiles. |
| `getPreset($type)` | `(string $type): array` | Devuelve un perfil por su key; si no existe, devuelve `hogar`. |
| `buildSpaceData($data)` | `(array $data): array` | Arma el array listo para insertar/actualizar en la tabla `spaces`. Toma `environment_type` y, si es `personalizable`, un `custom_name`; completa los umbrales faltantes con los del preset. |
| `getDisplayName($space)` | `(array $space): string` | Nombre legible de un espacio: el `custom_name` si es personalizable, o el `label` del preset. |
| `getEnvironmentLabel($type)` | `(string $type): string` | Label legible a partir de la key del tipo. |

### Métodos privados
| Método | Qué hace |
|---|---|
| `toFloat($value, $fallback)` | Castea a `float`; si el valor es `null`/`''`, usa el fallback. |
| `toInt($value, $fallback)` | Igual pero a `int`. |

### Con qué se conecta
- **No usa modelos.** Es lógica pura.
- **Lo usan:** `PanelService` (etiquetas y resumen del espacio),
  `DevicePairingService` (al crear el ambiente del equipo nuevo con
  `buildSpaceData`), y el controlador `AmbientesController`.

### Ejemplo de uso
```php
$presets = new \App\Services\EnvironmentPresetService();

// Datos listos para guardar en `spaces`:
$spaceData = $presets->buildSpaceData([
    'environment_type' => 'oficina',
]);
// → ['environment_type' => 'oficina', 'custom_name' => null,
//    'min_temperature' => 21.0, 'max_temperature' => 25.0,
//    'min_humidity' => 40.0, 'max_humidity' => 60.0, 'max_co2' => 900]

$label = $presets->getEnvironmentLabel('aula'); // "Aula"
```

---

## 4. CommandService

**Archivo:** `app/Services/CommandService.php`

### Qué hace
Es el **núcleo de estado y comandos**. Centraliza todo lo relacionado con:
- cambiar el **modo de operación** (automático / manual),
- **encolar y ejecutar** comandos manuales,
- **encolar** comandos generados por la automatización,
- listar comandos **pendientes** y **marcarlos como ejecutados** (actualizando el
  estado real de los actuadores en la tabla `device_states`),
- **cancelar** comandos pendientes.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `$commandModel` | `DeviceCommandModel` | Acceso a la tabla `device_commands`. |
| `$stateModel` | `DeviceStateModel` | Acceso a la tabla `device_states`. |
| `$actuatorMap` | `array` (privada) | Traduce el `command_type` al campo del estado: `fan→fan_state`, `aromatizer→aromatizer_state`, `alert_led→alert_led_state`. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `changeOperatingMode()` | `(int $deviceId, string $mode, ?int $userId, string $source = 'web'): array` | Cambia el modo del dispositivo. Si ya estaba en ese modo, no hace nada. Registra un comando `mode` ya ejecutado, actualiza `device_states`, y si pasa a **manual** cancela los comandos automáticos pendientes. Devuelve el estado actualizado. |
| `queueAndExecuteManualCommand()` | `(int $deviceId, string $commandType, string $targetValue, ?int $userId, string $source = 'web'): array` | Control manual desde la web: cancela pendientes del mismo tipo, inserta el comando y lo marca como ejecutado de inmediato (simulando que el dispositivo lo aplicó). Devuelve el comando. |
| `queueAutomationCommand()` | `(int $deviceId, string $commandType, string $targetValue, string $reason): ?array` | Encola un comando generado por automatización. **Evita ruido**: si el actuador ya está en ese valor, devuelve `null`; si ya existe un pendiente igual, lo reutiliza; si no, cancela pendientes del tipo y crea uno nuevo. |
| `getPendingCommands()` | `(int $deviceId): array` | Lista los comandos `pending` del dispositivo, ordenados por `id`. Lo usa la API que consulta el ESP32. |
| `applyPendingCommands()` | `(int $deviceId, string $executor = 'simulated-device'): array` | Ejecuta **todos** los pendientes de una (simulación web). Devuelve los ejecutados. |
| `markCommandAsExecuted()` | `(int $deviceId, int $commandId, string $executor = 'device-api'): ?array` | Marca un comando como ejecutado: valida que pertenezca al dispositivo, actualiza el actuador correspondiente en `device_states`, y pone `status=executed` + `executed_at`. |
| `getStateByDeviceId()` | `(int $deviceId): ?array` | Devuelve la fila de estado del dispositivo. **Método muy usado por el resto del sistema.** |

### Métodos privados
| Método | Qué hace |
|---|---|
| `cancelPendingByType($deviceId, $commandType)` | Cancela los pendientes de un tipo (al sustituirlos por uno nuevo). |
| `buildReasonFromCommand($command)` | Arma el texto de `last_reason` leyendo el `payload` JSON del comando, o un texto por defecto. |

### Con qué se conecta
- **Modelos:** `DeviceCommandModel`, `DeviceStateModel`.
- **Lo usan:** `PanelService` (`getStateByDeviceId`), `MeasurementService`
  (`queueAutomationCommand`, `getStateByDeviceId`), `PanelController`
  (cambio de modo, comando manual, estado), `DeviceApiController`
  (comandos pendientes + marcar ejecutado).

### Ejemplo de uso
```php
$commands = new \App\Services\CommandService();

// Control manual desde la web: encender el ventilador.
$commands->queueAndExecuteManualCommand(
    $deviceId   = 5,
    $commandType = 'fan',
    $targetValue = 'on',
    $userId      = 1,
    $source      = 'web'
);

// La ESP32 (API) consulta sus comandos pendientes:
$pendientes = $commands->getPendingCommands(5);
```

---

## 5. DeviceConfigService

**Archivo:** `app/Services/DeviceConfigService.php`

### Qué hace
Arma la **configuración que el ESP32 descarga** para poder decidir por su
cuenta. Reemplazó a `AutomationService`, que era quien decidía antes.

### El cambio de fondo (esto es lo que hay que entender)

| | Antes (`AutomationService`) | Ahora (`DeviceConfigService`) |
|---|---|---|
| Quién decide prender el ventilador | El servidor | **El ESP32** |
| Dónde están las reglas | `app/Services/` (PHP) | `firmware/reglas.py` (MicroPython) |
| Qué manda el servidor | Órdenes (*"prendé el ventilador"*) | Números (*"el máximo es 24 °C"*) |
| Si se corta internet | El equipo deja de regular | El equipo **sigue regulando** |
| Velocidad de reacción | La de una petición HTTP | Inmediata, local |

```
   servidor  →  "para este ambiente: 18–24 °C, 40–55 %, 900 ppm"
   ESP32     →  mide, compara, acciona, y recién después reporta
```

El servidor sigue siendo el dueño de **la configuración** (el usuario la edita
en `/panel/ambientes`) y del **historial**. Lo que perdió es el poder de
decisión, y eso es a propósito.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `MARGEN_ALERTA_TEMP` | `const` = 2.0 | Cuántos °C hay que pasarse del rango para que sea alerta grave (LED). |
| `MARGEN_ALERTA_HUM` | `const` = 8.0 | Ídem en puntos porcentuales de humedad. |
| `MARGEN_ALERTA_CO2` | `const` = 250 | Ídem en ppm de CO₂. |
| `AIRE_AROMATIZADOR` | `const` = 60 | Debajo de este índice de aire se enciende el aromatizador. |
| `AIRE_ALERTA` | `const` = 45 | Debajo de este índice se considera alerta grave. |
| `INTERVALO_MEDICION` | `const` = 300 | Segundos sugeridos entre mediciones. |
| `INTERVALO_COMANDOS` | `const` = 15 | Segundos entre consultas de órdenes manuales. |
| `INTERVALO_CONFIG` | `const` = 3600 | Cada cuánto el equipo refresca su configuración. |
| `$presets` | `EnvironmentPresetService` | Nombre legible del ambiente. |
| `$comandos` | `CommandService` | Modo actual y estado de los actuadores. |

> Estas constantes eran los números sueltos que estaban dentro de las reglas de
> `AutomationService`. Ahora viajan al equipo, así se pueden afinar desde el
> servidor **sin reprogramar la placa**.

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `paraDispositivo()` | `(array $device, array $space): array` | Devuelve el paquete completo de configuración: ambiente, umbrales, márgenes, modo, estado de actuadores e intervalos. |

### Qué le llega al equipo
```json
{
  "status": "success",
  "device_uid": "EDN-FA0F2FBC",
  "nombre": "Eden Air",
  "ambiente":  { "id": 12, "nombre": "Dormitorio", "tipo": "dormitorio" },
  "umbrales":  { "temp_min": 18, "temp_max": 24,
                 "hum_min": 40, "hum_max": 55, "co2_max": 900 },
  "margenes_alerta": { "temp": 2, "hum": 8, "co2": 250, "aire": 45 },
  "aire_aromatizador": 60,
  "modo": "automatic",
  "actuadores": { "fan": "off", "aromatizer": "off", "alert_led": "off" },
  "intervalos": { "medicion": 300, "comandos": 15, "config": 3600 }
}
```

Dos campos que valen la pena:

- **`modo`** — si es `manual`, el equipo **no decide**: solo obedece las órdenes
  del dashboard. Es así como el usuario recupera el control.
- **`actuadores`** — lo que el servidor cree que está encendido. Le sirve al
  equipo para reconciliarse después de un reinicio.

### Con qué se conecta
- **Modelos:** ninguno directo (lee `spaces` a través del controlador).
- **Servicios:** `EnvironmentPresetService`, `CommandService`.
- **Lo usa:** `DeviceApiController::config()` → `GET api/devices/{uid}/config`.
- **Del otro lado:** `firmware/servidor.py::config()` es quien lo consume, y
  `firmware/reglas.py` quien usa esos números.

### Ejemplo de uso
```php
$config = new \App\Services\DeviceConfigService();

$paquete = $config->paraDispositivo($device, $space);
// El equipo baja esto una vez por hora y decide con estos números
// hasta la próxima actualización.
```

---
## 6. MeasurementService

**Archivo:** `app/Services/MeasurementService.php`

### Qué hace
Guarda las mediciones **reales** que manda el hardware y dispara la
automatización con cada una. Reemplazó al viejo `SimulationService`, que
inventaba lecturas: ahora el panel muestra únicamente aire medido de verdad.

El sensor (SCD41) mide **tres** cosas: temperatura, humedad y CO₂. El **índice
de calidad de aire no es un sensor**: es una cuenta que hace este servicio a
partir de esos tres valores y de los rangos del ambiente.

> **Regla importante: este servicio no inventa datos.** Si falta un valor
> obligatorio o viene fuera del rango físico posible, corta con una excepción.
> Es preferible un error visible a un número inventado en el dashboard.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `LIMITES` | `const array` (privada) | Rangos físicos aceptables: temperatura −20 a 60 °C, humedad 0 a 100 %, CO₂ 300 a 10000 ppm. Fuera de eso es un error de lectura, no un dato. |
| `$mediciones` | `MeasurementModel` | Insertar/leer mediciones. |
| `$comandos` | `CommandService` | Registrar el estado de actuadores que reporta el equipo. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `registrar()` | `(array $device, array $space, array $datos, string $origen = 'api'): array` | Valida los tres valores, calcula el índice de aire si no vino, INSERTA la medición y **registra los actuadores que el equipo informó**. Devuelve `['measurement' => ..., 'actuadores' => cambios]`. Lanza `InvalidArgumentException` si los datos no sirven. |
| `calcularIndiceAire()` | `(float $temp, float $hum, int $co2, array $space): int` | Índice 0–100 (más alto es mejor). |
| `etiquetaAire()` | `(int $indice): string` | ≥85 Excelente, ≥70 Buena, ≥55 Aceptable, resto Mala. |

### Métodos privados
| Método | Qué hace |
|---|---|
| `exigirNumero($datos, $campo)` | Devuelve el valor, o lanza excepción si falta, no es numérico o está fuera del rango físico. |

### Cómo se calcula el índice de aire
Arranca en **100** y descuenta puntos por cada desvío respecto del ambiente:

| Desvío | Descuento |
|---|---|
| Temperatura lejos del **centro** del rango del ambiente | 6 puntos por cada °C de distancia |
| Humedad **por encima** del máximo | 1,5 puntos por cada punto porcentual |
| Humedad **por debajo** del mínimo | 1,3 puntos por cada punto porcentual |
| CO₂ **por encima** del máximo | 1 punto por cada 12 ppm de exceso |

El resultado se encierra entre 0 y 100.

### Con qué se conecta
- **Modelos:** `MeasurementModel`.
- **Servicios:** `CommandService` (para guardar el estado reportado).
- **Lo usa:** `DeviceApiController` (`storeMeasurement`). El error de validación
  se traduce a un **HTTP 422** para el dispositivo.

### Ejemplo de uso
```php
$mediciones = new \App\Services\MeasurementService();

// Medición que llegó por la API desde el ESP32:
$resultado = $mediciones->registrar($device, $space, [
    'temperature' => 28.4,
    'humidity'    => 67,
    'co2_ppm'     => 1280,
], 'api');

$resultado['measurement']['air_quality_label'];  // "Mala"
$resultado['actuadores'];                        // ["fan"] — lo que cambió
```

---

## 7. DevicePairingService

**Archivo:** `app/Services/DevicePairingService.php`

### Qué hace
Maneja **todo el ciclo de conectar un dispositivo**, sin códigos de activación
y sin pasos intermedios. Reemplazó a `DeviceClaimService`.

> **Qué cambió y por qué.** El flujo anterior obligaba al cliente a buscar un
> código `EDEN-XXXX-XXXX` en la caja y tipearlo en un asistente de 4 pasos.
> Eso se eliminó por completo: la tabla `device_activation_codes`, la columna
> `devices.activation_code`, el wizard y la validación en vivo del código.

### El flujo completo

```
1. El usuario aprieta "Conectar" en la web
        │
        ▼
   abrirVentana()  → se abre una VENTANA DE VINCULACIÓN de 10 minutos
                     y se genera EN EL MOMENTO el QR con las credenciales
                     del WiFi de configuración del equipo
        │
        ▼
2. Escanea el QR con la cámara del celular
        │  (el QR está en el formato estándar WIFI:, el mismo de cualquier
        │   cartel de WiFi: el celular se conecta solo, sin tipear nada)
        ▼
3. En el portal que abre el equipo, elige su WiFi de casa y pone la clave
        │
        ▼
4. El equipo sale a internet y llama a POST api/devices/pair con su MAC
        │
        ▼
   registrarDispositivo() → el equipo que aparece mientras la ventana está
                            abierta queda dado de alta en ESA cuenta, con
                            ambiente y estado inicial, y recibe device_uid
                            + api_token
        │
        ▼
5. La página, que venía preguntando cada 2,5 s, muestra "conectado"
```

### Por qué el QR lleva el WiFi del equipo y no el de tu casa
Una **ESP32 no tiene cámara**: no puede leer un QR. El que escanea es siempre el
celular. Por eso el QR lleva la red que **publica el equipo**, que es lo único
que el celular puede usar para ponerse en contacto con él.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `MINUTOS_VENTANA` | `const int` = 10 | Cuánto dura abierta una ventana. |
| `AP_SSID_DEFECTO` | `const string` = `EdenAir-Setup` | Nombre del WiFi de configuración. **Es una constante del firmware**: la ESP32 tiene que crear su punto de acceso con exactamente este nombre, porque la web arma el QR sin poder preguntarle nada al equipo (todavía no está en la red). Sobreescribible desde el `.env` con `edenair.apSsid`. |
| `AP_PASSWORD_DEFECTO` | `const string` = `edenair.setup` | Ídem para la clave (`edenair.apPassword`). |
| `NOMBRE_BASE` | `const string` = `Eden Air` | Nombre automático del equipo. |
| `$ventanas` | `DevicePairingModel` | Tabla `device_pairings`. |
| `$devices` / `$spaces` / `$states` | modelos | Alta del equipo. |
| `$presets` | `EnvironmentPresetService` | Ambiente por defecto. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `abrirVentana()` | `(int $userId, ?string $ip = null): array` | Cierra las ventanas anteriores del usuario (siempre hay una sola abierta), crea la nueva y devuelve el paquete con el **SVG del QR ya dibujado**. |
| `paquetePantalla()` | `(array $ventana, int $tamanoQr = 260): array` | Credenciales + SVG + segundos restantes. |
| `payloadWifi()` | `(string $ssid, string $password): string` *(estático)* | El texto que va adentro del QR: `WIFI:T:WPA;S:EdenAir-Setup;P:edenair.setup;;`. |
| `ssid()` / `password()` | `(): string` *(estáticos)* | Credenciales del punto de acceso (constante o `.env`). |
| `estado()` | `(string $token, int $userId): array` | Para el sondeo de la página: `esperando` \| `vinculado` \| `expirado` \| `cancelado` \| `desconocida`. Una cuenta **no puede** consultar la ventana de otra. |
| `cancelar()` | `(string $token, int $userId): void` | Cierra la ventana a pedido del usuario. |
| `registrarDispositivo()` | `(array $datos, ?string $ipDispositivo = null): array` | Alta del equipo desde la API. Devuelve `['estado' => ..., 'mensaje' => ..., 'device' => ...]`. |
| `listarDeUsuario()` | `(int $userId): array` | Dispositivos con etiquetas listas para "Mis dispositivos". |
| `estadoLegible()` | `(string $status): array` | `active→[Conectado, success]`, `offline→[Sin conexión, danger]`, resto `[Esperando primera lectura, info]`. |

### Métodos privados
| Método | Qué hace |
|---|---|
| `escaparWifi($valor)` | Escapa `\ ; , : "` como pide el formato `WIFI:`. |
| `elegirVentana($ip)` | A qué cuenta pertenece el equipo que se presentó. |
| `red24($ip)` | `"192.168.1.7"` → `"192.168.1."` (para desempatar). |
| `crearDispositivo(...)` | Ambiente + dispositivo + estado, **en transacción**, y cierra la ventana. |
| `nombreLibre($userId, ...)` | `"Eden Air"`, `"Eden Air 2"`, `"Eden Air 3"`… |

### Los tres casos de `registrarDispositivo()`
| Situación | Respuesta | Qué hace la API |
|---|---|---|
| La **MAC ya está registrada** (el equipo se reinició o le regrabaron el firmware) | `ok` con **sus credenciales de siempre** | HTTP 200. No se duplica el dispositivo. |
| Hay una **ventana abierta** | `ok`, se crea el dispositivo en esa cuenta | HTTP 200 con `device_uid` + `api_token`. |
| **No hay ninguna ventana** | `sin_ventana` | HTTP **202**: no es un error, es que el dueño todavía no apretó "Conectar". El firmware reintenta a los 15 s. |
| Vino **sin MAC** | `invalido` | HTTP 422. |

### Cómo se desempata si hay varias ventanas abiertas
Si dos personas están conectando su equipo al mismo tiempo, se prefiere la
ventana que se abrió desde la **misma red** que el equipo: el navegador del
dueño y su Eden Air comparten el WiFi de la casa, así que sus IP caen en el
mismo `/24`. Si eso no desempata, se toma la más reciente.

### Con qué se conecta
- **Modelos:** `DevicePairingModel`, `DeviceModel`, `SpaceModel`, `DeviceStateModel`.
- **Servicios:** `EnvironmentPresetService`.
- **Librerías:** `App\Libraries\QrCode` (dibuja el QR).
- **Lo usan:** `DispositivosController` (pantalla y sondeo) y
  `DeviceApiController` (`POST api/devices/pair`).

### Ejemplo de uso
```php
$vinculacion = new \App\Services\DevicePairingService();

// La web, al apretar "Conectar":
$paquete = $vinculacion->abrirVentana($userId, $request->getIPAddress());
echo $paquete['svg'];          // <svg>…</svg> listo para incrustar
echo $paquete['expira_en'];    // 600 (segundos)

// La API, cuando el equipo se presenta:
$resultado = $vinculacion->registrarDispositivo(['mac' => 'A1:B2:C3:D4:E5:F6'], $ip);
if ($resultado['estado'] === 'ok') {
    $resultado['device']['api_token'];   // el equipo se lo guarda y ya mide
}

// La página, mientras espera:
$estado = $vinculacion->estado($paquete['token'], $userId);   // 'esperando' | 'vinculado' | …
```

### La librería QrCode
**Archivo:** `app/Libraries/QrCode.php`

El QR **no** se pide a ningún servicio externo ni se baja de un CDN: se genera
en el servidor, con el algoritmo del estándar ISO/IEC 18004 implementado a mano
en PHP. El proyecto no usa Composer y la página tiene que funcionar sin
internet, así que no había de dónde sacar una librería.

| Qué implementa | Detalle |
|---|---|
| Modo | BYTE (cualquier texto ASCII/UTF-8) |
| Corrección de errores | Nivel M (~15% del código puede estar tapado y aun así se lee) |
| Versiones | 1 a 10 (hasta 216 bytes) |
| Salida | SVG (un solo `<path>`, sin dependencias de imagen) |

Uso: `(new \App\Libraries\QrCode($texto))->toSvg(260)`.

Para el payload de EdenAir (43 bytes) sale un QR **versión 4**: 33×33 módulos.

---

## 8. PanelService

**Archivo:** `app/Services/PanelService.php`

### Qué hace
Es el servicio **más grande**: arma **todos los datos** que necesita la vista del
panel (`panel.php`) para dibujar el dashboard, dejando la vista sin lógica PHP.
Junta usuario, espacio, dispositivo (con soporte **multi-dispositivo**), estado de
actuadores, última medición, historial, métricas, gráficos, alertas y los datos
de la API; además calcula tonos (success/warning/danger), barras, sparkline y
valores por defecto cuando todavía no hay mediciones.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `$usuarios` | `UserModel` | Datos del usuario. |
| `$espacios` | `SpaceModel` | Ambiente(s). |
| `$dispositivos` | `DeviceModel` | Dispositivo(s) del usuario. |
| `$mediciones` | `MeasurementModel` | Última medición e historial. |
| `$comandos` | `CommandService` | Estado de actuadores (`getStateByDeviceId`). |
| `$presets` | `EnvironmentPresetService` | Etiquetas y resumen del espacio. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `obtenerVistaPanel()` | `(int $userId, ?int $activeDeviceId = null): array` | Llama a `obtenerDatos()` y le agrega la clave `view` con el **bloque listo para la vista** (defaults, tonos, sparkline, sensorCards, reglas). Es el método que usa el controlador para renderizar. |
| `obtenerDatos()` | `(int $userId, ?int $activeDeviceId = null): array` | Reúne los datos crudos del panel: `user`, `space`, `device`, `state`, `resumen`, `metrics`, `charts`, `actuators`, `latest_measurement`, `history`, `alerts`, `api`, `devices_list`, etc. Soporta dispositivo activo elegido por el usuario. Lanza `RuntimeException` si no hay usuario/dispositivo/espacio. |

> Multi-dispositivo: si `activeDeviceId` corresponde a un dispositivo del usuario,
> se usa ese; si no, el primero. La lista completa se devuelve en `devices_list`
> para alimentar el selector del header.

### Métodos privados (resumen)
Son muchos helpers de **presentación y cálculo**. Los principales:

| Método | Qué hace |
|---|---|
| `armarBloqueVista($datos)` | Construye el bloque `view`: aplica defaults cuando no hay datos, calcula tono general, sensorCards, automationRules, filas de historial de ejemplo y el sparkline. |
| `crearGraficos($historial, $espacio)` | Series para los 4 gráficos (temperatura, humedad, CO₂, calidad del aire) con su tono y rango ideal. |
| `crearMetricas($medicion, $espacio)` | Tarjetas de métricas con valor, estado (Baja/Alta/En rango) y tono. |
| `crearActuadores($estado)` / `crearActuador(...)` | Tarjetas de actuadores (Encendido/Apagado + tono). |
| `formatearMedicion($medicion)` | Da formato legible a una medición (unidades, origen, fecha). |
| `crearAlertas($medicion, $espacio)` | Genera alertas según valores fuera de rango (o "estado estable"). |
| `crearPuntosGrafico(...)` | Convierte lecturas en puntos con porcentaje (alto de barra), tono y etiqueta horaria. |
| `tonoTemperatura/tonoHumedad/tonoCo2/tonoAire(...)` | Devuelven `success`/`warning`/`danger` según umbrales. |
| `etiquetaOrigen($origen)` | Traduce el origen de la medición (web, automation, api, seed). |
| `fechaHumana($fecha, $fallback)` | Formatea fechas a `d/m/Y H:i`. |
| `extraerNumero(...)` / `extraerSerieGrafico(...)` / `construirSparkPath(...)` | Helpers para parsear números desde strings, extraer una serie y armar el `path` SVG del sparkline. |

### Con qué se conecta
- **Modelos:** `UserModel`, `SpaceModel`, `DeviceModel`, `MeasurementModel`.
- **Servicios:** `CommandService`, `EnvironmentPresetService`.
- **Lo usa:** `PanelController` (`obtenerVistaPanel` para la vista, `obtenerDatos`
  para refrescos AJAX). La salida la consume la vista `panel.php`.

### Ejemplo de uso
```php
$panel = new \App\Services\PanelService();

// Para renderizar la vista completa (incluye el bloque 'view'):
$datos = $panel->obtenerVistaPanel($userId, $activeDeviceId);
return view('panel', ['panel' => $datos]);

// Para un refresco AJAX (solo datos crudos):
$datos = $panel->obtenerDatos($userId);
return $this->response->setJSON($datos);
```

---

## 9. Resumen de conexiones (servicio → modelos)

| Servicio | Modelos que usa | Otros servicios que usa |
|---|---|---|
| EnvironmentPresetService | — | — |
| CommandService | DeviceCommandModel, DeviceStateModel | — |
| DeviceConfigService | — | EnvironmentPresetService, CommandService |
| MeasurementService | MeasurementModel | CommandService |
| DevicePairingService | DevicePairingModel, DeviceModel, SpaceModel, DeviceStateModel | EnvironmentPresetService (+ librería QrCode) |
| PanelService | UserModel, SpaceModel, DeviceModel, MeasurementModel | CommandService, EnvironmentPresetService |

---

## 10. Notas finales

- **No hay registro en `app/Config/Services.php`.** Todos los servicios se
  instancian con `new`. Si en el futuro se quiere usar el contenedor de
  CodeIgniter (`Services::nombre()`), habría que registrarlos ahí.
- **Patrón común:** el constructor crea sus dependencias; los métodos públicos son
  el "contrato" con los controladores; los privados son apoyo interno.
- **Transacciones:** solo `DevicePairingService::crearDispositivo()` usa una
  transacción de base de datos (`transStart`/`transComplete`), porque crea
  varias filas relacionadas (ambiente + dispositivo + estado) que deben quedar
  consistentes.
- **Nada es simulado.** Se eliminaron `SimulationService` y
  `DeviceProvisioningService`: no hay mediciones inventadas ni dispositivos
  creados en silencio. Todo lo que se ve en el panel lo midió el hardware, y
  todo dispositivo existe porque alguien lo conectó desde la web.
- **Sin dependencias externas.** No hay Composer ni CDNs: el QR se genera con
  `app/Libraries/QrCode.php`, escrito dentro del proyecto.
