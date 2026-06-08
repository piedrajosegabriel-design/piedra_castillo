# services.md — Capa de Servicios de EdenAir

Documento técnico de la **capa de Servicios** (`app/Services/`). Acá vive la
**lógica de negocio** del sistema: las reglas, los cálculos, la simulación de
mediciones, el armado de datos para el panel y el alta de dispositivos.

Está escrito para **estudiarlo, explicarlo y defenderlo** servicio por servicio,
método por método y variable por variable. Complementa a
`HITO_1_BACKEND_Y_BASE_DE_DATOS.md` (arquitectura + base de datos) y a
`HITO_2_PAGINA_Y_EXPERIENCIA.md` (vistas y experiencia).

> **Idea clave (recordatorio de arquitectura).** El controlador **no** hace
> cálculos pesados ni arma HTML a mano: le **pide datos a un servicio**, el
> servicio usa **modelos** para leer/escribir en MySQL, y la **vista** solo
> recorre esos datos y los dibuja.

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

## 2. Mapa general (los 7 servicios)

| # | Servicio | Responsabilidad en una frase |
|---|---|---|
| 1 | **EnvironmentPresetService** | Perfiles ambientales (oficina, aula, hogar…) y sus umbrales base. |
| 2 | **CommandService** | Comandos a actuadores y estado del dispositivo (modo, ventilador, aromatizador, LED). |
| 3 | **AutomationService** | Reglas que deciden qué actuadores encender según una medición. |
| 4 | **SimulationService** | Genera mediciones simuladas (semilla, web y API). |
| 5 | **DeviceProvisioningService** | Alta automática de ambiente + dispositivo + estado al entrar/seedear. |
| 6 | **DeviceClaimService** | Alta de dispositivo por **código de activación** (wizard "Agregar dispositivo"). |
| 7 | **PanelService** | Arma **todos** los datos que la vista del panel necesita para dibujar. |

### 2.1. Cómo se conectan entre sí

```
                         EnvironmentPresetService
                          (perfiles / umbrales)
                            ▲      ▲      ▲
            ┌───────────────┘      │      └───────────────┐
            │                      │                      │
   DeviceProvisioning        DeviceClaim              PanelService
       Service                 Service                    │
        │   │                   │   │                     │
        │   └──────► Simulation ◄───┘                     │
        │            Service                              │
        │              │                                  │
        │              ▼                                  ▼
        │        AutomationService ─────────►  CommandService
        │              │                          ▲   ▲
        └──────────────┘                          │   │
                                       PanelController  DeviceApiController
```

- **EnvironmentPresetService** es la base: no depende de nadie, lo usan casi todos.
- **CommandService** es el "núcleo de estado": maneja comandos y el estado del
  dispositivo. Lo usan `AutomationService`, `PanelService` y dos controladores.
- **SimulationService** depende de `AutomationService` (al crear una medición,
  dispara la automatización).
- **DeviceProvisioning** y **DeviceClaim** son los dos caminos de alta: comparten
  `EnvironmentPresetService` + `SimulationService`.

### 2.2. Quién instancia a cada servicio

| Servicio | Instanciado por |
|---|---|
| EnvironmentPresetService | `PanelService`, `DeviceClaimService`, `DeviceProvisioningService`, `AmbientesController` |
| CommandService | `PanelService`, `AutomationService`, `PanelController`, `DeviceApiController` |
| AutomationService | `SimulationService`, `PanelController` |
| SimulationService | `DeviceProvisioningService`, `DeviceClaimService`, `PanelController`, `DeviceApiController` |
| DeviceProvisioningService | `PanelController`, `DatabaseSeeder` |
| DeviceClaimService | `DispositivosController` |
| PanelService | `PanelController` |

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
- **Lo usan:** `PanelService` (etiquetas y resumen del espacio), `DeviceClaimService`
  y `DeviceProvisioningService` (al crear el espacio con `buildSpaceData`), y el
  controlador `AmbientesController`.

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
- **Lo usan:** `PanelService` (`getStateByDeviceId`), `AutomationService`
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

## 5. AutomationService

**Archivo:** `app/Services/AutomationService.php`

### Qué hace
Contiene las **reglas de automatización**. Dada una medición y el perfil del
espacio, decide qué actuadores deberían encenderse o apagarse y **encola los
comandos** correspondientes a través de `CommandService`. Solo actúa si el
dispositivo está en **modo automático**.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `$commandService` | `CommandService` | Para leer el estado y encolar comandos. |
| `$measurementModel` | `MeasurementModel` | Para buscar la última medición. |

