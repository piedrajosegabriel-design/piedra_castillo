# HITO 1 — Backend, arquitectura y base de datos

Documento técnico de EdenAir. Explica **cómo está construido el sistema por
dentro**: la arquitectura, el backend (rutas, controladores, modelos,
servicios, sesiones, filtros y validaciones) y, en una sección aparte y
detallada, la **base de datos**. Está escrito para estudiarlo, explicarlo y
defenderlo archivo por archivo, variable por variable y función por función.

> **Lógica de entrada actualizada (Hito 2).** El sistema **ya no obliga a elegir
> un ambiente al loguearse**. Al iniciar sesión, el usuario va **directo a
> `/panel`**, y es `PanelController::index()` quien decide qué mostrar:
> pantalla de **bienvenida** si tiene 0 dispositivos, o el **panel monitor** si
> tiene ≥ 1. El ambiente se configura **dentro del alta de un dispositivo**
> (wizard), no en el login. Toda referencia al viejo flujo `/panel/ambiente`
> fue eliminada del código. La lógica de la experiencia (vistas, wizard) se
> documenta en `HITO_2_PAGINA_Y_EXPERIENCIA.md`; la lógica de negocio
> (servicios) en `services.md`.

---

## 1. Qué es EdenAir y con qué está hecho

EdenAir es un **sistema de monitoreo y ambientación inteligente de espacios
interiores**. Mide temperatura, humedad, CO₂ y calidad del aire con un módulo
**ESP32**, muestra todo en un **dashboard** web y puede **automatizar**
actuadores (ventilación/aire acondicionado, humidificación/aromatizador y un LED
de alerta).

**Stack técnico**

| Capa | Tecnología |
|---|---|
| Lenguaje / Framework | PHP 8.2 + **CodeIgniter 4** (patrón MVC) |
| Servidor | Apache (XAMPP), `http://localhost/piedra_castillo/public/` |
| Base de datos | MySQL / MariaDB (`tesina_esp32`) |
| Hardware (IoT) | ESP32 que envía mediciones y recibe comandos por una API REST |
| Frontend | HTML + CSS propio (sistema de tokens) + JavaScript |

---

## 2. Arquitectura general (MVC + Servicios)

El proyecto sigue **Modelo–Vista–Controlador** de CodeIgniter 4, con una capa
extra de **Servicios** para la lógica de negocio:

```
Navegador / ESP32
      │  (HTTP)
      ▼
[ Rutas ]   app/Config/Routes.php        → decide qué controlador atiende
      ▼
[ Filtros ] AuthFilter / GuestFilter      → controlan el acceso por sesión
      ▼
[ Controladores ] app/Controllers/...     → reciben el pedido, validan, responden
      ▼
[ Servicios ] app/Services/...            → lógica de negocio (cálculos, reglas)
      ▼
[ Modelos ] app/Models/...                → hablan con la base de datos
      ▼
[ Vistas ] app/Views/...                  → arman el HTML que ve el usuario
```

**Idea clave:** el controlador no hace cálculos pesados ni arma el HTML a mano.
Pide datos a un **servicio**, el servicio usa **modelos** para leer/escribir en
la base, y la **vista** solo recorre esos datos y los dibuja. Cada parte queda
ordenada y fácil de mantener.

---

## 3. Organización del backend (carpetas)

| Carpeta | Qué contiene |
|---|---|
| `app/Config/` | Configuración: rutas (`Routes.php`), filtros (`Filters.php`), base de datos, email. |
| `app/Controllers/` | Controladores: el punto de entrada de cada acción. |
| `app/Controllers/Api/` | Controlador de la **API REST** que usa el ESP32. |
| `app/Filters/` | Filtros de acceso (`AuthFilter`, `GuestFilter`). |
| `app/Models/` | Modelos: una clase por tabla, encapsulan las consultas. |
| `app/Services/` | Lógica de negocio reutilizable (reglas, simulación, armado del panel). |
| `app/Views/` | Vistas (HTML/PHP) y parciales reutilizables. |
| `app/Database/Migrations/` | Cambios versionados de estructura de la base. |
| `writable/` | Sesiones, logs y caché (lo que el framework necesita escribir). |

---

## 4. Autenticación y la NUEVA lógica de entrada

Este es el cambio conceptual más importante del proyecto y atraviesa varios
archivos. Conviene entenderlo antes que el resto.

### 4.1 Qué pasa al iniciar sesión

1. El usuario envía usuario/correo + contraseña a `POST /login`.
2. `AccesoController::validarLogin()` valida el formulario, busca al usuario con
   `UserModel::buscarParaLogin()` y compara la contraseña con
   `password_verify()` contra el `password_hash` (bcrypt).
3. Si coincide, `iniciarSesion()` **regenera la sesión** y guarda
   `user_id`, `user_name` e `is_logged_in`.
4. `redirigirDespuesDelLogin()` devuelve **siempre** `redirect()->to('/panel')`.
   **Ya no se evalúa si el usuario tiene un ambiente.**

### 4.2 Qué decide la pantalla de destino

`PanelController::index()` es el que ramifica:

```php
$cantidadDispositivos = (new DeviceModel())->where('user_id', $userId)->countAllResults();

if ($cantidadDispositivos === 0) {
    return view('panel/bienvenida', [...]);   // 0 dispositivos → bienvenida
}

$activeDeviceId = $this->dispositivoActivo($userId);
return view('panel', ['panel' => (new PanelService())->obtenerVistaPanel($userId, $activeDeviceId)]);
```

- **0 dispositivos** → `panel/bienvenida` (3 CTAs: agregar dispositivo, ver
  demo, comprar). No se auto-crea nada en silencio.
- **≥ 1 dispositivo** → panel monitor del **dispositivo activo**.

### 4.3 Dónde se elige el ambiente ahora

El ambiente (perfil de rangos) se crea **dentro del wizard de alta de
dispositivo** (`DispositivosController` + `DeviceClaimService::vincular()`), o
se reutiliza uno ya existente. Editar rangos se hace en **Ambientes**
(`AmbientesController`). Nunca más en el login.

> **Archivos que sostienen esta lógica:** `GuestFilter` (manda al panel si ya
> hay sesión), `AccesoController::redirigirDespuesDelLogin()` (siempre
> `/panel`), `PanelController::index()` (ramifica bienvenida/panel).

---

## 5. Filtros de acceso (`app/Filters/`)

Los filtros se ejecutan **antes** de que la petición llegue al controlador. Se
registran con alias en `app/Config/Filters.php`:

```php
'auth'  => \App\Filters\AuthFilter::class,
'guest' => \App\Filters\GuestFilter::class,
```

Y en `Filters.php` también está activo, **global y antes de cada request**, el
filtro **`csrf`** (excepto en `api/*`), que protege todos los formularios.

### 5.1 `AuthFilter` — protege el área privada

Aplica al grupo `panel` y a `logout`/`dashboard`. Su método `before()`:

| Situación | Respuesta |
|---|---|
| Hay sesión (`user_id`) | `return null` → deja pasar. |
| No hay sesión y la request es AJAX/JSON | `401 Unauthorized` con JSON `{status:error, message:...}`. |
| No hay sesión y es navegación normal | `redirect()->to('/login')` con un flash `error`. |

La detección de AJAX/JSON combina `$request->isAJAX()` (header
`X-Requested-With`) y `str_contains($accept, 'application/json')` (fetch/axios).
Así una llamada de JavaScript recibe un 401 accionable en vez de un HTML de
login que no sabría interpretar.

### 5.2 `GuestFilter` — protege login/registro/recuperación

Aplica al grupo `guest`. Su método `before()`:

| Situación | Respuesta |
|---|---|
| No hay sesión | `return null` → deja pasar (son pantallas para invitados). |
| Hay sesión | `redirect()->to('/panel')` **siempre**. |

> **Cambio respecto del flujo viejo:** antes consultaba `SpaceModel` y mandaba a
> `/panel/ambiente` si no había ambiente. Ahora **no consulta nada**: si estás
> logueado, vas al panel y `PanelController::index()` decide. Esto eliminó la
> dependencia del filtro con la base de datos.

---

## 6. Rutas (`app/Config/Routes.php`)

Las rutas se agrupan según **quién puede entrar**. La variable `$routes` es la
colección de rutas que CI4 inyecta.

### 6.1 Públicas (cualquiera)
| Método | Ruta | Controlador → acción |
|---|---|---|
| GET | `/` | `AccesoController::inicio` (landing). |
| GET | `portfolio`, `portfolio.php` | `PortfolioController::index`. |
| GET | `api/sensores` | Closure: devuelve sensores **simulados** en JSON para el objeto 3D del hero. |