### Reglas implementadas (lógica de negocio)
A partir de la medición (`temp`, `humidity`, `co2`, `airScore`) y los umbrales
del espacio (`minTemp/maxTemp`, `minHum/maxHum`, `maxCo2`):

| Actuador | Se enciende (`on`) cuando… |
|---|---|
| **Ventilador / aire** (`fan`) | `temp > maxTemp` **o** `humidity > maxHum` **o** `co2 > maxCo2`. |
| **Aromatizador** (`aromatizer`) | `airScore < 60`. |
| **LED de alerta** (`alert_led`) | `temp > maxTemp+2` o `temp < minTemp-2` o `humidity > maxHum+8` o `humidity < minHum-8` o `co2 > maxCo2+250` o `airScore < 45`. |

Por cada actuador se llama a `queueAutomationCommand()` con el valor objetivo
(`on`/`off`) y un `reason` armado con los motivos detectados.

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `processMeasurement()` | `(array $device, array $space, array $measurement): array` | Aplica las reglas a una medición concreta. Si no hay estado o el modo es manual, no genera comandos. Devuelve `['summary' => string, 'commands' => array]`. |
| `processLatestMeasurement()` | `(array $device, array $space): array` | Busca la **última** medición del dispositivo y la procesa. Si no hay mediciones, devuelve un resumen vacío. |

### Con qué se conecta
- **Modelos:** `MeasurementModel`.
- **Servicios:** `CommandService`.
- **Lo usan:** `SimulationService` (al crear una medición la procesa enseguida),
  `PanelController` (`processLatestMeasurement` para reevaluar).

### Ejemplo de uso
```php
$automation = new \App\Services\AutomationService();

$resultado = $automation->processMeasurement($device, $space, $measurement);
// $resultado['summary']  → "Aire acondicionado sugerido. Aromatizador sugerido."
// $resultado['commands'] → [ ...comandos encolados... ]
```

---

## 6. SimulationService

**Archivo:** `app/Services/SimulationService.php`

### Qué hace
Genera **mediciones simuladas** realistas mientras no hay una ESP32 física. Se
encarga de:
- precargar un **historial** inicial al dar de alta un dispositivo,
- crear una **nueva medición** (desde la web o la API), aplicando variación
  aleatoria suave a partir de la última lectura y respetando límites,
- calcular el **índice de calidad del aire** y su etiqueta cuando no vienen dados,
- disparar la **automatización** después de cada medición nueva.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `$measurementModel` | `MeasurementModel` | Insertar/leer mediciones. |
| `$automationService` | `AutomationService` | Reevaluar actuadores tras cada medición. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `seedHistoryForDevice()` | `(array $device, array $space, int $count = 6): void` | Inserta `count` mediciones hacia atrás (una por hora) con origen `seed`, para que el panel tenga datos desde el inicio. |
| `createMeasurement()` | `(array $device, array $space, string $source = 'web', array $input = []): array` | Crea una medición nueva. Si `$input` trae valores los respeta (acotados a límites); si no, varía a partir de la última. Tras guardar, llama a la automatización. Devuelve `['measurement' => ..., 'automation' => ...]`. |

### Métodos privados (lógica de cálculo)
| Método | Qué hace |
|---|---|
| `generateMeasurementPayload($space, $lastMeasurement, $input)` | Arma el payload completo: parte de los puntos medios del rango del espacio, aplica variación o respeta el input, y completa AQI/label/notas/fecha. |
| `calculateAirQualityIndex($temp, $humidity, $co2, $space)` | Calcula el AQI (0–100) penalizando alejamiento de la temperatura media, humedad fuera de rango y CO₂ sobre el límite. |
| `getAirQualityLabel($score)` | Traduce el AQI a etiqueta: ≥85 Excelente, ≥70 Buena, ≥55 Aceptable, resto Mala. |
| `resolveDecimal($value, $lastValue, $min, $max, $variation)` | Resuelve un valor decimal: usa el input si vino; si no, parte del anterior + offset aleatorio, acotado a `[min, max]`. |
| `resolveInteger($value, $lastValue, $min, $max, $variation)` | Igual que el anterior pero para enteros (CO₂). |

### Con qué se conecta
- **Modelos:** `MeasurementModel`.
- **Servicios:** `AutomationService`.
- **Lo usan:** `DeviceProvisioningService` y `DeviceClaimService`
  (`seedHistoryForDevice` en el alta), `PanelController` (medición web),
  `DeviceApiController` (medición vía API).