### 6.2 Grupo `guest` (filtro `guest` — solo SIN sesión)
| Método | Ruta | Acción |
|---|---|---|
| GET/POST | `login` | `login` / `validarLogin`. |
| GET/POST | `registro` (y `register`) | `registro` / `guardarRegistro`. |
| GET/POST | `recuperar` | `recuperar` / `procesarRecuperacion`. |
| GET/POST | `restablecer/(:any)` | `restablecer/$1` / `guardarNuevaPassword/$1`. |

### 6.3 Grupo `panel` (filtro `auth` — solo CON sesión)
| Método | Ruta | Acción |
|---|---|---|
| GET | `panel` | `PanelController::index` (bienvenida o panel monitor). |
| GET/POST | `panel/perfil` | `perfil` / `actualizarPerfil`. |
| POST | `panel/password` | `actualizarPassword`. |
| GET | `panel/compra` | `compra`. |
| GET | `panel/dispositivos` | `DispositivosController::index` (Mis dispositivos). |
| GET | `panel/dispositivos/agregar` | `agregar` (wizard de 4 pasos). |
| GET | `panel/dispositivos/validar` | `validar` (chequeo en vivo del código, JSON, exento de CSRF por ser GET). |
| POST | `panel/dispositivos` | `guardar` (canjea el código y crea el dispositivo). |
| POST | `panel/dispositivo-activo` | `PanelController::seleccionarDispositivo` (switcher). |
| POST | `panel/demo` | `PanelController::iniciarDemo`. |
| GET | `panel/ambientes` | `AmbientesController::index`. |
| GET | `panel/ambientes/(:num)/editar` | `editar/$1`. |
| POST | `panel/ambientes/(:num)` | `actualizar/$1`. |
| POST | `panel/medicion` | `guardarMedicion`. |
| POST | `panel/modo` | `cambiarModo`. |
| POST | `panel/actuador` | `cambiarActuador`. |
| GET | `dashboard` (fuera del grupo, filtro `auth`) | `PanelController::index` (alias). |
| GET | `logout` (filtro `auth`) | `AccesoController::logout`. |

> **Eliminado en la limpieza:** las rutas `GET/POST panel/ambiente` que
> apuntaban a `AccesoController::seleccionAmbiente` / `guardarAmbiente` (flujo
> viejo) ya no existen.

### 6.4 API REST para el ESP32 (`api/devices/...`)
Sin filtro `auth` (autentican con token de dispositivo), exentas de CSRF.
| Método | Ruta | Acción |
|---|---|---|
| POST | `api/devices/(:segment)/measurements` | `DeviceApiController::storeMeasurement/$1`. |
| GET | `api/devices/(:segment)/commands/pending` | `pendingCommands/$1`. |
| POST | `api/devices/(:segment)/commands/(:num)/executed` | `markCommandExecuted/$1/$2`. |

---

## 7. Controladores (`app/Controllers/`)

Todos extienden `BaseController`. Acceden a `$this->request`,
`$this->validateData()`, `$this->validator`, `$this->response`.

### 7.1 `AccesoController`
**Responsabilidad:** todo lo público y de cuenta — landing, login, registro,
recuperación/restablecimiento de contraseña y logout. *(Ya no maneja ambiente.)*

| Método | Qué hace | Conexiones |
|---|---|---|
| `inicio()` | Renderiza `inicio` (landing). | vista `inicio.php` |
| `login()` / `registro()` / `recuperar()` | Renderizan sus formularios. | vistas correspondientes |
| `validarLogin()` | Orquesta login: lee datos, valida formulario y credenciales, rehashea si hace falta, inicia sesión y redirige. | `UserModel::buscarParaLogin`, `password_verify`, `iniciarSesion`, `redirigirDespuesDelLogin` |
| `guardarRegistro()` | Valida el formulario, verifica unicidad y crea el usuario. Mensaje: *"Inicia sesion para entrar al panel."* | `UserModel::existeCorreoOUsuario`, `UserModel::crearUsuario` |
| `procesarRecuperacion()` | Genera token, lo guarda hasheado con vencimiento y envía email. | `UserModel::guardarToken`, `Config\Services::email()`, vista `emails/recuperar_password` |
| `restablecer($token)` | Muestra el formulario si el token es válido. | `UserModel::buscarPorToken` |
| `guardarNuevaPassword($token)` | Valida la nueva contraseña y la actualiza. | `UserModel::actualizarPasswordConToken` |
| `logout()` | `session()->destroy()` y vuelve al login. | — |

**Helpers privados clave:** `reglasLogin()`, `reglasRegistro()`,
`mensajesRegistro()`, `validarFormularioLogin()`, `validarCredencialesLogin()`,
`actualizarHashLoginSiHaceFalta()`, `redirigirConInputYDato()`,
`leerDatosLogin()`, `leerDatosRegistro()`, `iniciarSesion()`.

```php
private function iniciarSesion(array $usuario): void {
    session()->regenerate(true);          // evita fijación de sesión
    session()->set([
        'user_id'      => (int) $usuario['id'],
        'user_name'    => $usuario['nombre'],
        'is_logged_in' => true,
    ]);
}
```

### 7.2 `PanelController`
**Responsabilidad:** el corazón del área privada. Listas blancas como constantes:
`MODOS = ['automatic','manual']`, `ACTUADORES = ['fan','aromatizer','alert_led']`,
`VALORES_ACTUADOR = ['on','off']`.

| Método | Qué hace | Conexiones |
|---|---|---|
| `index()` | Ramifica bienvenida (0 dispositivos) vs panel monitor (≥1). | `DeviceModel::countAllResults`, `UserModel::obtenerPorId`, `PanelService::obtenerVistaPanel`, `dispositivoActivo()` |
| `iniciarDemo()` | Si no hay dispositivos, crea uno simulado de demo y vuelve al panel. | `DeviceProvisioningService::ensureUserSetup` |
| `seleccionarDispositivo()` | Cambia el dispositivo activo; valida pertenencia y lo guarda en `active_device_id`. | `DeviceModel`, sesión |
| `dispositivoActivo()` *(priv.)* | Devuelve el id activo de sesión si pertenece al usuario; si no, lo limpia. | `DeviceModel`, sesión |
| `perfil()` | Muestra el perfil; si el usuario no existe, cierra sesión. | `UserModel::obtenerPorId` |
| `actualizarPerfil()` | Valida, confirma identidad con la contraseña actual, verifica unicidad y guarda. | `UserModel::existeCorreoOUsuarioExcepto`, `UserModel::actualizarPerfil` |
| `actualizarPassword()` | Valida y confirma identidad antes de cambiar el hash. | `UserModel::actualizarHashContrasena` |
| `compra()` | Muestra `compra_mercadopago`. | vista |
| `guardarMedicion()` | Guarda una medición manual y dispara automatización. | `redireccionarSiFaltaDispositivo`, `obtenerContexto`, `SimulationService::createMeasurement` |
| `cambiarModo()` | Cambia automático/manual (lista blanca). | `CommandService::changeOperatingMode`, `AutomationService` |
| `cambiarActuador()` | Enciende/apaga un actuador, solo en modo manual. | `CommandService::queueAndExecuteManualCommand` |

**Helpers privados clave:**
- `crearPanel()` → llama `DeviceProvisioningService::ensureUserSetup($userId, [], false)` y luego `PanelService::obtenerDatos()`.
- `obtenerContexto()` → devuelve `['device' => $panel['device_raw'], 'space' => $panel['space_raw']]`.
- `redireccionarSiFaltaDispositivo()` → **(renombrado)** guard que corta la acción si el usuario tiene 0 dispositivos. Antes se llamaba `redireccionarSiFaltaAmbiente`.
- `estaEnModoManual()`, `modoValido()`, `accionActuadorValida()`, `crearMensajeCambioModo()`.

### 7.3 `DispositivosController`
**Responsabilidad:** "Mis dispositivos" y el alta por código de activación.

| Método | Qué hace | Conexiones |
|---|---|---|
| `index()` | Lista los dispositivos del usuario con metadatos de estado. | `DeviceClaimService::listarDeUsuario` |
| `agregar()` | Prepara el wizard: tipos, catálogo de espacios y ambientes existentes. | `DeviceClaimService::tiposDispositivo/espacios`, `SpaceModel`, `EnvironmentPresetService` |
| `validar()` | Chequeo en vivo del código (JSON). GET → exento de CSRF. | `DeviceClaimService::inspeccionarCodigo` |
| `guardar()` | Valida, decide ambiente existente o nuevo, y vincula dentro de transacción. | `DeviceClaimService::vincular` |