### Ejemplo de uso
```php
$simulation = new \App\Services\SimulationService();

// Medición desde la web (sin valores: se generan):
$resultado = $simulation->createMeasurement($device, $space, 'web', []);
$medicion  = $resultado['measurement'];

// Medición desde la API con datos del cuerpo del request:
$resultado = $simulation->createMeasurement($device, $space, 'api', [
    'temperature' => 28.4,
    'humidity'    => 67,
    'co2_ppm'     => 1280,
]);
```

---

## 7. DeviceProvisioningService

**Archivo:** `app/Services/DeviceProvisioningService.php`

### Qué hace
Garantiza que un usuario tenga **lo mínimo para operar**: un ambiente, un
dispositivo simulado, su estado inicial y un historial de mediciones. Es el alta
**automática** (idempotente): si algo ya existe, lo reutiliza; si falta, lo crea.
Se usa en el seeder y como red de seguridad en el panel.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `$spaceModel` | `SpaceModel` | Ambiente del usuario. |
| `$deviceModel` | `DeviceModel` | Dispositivo del usuario. |
| `$deviceStateModel` | `DeviceStateModel` | Estado del dispositivo. |
| `$measurementModel` | `MeasurementModel` | Comprobar/crear mediciones. |
| `$presetService` | `EnvironmentPresetService` | Construir el ambiente. |
| `$simulationService` | `SimulationService` | Sembrar el historial. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `ensureUserSetup()` | `(int $userId, array $spaceInput = [], bool $createSpaceIfMissing = true): array` | Asegura ambiente + dispositivo + estado + mediciones. Crea lo que falte (salvo que `createSpaceIfMissing` sea `false`, en cuyo caso lanza excepción si no hay ambiente). Devuelve `['space' => ..., 'device' => ..., 'state' => ...]`. |

**Detalle del flujo de `ensureUserSetup()`:**
1. Busca el ambiente; si no hay y se permite, lo crea con `buildSpaceData` (default `hogar`).
2. Busca el dispositivo; si no hay, crea uno simulado (`device_uid` `SIM-XXXX`, `api_token` aleatorio).
3. Busca el estado; si no hay, lo crea en modo automático con todos los actuadores en `off`.
4. Si no hay mediciones, siembra el historial con `SimulationService`.

### Con qué se conecta
- **Modelos:** `SpaceModel`, `DeviceModel`, `DeviceStateModel`, `MeasurementModel`.
- **Servicios:** `EnvironmentPresetService`, `SimulationService`.
- **Lo usan:** `PanelController` (`ensureUserSetup`), `DatabaseSeeder` (alta del usuario demo).

### Ejemplo de uso
```php
$provisioning = new \App\Services\DeviceProvisioningService();

// Asegura todo lo necesario para el usuario (crea lo que falte):
$setup = $provisioning->ensureUserSetup($userId, [], true);
$device = $setup['device'];
$space  = $setup['space'];

// Variante estricta: error si el usuario no tiene ambiente todavía.
$provisioning->ensureUserSetup($userId, [], false);
```

---

## 8. DeviceClaimService

**Archivo:** `app/Services/DeviceClaimService.php`

### Qué hace
Implementa el alta de dispositivos por **código de activación** (claim code), el
camino del wizard "Agregar dispositivo" (Hito 2). El flujo:
1. El producto trae un código único `EDEN-XXXX-XXXX`.
2. El usuario lo ingresa (o escanea el QR).
3. Se valida que exista y no haya sido usado.
4. El usuario le pone nombre, tipo y ambiente.
5. El código queda marcado como **usado** y el dispositivo queda asociado a la cuenta.

> La MAC es solo un dato técnico interno, **nunca una credencial**.

### Variables / propiedades
| Nombre | Tipo | Para qué |
|---|---|---|
| `TIPOS` | `const array` | Catálogo de tipos de dispositivo ofrecidos en el alta (Eden Air Core, Monitor ambiental, Ambientador inteligente, Prototipo educativo) con su descripción. |
| `ESPACIOS` | `const array` | Catálogo de espacios elegibles → cómo se mapean a un `preset` de `spaces` (dormitorio, living, aula, oficina, cocina, laboratorio, otro). |
| `$codes` | `DeviceActivationCodeModel` | Validar/canjear códigos. |
| `$devices` | `DeviceModel` | Crear y listar dispositivos. |
| `$spaces` | `SpaceModel` | Crear/reutilizar ambientes. |
| `$states` | `DeviceStateModel` | Estado inicial del dispositivo. |
| `$presets` | `EnvironmentPresetService` | Construir datos del ambiente. |
| `$simulation` | `SimulationService` | Sembrar historial al vincular. |