### 7.4 `AmbientesController`
**Responsabilidad:** listar y editar los ambientes del usuario (perfiles de
rangos). Un ambiente puede tener varios dispositivos.

| Método | Qué hace | Conexiones |
|---|---|---|
| `index()` | Lista ambientes con sus rangos y dispositivos asignados. | `SpaceModel`, `DeviceModel`, `EnvironmentPresetService` |
| `editar($id)` | Muestra el form de edición; valida pertenencia (`user_id`). | `SpaceModel::find` |
| `actualizar($id)` | Valida (min < max, CO₂ > 0) y guarda los rangos. | `SpaceModel::update` |

### 7.5 `Api\DeviceApiController`
**Responsabilidad:** la API REST del ESP32. Autentica por **token de
dispositivo**, no por sesión.

| Método | Qué hace | Conexiones |
|---|---|---|
| `storeMeasurement($uid)` | Recibe una medición JSON, la valida y la guarda; corre automatización. | `SimulationService::createMeasurement`, `resolveAuthenticatedDevice` |
| `pendingCommands($uid)` | Devuelve los comandos pendientes del dispositivo. | `CommandService::getPendingCommands` |
| `markCommandExecuted($uid,$id)` | Marca un comando como ejecutado. | `CommandService::markCommandAsExecuted` |

**Autenticación:** `resolveAuthenticatedDevice()` busca el dispositivo por
`device_uid`, lee el token del header `X-Device-Token` (o del parámetro
`api_token`) y lo compara con `devices.api_token`. Si no coincide, lanza
`InvalidArgumentException` → respuesta `401`. Cada llamada actualiza
`last_seen_at` / `last_command_sync_at` con `actualizarActividadDispositivo()`.

---

## 8. Modelos (`app/Models/`)

Cada modelo extiende `CodeIgniter\Model`, representa **una tabla** y declara
`$table`, `$primaryKey`, `$returnType='array'`, `$allowedFields` (campos que se
pueden insertar/actualizar masivamente) y, en la mayoría, `$useTimestamps=true`
(`created_at`/`updated_at` automáticos).

| Modelo | Tabla | Métodos propios destacados |
|---|---|---|
| **UserModel** | `users` | `obtenerPorId`, `buscarParaLogin` (email **o** usuario), `existeCorreoOUsuario`, `existeCorreoOUsuarioExcepto`, `crearUsuario` (hashea con `password_hash`), `actualizarPerfil`, `actualizarHashContrasena`, `guardarToken`/`buscarPorToken` (token **hasheado** SHA-256 + vencimiento), `actualizarPasswordConToken`, `limpiarTokenRecuperacion`. |
| **DeviceModel** | `devices` | `obtenerDeUsuario($userId)`: hace `JOIN` con `spaces` para traer `environment_type` y `custom_name` de cada dispositivo. `allowedFields` incluye los campos nuevos del Hito 2: `device_type`, `status`, `mac_address`, `activation_code`, `notes`, `last_seen_at`, `last_command_sync_at`. |
| **SpaceModel** | `spaces` | Solo configuración base. Guarda el ambiente y sus rangos. *(Ahora admite varias filas por usuario.)* |
| **MeasurementModel** | `measurements` | Solo configuración base. Historial de lecturas (`source`, `temperature`, `humidity`, `co2_ppm`, `air_quality_index/label`, `captured_at`). |
| **DeviceStateModel** | `device_states` | Estado actual: `operating_mode`, `fan_state`, `aromatizer_state`, `alert_led_state`, `last_reason`, `updated_by`. |
| **DeviceCommandModel** | `device_commands` | Cola de comandos: `command_type`, `target_value`, `payload` (JSON con `reason`), `status`, `executed_at`, `source`, `issued_by_user_id`. |
| **DeviceActivationCodeModel** | `device_activation_codes` | `normalizar()` (estático: mayúsculas, sin espacios), `buscarPorCodigo`, `marcarCanjeado` (pone `status='claimed'` + quién/cuándo). |

---

## 9. Servicios (resumen)

Los servicios concentran la lógica de negocio. Aquí solo el mapa; el detalle
completo está en **`services.md`**.

| Servicio | Qué resuelve |
|---|---|
| **PanelService** | Arma **todo** el dashboard a partir de mediciones, estado y ambiente. |
| **AutomationService** | Aplica las reglas automáticas comparando la última medición con los rangos. |
| **CommandService** | Crea y ejecuta comandos y mantiene `device_states`. |
| **SimulationService** | Genera mediciones simuladas (demo / historial). |
| **DeviceProvisioningService** | Prepara la cuenta: ambiente + dispositivo + estado iniciales (usado por la demo). |
| **DeviceClaimService** | Vincula un dispositivo real por código de activación. |
| **EnvironmentPresetService** | Define los presets de ambiente (rangos sugeridos por tipo). |

---

## 10. Sesiones y seguridad

- **Sesión:** claves `user_id`, `user_name`, `is_logged_in` y, en el panel,
  `active_device_id` (dispositivo elegido en el switcher).
- **Regeneración de sesión** en cada login (`session()->regenerate(true)`):
  evita ataques de fijación de sesión.
- **Contraseñas:** siempre con `password_hash`/`password_verify` (bcrypt),
  nunca en texto plano. Rehash automático si el algoritmo cambió.
- **Recuperación:** `reset_token` se guarda **hasheado (SHA-256)** con
  `reset_expires_at`; el enlace solo sirve mientras el token es válido.
- **CSRF:** activo globalmente antes de cada request (excepto `api/*`); todos
  los formularios deben incluir `csrf_field()`.
- **API:** autenticación por token de dispositivo (`X-Device-Token`), no por
  sesión; exenta de CSRF.

---

## 11. Validaciones (qué controla el sistema)

| Acción | Reglas principales |
|---|---|
| **Login** | `usuario` (mín. 3) y `password` requeridos. |
| **Registro** | Nombre/apellido requeridos; email válido y único; usuario único con `[A-Za-z0-9._-]`; contraseña con mayúscula + minúscula + número (mín. 8) y `password_confirm` coincidente. |
| **Perfil** | Nombre y **apellido** requeridos (2–120); email/usuario válidos y únicos salvo el propio; **contraseña actual** obligatoria para confirmar. |
| **Contraseña** | Actual + nueva (con mayús/minús/número) + confirmación. |
| **Medición manual** | Temperatura/humedad decimales; CO₂ entero; índice de aire 0–100. |
| **Modo / actuador** | Solo valores de **lista blanca** (`MODOS`, `ACTUADORES`, `VALORES_ACTUADOR`); control manual solo en modo manual. |
| **Alta de dispositivo** | `code` requerido (≤40), `name` (2–60), `device_type` requerido; ambiente existente (con pertenencia) o nuevo (clave válida del catálogo). |
| **Editar ambiente** | `min_temperature < max_temperature`, `min_humidity < max_humidity`, `max_co2 > 0`. |

---

# 12. BASE DE DATOS (sección detallada)

La base se llama **`tesina_esp32`**. Idea central: un **usuario** tiene uno o
varios **ambientes** (con sus rangos ideales) y uno o varios **dispositivos**;
cada dispositivo genera **mediciones** y tiene un **estado** de actuadores; y
existe una **cola de comandos** para hablar con el hardware. Un **código de
activación** vincula un EdenAir físico con una cuenta.

### 12.1 Tablas principales y qué guarda cada campo

#### `users` — cuentas de usuario
| Campo | Significado |
|---|---|
| `id` | Identificador único. |
| `nombre`, `apellido` | Datos personales (apellido **requerido**). |
| `email` *(único)* | Correo; sirve para login y recuperación. |
| `usuario` *(único)* | Nombre de usuario para login. |
| `password_hash` | Contraseña **encriptada** (bcrypt). |
| `reset_token`, `reset_expires_at` | Token de recuperación (hasheado) y vencimiento. |
| `created_at`, `updated_at` | Fechas automáticas. |

#### `spaces` — el ambiente del usuario (perfil de rangos)
Define qué es "normal" para ese espacio. **Ahora puede haber varios por usuario.**
| Campo | Significado |
|---|---|
| `id` | Identificador del ambiente. |
| `user_id` *(FK→users, índice normal)* | A qué usuario pertenece. |
| `environment_type` | Tipo (oficina, aula, hogar, dormitorio, personalizable). |
| `custom_name` | Nombre propio si es personalizable. |
| `min/max_temperature`, `min/max_humidity`, `max_co2` | Rangos ideales / límite de CO₂. |