### Métodos públicos
| Método | Firma | Qué hace |
|---|---|---|
| `tiposDispositivo()` | `(): array` | Devuelve el catálogo `TIPOS`. |
| `espacios()` | `(): array` | Devuelve el catálogo `ESPACIOS`. |
| `esTipoValido($tipo)` | `(string): bool` | Valida un tipo contra `TIPOS`. |
| `esEspacioValido($espacio)` | `(string): bool` | Valida un espacio contra `ESPACIOS`. |
| `inspeccionarCodigo($codigo)` | `(string): array` | Inspecciona un código **sin canjearlo** (feedback del paso 1). Valida formato `EDEN-XXXX-XXXX` y estado. Devuelve `['ok'=>bool, 'estado'=>string, 'mensaje'=>string, 'code'=>?array]`. |
| `vincular($userId, $datos)` | `(int, array): array` | Canjea el código y crea, **en una transacción**, ambiente (nuevo o reusado) + dispositivo + estado + historial; marca el código como usado. Devuelve `['device'=>..., 'space'=>...]`. Lanza `RuntimeException` con mensaje apto para el usuario si algo falla. |
| `listarDeUsuario($userId)` | `(int): array` | Lista los dispositivos del usuario con metadatos listos para la vista "Mis dispositivos" (espacio legible, etiqueta y tono de estado). |
| `estadoLegible($status, $lastSeen)` | `(string, ?string): array` | Texto + tono visual por estado: `active→[Activo,success]`, `offline→[Sin conexión,danger]`, `pending→[Pendiente…,warning]`, default `[Simulado,info]`. |

**Estados posibles de `inspeccionarCodigo()`:** `vacio`, `formato`,
`inexistente`, `usado` (claimed), `deshabilitado` (disabled), `disponible`.

### Con qué se conecta
- **Modelos:** `DeviceActivationCodeModel`, `DeviceModel`, `SpaceModel`, `DeviceStateModel`.
- **Servicios:** `EnvironmentPresetService`, `SimulationService`.
- **Lo usa:** `DispositivosController` (formulario, validación AJAX del código,
  alta y listado).
- **Doc relacionada:** `docs/HITO_2_IMPLEMENTACION.md`.

### Ejemplo de uso
```php
$claim = new \App\Services\DeviceClaimService();

// Paso 1: feedback en vivo del código (sin canjear).
$inspeccion = $claim->inspeccionarCodigo('EDEN-AB12-CD34');
if (! $inspeccion['ok']) {
    // mostrar $inspeccion['mensaje']
}

// Paso 2: vincular (canjear) el dispositivo a la cuenta.
try {
    $resultado = $claim->vincular($userId, [
        'code'        => 'EDEN-AB12-CD34',
        'name'        => 'Monitor del living',
        'device_type' => 'Eden Air Core',
        'space'       => 'living',
        'space_custom'=> 'Living principal',
    ]);
    $device = $resultado['device'];
} catch (\RuntimeException $e) {
    // mostrar $e->getMessage() al usuario
}
```

---

## 9. PanelService

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

## 10. Resumen de conexiones (servicio → modelos)

| Servicio | Modelos que usa | Otros servicios que usa |
|---|---|---|
| EnvironmentPresetService | — | — |
| CommandService | DeviceCommandModel, DeviceStateModel | — |
| AutomationService | MeasurementModel | CommandService |
| SimulationService | MeasurementModel | AutomationService |
| DeviceProvisioningService | SpaceModel, DeviceModel, DeviceStateModel, MeasurementModel | EnvironmentPresetService, SimulationService |
| DeviceClaimService | DeviceActivationCodeModel, DeviceModel, SpaceModel, DeviceStateModel | EnvironmentPresetService, SimulationService |
| PanelService | UserModel, SpaceModel, DeviceModel, MeasurementModel | CommandService, EnvironmentPresetService |

---

## 11. Notas finales

- **No hay registro en `app/Config/Services.php`.** Todos los servicios se
  instancian con `new`. Si en el futuro se quiere usar el contenedor de
  CodeIgniter (`Services::nombre()`), habría que registrarlos ahí.
- **Patrón común:** el constructor crea sus dependencias; los métodos públicos son
  el "contrato" con los controladores; los privados son apoyo interno.
- **Transacciones:** solo `DeviceClaimService::vincular()` usa una transacción de
  base de datos (`transStart`/`transComplete`), porque crea varias filas
  relacionadas que deben quedar consistentes.
- **Pensado para la ESP32 real:** la simulación (`SimulationService`) y los
  comandos (`CommandService`) ya están preparados para que, cuando llegue el
  hardware, el ESP32 envíe mediciones reales y consuma los comandos por la API
  sin cambiar la lógica de negocio.
```