> Cada medición se compara contra estos rangos para decidir si el estado es
> **normal, advertencia o crítico** y para disparar la automatización.

#### `devices` — dispositivos EdenAir (ESP32)
| Campo | Significado |
|---|---|
| `id` | Identificador. |
| `user_id` *(FK→users)* | Dueño. |
| `space_id` *(FK→spaces)* | Ambiente asociado. |
| `name` | Nombre visible. |
| `device_type` | Tipo elegido en el alta (Eden Air Core, Monitor ambiental, etc.). |
| `device_uid` *(único)* | Identificador público usado en la URL de la API. |
| `api_token` *(único)* | Credencial secreta del ESP32 (header `X-Device-Token`). |
| `is_simulated` | 1 si es demo/simulado. |
| `is_active` | 1 si es el visible por defecto en el panel. |
| `status` | `simulated` / `active` / `offline` / `pending`. |
| `mac_address` | Dato **técnico interno**, nunca credencial. |
| `activation_code` | Código con el que se vinculó. |
| `notes` | Notas opcionales. |
| `last_seen_at`, `last_command_sync_at` | Última actividad y última sincronización de comandos. |

#### `device_states` — estado actual de cada dispositivo
| Campo | Significado |
|---|---|
| `device_id` *(FK→devices)* | Un estado por dispositivo. |
| `operating_mode` | `automatic` o `manual`. |
| `fan_state`, `aromatizer_state`, `alert_led_state` | `on`/`off` de cada actuador. |
| `last_reason` | Por qué quedó así (última regla aplicada). |
| `updated_by` | Quién lo cambió (system / web / device-api…). |

#### `measurements` — mediciones (fuente del dashboard)
| Campo | Significado |
|---|---|
| `id` | Identificador. |
| `device_id` / `user_id` / `space_id` *(FK)* | A qué dispositivo, usuario y ambiente pertenece. |
| `source` | `device`/`api` (ESP32), `web` (manual), `seed`/`sim` (simulada). |
| `temperature`, `humidity` | °C y %. |
| `co2_ppm` | CO₂ en partes por millón. |
| `air_quality_index` (0–100), `air_quality_label` | Índice y etiqueta legible. |
| `captured_at` | Momento de la medición. |

#### `device_commands` — cola de comandos hacia el ESP32
| Campo | Significado |
|---|---|
| `device_id` *(FK→devices)* | A qué dispositivo. |
| `issued_by_user_id` *(FK→users)* | Quién lo originó (si fue persona). |
| `source` | `web` (usuario) o `automation`. |
| `command_type`, `target_value` | Qué hacer (ej.: `fan` → `on`). |
| `payload` | JSON con `reason`. |
| `status`, `executed_at` | `pending`/`executed`/`cancelled` y cuándo. |

#### `device_activation_codes` — vinculación de hardware real
Guarda los **claim codes** físicos: `code` *(único)*, `device_type`,
`default_name`, `mac_address`, `status` (`available`/`claimed`/`disabled`),
`claimed_by_user_id`, `device_id`, `claimed_at`, `batch`.

#### Tablas heredadas (legacy) e internas
- **`ambientes`** y **`lecturas_ambientales`**: modelo inicial; reemplazadas por
  `spaces` + `measurements`. Se conservan por historia del proyecto.
- **`migrations`**: control interno de CI4 de qué migraciones se aplicaron.

### 12.2 Relaciones entre tablas

```
users ──1:N── spaces             (un usuario, varios ambientes)   ← cambió: antes 1:1
users ──1:N── devices            (un usuario, varios dispositivos)
spaces ─1:N── devices            (un ambiente, varios dispositivos)
devices ─1:1── device_states     (un dispositivo, un estado)
devices ─1:N── device_commands   (un dispositivo, muchos comandos)
devices ─1:N── measurements      (un dispositivo, muchas mediciones)
device_activation_codes ─1:1── devices  (un código canjea un dispositivo)
```

> **Migración clave del Hito 2:** `spaces` tenía `UNIQUE(user_id)` (un solo
> ambiente por usuario). La migración `...AllowMultipleSpacesPerUser` lo
> reemplaza por un índice normal — primero crea el índice nuevo (la FK
> `fk_spaces_user` exige un índice sobre `user_id`) y después quita el `UNIQUE`.
> Sin esto no se podrían tener varios dispositivos con su propio ambiente.

### 12.3 Cómo la base impacta en lo que se ve

1. El usuario abre `/panel`.
2. `PanelService` lee la **última medición** y el **estado** del dispositivo
   activo, y los **rangos** de su ambiente (`spaces`).
3. Compara cada valor contra esos rangos → calcula el **estado general**
   (normal/advertencia/crítico), arma tarjetas, historial y reglas.
4. La vista recibe ese paquete ya calculado y lo dibuja.

En otras palabras: **todo lo que se ve en el dashboard es un reflejo de estas
tablas interpretadas por los servicios.**

---

## 13. Glosario y líneas de código importantes

### Términos

| Término | Significado |
|---|---|
| **MVC** | Modelo–Vista–Controlador: separa datos, presentación y orquestación. |
| **Servicio (Service)** | Clase con lógica de negocio reutilizable, fuera del controlador y la vista. |
| **Filtro (Filter)** | Código que corre antes/después del controlador (aquí: control de acceso `auth`/`guest` y `csrf`). |
| **Sesión** | Datos del usuario logueado guardados en servidor (`user_id`, `user_name`, `is_logged_in`, `active_device_id`). |
| **CSRF** | Token anti-falsificación de formularios; activo global salvo `api/*`. |
| **`allowedFields`** | Lista blanca de columnas que un modelo permite insertar/actualizar masivamente. |
| **`device_uid`** | Identificador público del dispositivo, va en la URL de la API. |
| **`api_token`** | Secreto privado del dispositivo para autenticar sus llamadas (header `X-Device-Token`). |
| **Claim code** | Código `EDEN-XXXX-XXXX` que viene con el producto físico; de un solo uso. |
| **Preset de ambiente** | Conjunto de rangos sugeridos según el tipo de espacio (oficina, aula…). |
| **Modo automático/manual** | `operating_mode`: en automático el sistema decide actuadores; en manual decide el usuario. |
| **Estado general** | Diagnóstico calculado (normal/advertencia/crítico) comparando mediciones vs rangos. |
| **`active_device_id`** | Dispositivo elegido en el switcher del panel (en sesión). |
| **Migración** | Cambio versionado de la estructura de la base. |

### Líneas de código explicadas

```php
// AccesoController::redirigirDespuesDelLogin() — el núcleo de la nueva lógica.
// Siempre manda al panel; ya NO evalúa si hay ambiente.
return redirect()->to('/panel');
```

```php
// GuestFilter::before() — si ya hay sesión, no tiene sentido ver login/registro.
if (! session()->get('user_id')) { return null; }   // invitado → pasa
return redirect()->to('/panel');                      // logueado → al panel
```

```php
// PanelController::index() — decide la pantalla según cantidad de dispositivos.
$cantidadDispositivos = (new DeviceModel())->where('user_id', $userId)->countAllResults();
if ($cantidadDispositivos === 0) { return view('panel/bienvenida', [...]); }
```

```php
// AccesoController::iniciarSesion() — regenerar la sesión evita fijación de sesión.
session()->regenerate(true);
```

```php
// UserModel::buscarParaLogin() — permite loguear con email O usuario.
$this->groupStart()->where('email', strtolower($id))->orWhere('usuario', $id)->groupEnd()->first();
```

```php
// UserModel::hashToken() — el token de recuperación se guarda hasheado, no en claro.
return hash('sha256', trim($token));
```

```php
// DeviceApiController::resolveAuthenticatedDevice() — la API autentica por token, no por sesión.
if ($token !== (string) $device['api_token']) {
    throw new \InvalidArgumentException('Token de dispositivo invalido.');   // → 401
}
```

```php
// DeviceModel::obtenerDeUsuario() — trae el dispositivo con el nombre de su ambiente (JOIN).
$this->select('devices.*, spaces.environment_type, spaces.custom_name')
     ->join('spaces', 'spaces.id = devices.space_id', 'left')
     ->where('devices.user_id', $userId)->findAll();
```

```php
// Filters.php — CSRF global antes de cada request, salvo la API.
'csrf' => ['except' => ['api/*']],
```
