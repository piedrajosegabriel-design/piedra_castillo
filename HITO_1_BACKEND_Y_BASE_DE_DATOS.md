# HITO 1 — Backend, arquitectura y base de datos

Documento técnico de EdenAir. Explica **cómo está construido el sistema por
dentro**: la arquitectura, el backend (rutas, controladores, modelos,
servicios, sesiones y validaciones) y, en una sección aparte y detallada, la
**base de datos**. Está escrito para poder estudiarlo, explicarlo y defenderlo.

---

## 1. Qué es EdenAir y con qué está hecho

EdenAir es un **sistema de monitoreo y ambientación inteligente de espacios
interiores**. Mide temperatura, humedad, CO₂ y calidad del aire con un módulo
**ESP32**, muestra todo en un **dashboard** web y puede **automatizar**
actuadores (ventilación, humidificación/aromatizador y un LED de alerta).

**Stack técnico**

| Capa | Tecnología |
|---|---|
| Lenguaje / Framework | PHP 8.2 + **CodeIgniter 4** (patrón MVC) |
| Servidor | Apache (XAMPP) |
| Base de datos | MySQL / MariaDB (`tesina_esp32`) |
| Hardware (IoT) | ESP32 que envía mediciones y recibe comandos por una API REST |
| Frontend | HTML + CSS propio (sistema de tokens) + JavaScript |

---

## 2. Arquitectura general (MVC)

El proyecto sigue el patrón **Modelo–Vista–Controlador** de CodeIgniter 4, con
una capa extra de **Servicios** para la lógica de negocio:

```
Navegador / ESP32
      │  (HTTP)
      ▼
[ Rutas ]  app/Config/Routes.php       → decide qué controlador atiende
      ▼
[ Filtros ] AuthFilter / GuestFilter   → controlan el acceso (sesión)
      ▼
[ Controladores ] app/Controllers/...  → reciben el pedido, validan, responden
      ▼
[ Servicios ] app/Services/...         → lógica de negocio (cálculos, reglas)
      ▼
[ Modelos ] app/Models/...             → hablan con la base de datos
      ▼
[ Vistas ] app/Views/...               → arman el HTML que ve el usuario
```

**Idea clave:** el controlador no hace cálculos pesados ni arma el HTML a mano.
Pide los datos a un **servicio**, el servicio usa **modelos** para leer/escribir
en la base, y la **vista** solo recorre esos datos y los dibuja. Eso mantiene
cada parte ordenada y fácil de mantener.

---

## 3. Organización del backend (carpetas)

| Carpeta | Qué contiene |
|---|---|
| `app/Config/` | Configuración: rutas (`Routes.php`), filtros, base de datos, email. |
| `app/Controllers/` | Controladores: el punto de entrada de cada acción. |
| `app/Controllers/Api/` | Controlador de la **API REST** que usa el ESP32. |
| `app/Models/` | Modelos: una clase por tabla, encapsulan las consultas. |
| `app/Services/` | Lógica de negocio reutilizable (reglas, simulación, armado del panel). |
| `app/Filters/` | Filtros de acceso (`auth`, `guest`). |
| `app/Views/` | Vistas (HTML/PHP) y parciales reutilizables. |
| `writable/` | Sesiones, logs y caché (lo que el framework necesita escribir). |

---

## 4. Rutas (cómo se llega a cada parte)

Las rutas están en `app/Config/Routes.php`. Se agrupan según **quién puede
entrar**:

### 4.1 Públicas (cualquiera)
| Método | Ruta | Controlador | Qué hace |
|---|---|---|---|
| GET | `/` | `AccesoController::inicio` | Landing page. |
| GET | `/portfolio` | `PortfolioController::index` | Portfolio del proyecto. |

### 4.2 Grupo `guest` (solo si NO hay sesión iniciada)
Protegidas por el filtro **GuestFilter**: si ya estás logueado, te manda al panel.

| Método | Ruta | Acción |
|---|---|---|
| GET/POST | `/login` | Mostrar y procesar el login. |
| GET/POST | `/registro` (y `/register`) | Crear una cuenta. |
| GET/POST | `/recuperar` | Pedir recuperación de contraseña por email. |
| GET/POST | `/restablecer/{token}` | Definir una nueva contraseña con un token válido. |

### 4.3 Grupo `panel` (solo con sesión — filtro `auth`)
| Método | Ruta | Acción |
|---|---|---|
| GET | `/panel` (y `/dashboard`) | Dashboard principal. |
| GET | `/panel/ambiente` · POST | Elegir/guardar el ambiente (perfil de rangos). |
| GET/POST | `/panel/perfil`, `/panel/password` | Editar datos de cuenta y contraseña. |
| GET | `/panel/compra` | Pantalla de **compra del producto**. |
| GET/POST | `/panel/dispositivos`, `/dispositivos/agregar` | Listar y vincular dispositivos. |
| POST | `/panel/dispositivo-activo` | Cambiar el dispositivo que se está viendo. |
| POST | `/panel/demo` | Cargar un dispositivo de demostración. |
| GET/POST | `/panel/ambientes`, `/ambientes/{id}/editar` | Listar y editar ambientes. |
| POST | `/panel/medicion` | Cargar una medición manual. |
| POST | `/panel/modo` | Cambiar modo automático/manual. |
| POST | `/panel/actuador` | Encender/apagar un actuador (en modo manual). |
| GET | `/logout` | Cerrar sesión. |

### 4.4 API REST para el ESP32 (`/api/devices/...`)
| Método | Ruta | Acción |
|---|---|---|
| POST | `/api/devices/{uid}/measurements` | El ESP32 **envía una medición**. |
| GET | `/api/devices/{uid}/commands/pending` | El ESP32 **pide comandos pendientes**. |
| POST | `/api/devices/{uid}/commands/{id}/executed` | El ESP32 **avisa que ejecutó** un comando. |

---

## 5. Controladores (quién atiende cada acción)

| Controlador | Responsabilidad |
|---|---|
| **AccesoController** | Todo lo público y de cuenta: landing, login, registro, recuperación/restablecimiento de contraseña, logout y selección de ambiente. |
| **PanelController** | El corazón del área privada: arma el dashboard, edita perfil/contraseña, muestra la compra, cambia el modo de operación, controla actuadores, registra mediciones manuales, cambia el dispositivo activo y carga la demo. |
| **DispositivosController** | Listado y **vinculación** de dispositivos (alta con código de activación, validación). |
| **AmbientesController** | Listado y edición de los **ambientes** (perfiles de rangos ideales). |
| **PortfolioController** | Renderiza el portfolio del proyecto. |
| **Api\DeviceApiController** | La **API REST** que conecta el ESP32 con el sistema (guardar mediciones, entregar comandos, confirmar ejecución). |

---

## 6. Sesiones, autenticación y filtros

- **Login:** al ingresar usuario + contraseña, el sistema busca al usuario y
  compara la contraseña con `password_verify()` contra el `password_hash`
  guardado (hash **bcrypt**, nunca texto plano). Si coincide, se **regenera la
  sesión** (por seguridad) y se guardan en sesión: `user_id`, `user_name` e
  `is_logged_in`.
- **Filtro `auth` (AuthFilter):** protege todo el grupo `/panel`. Si no hay
  sesión válida, redirige al login. Así ninguna pantalla privada se puede abrir
  sin estar logueado.
- **Filtro `guest` (GuestFilter):** protege login/registro/recuperación. Si ya
  estás logueado, te lleva directo al panel (no tiene sentido volver a loguearte).
- **Recuperación de contraseña:** genera un `reset_token` con vencimiento
  (`reset_expires_at`), envía un email con un enlace y permite definir una nueva
  contraseña solo mientras el token es válido.
- **CSRF:** todos los formularios incluyen el token CSRF de CodeIgniter, que
  evita envíos falsificados desde otros sitios.

---

## 7. Modelos (acceso a datos)

Cada modelo representa **una tabla** y concentra sus consultas. Así el resto del
código no escribe SQL suelto.

| Modelo | Tabla | Para qué |
|---|---|---|
| **UserModel** | `users` | Crear usuarios, buscarlos para login, actualizar perfil/contraseña, manejar tokens de recuperación. |
| **DeviceModel** | `devices` | Dispositivos del usuario (alta, búsqueda, dispositivo activo). |
| **SpaceModel** | `spaces` | El ambiente configurado del usuario y sus **rangos ideales**. |
| **MeasurementModel** | `measurements` | Mediciones (lecturas) que alimentan el dashboard. |
| **DeviceStateModel** | `device_states` | Estado actual de los actuadores y el modo de operación. |
| **DeviceCommandModel** | `device_commands` | Cola de **comandos** hacia el ESP32. |
| **DeviceActivationCodeModel** | `device_activation_codes` | Códigos para vincular un EdenAir real. |

---

## 8. Servicios (lógica de negocio)

Los servicios son el "cerebro": cálculos y reglas que no deben estar ni en el
controlador ni en la vista.

| Servicio | Qué resuelve |
|---|---|
| **PanelService** | Arma **todo** el dashboard: toma las mediciones, calcula estados (normal/advertencia/crítico), valores actuales, tarjetas de sensores, reglas de automatización visibles, historial y la mini-tendencia. La vista solo dibuja lo que este servicio devuelve. |
| **AutomationService** | Aplica las **reglas automáticas**: compara la última medición con los rangos del ambiente y decide qué actuador encender/apagar. |
| **CommandService** | Crea y ejecuta **comandos** (cambiar modo, forzar un actuador) y mantiene el `device_states`. |
| **SimulationService** | Genera mediciones **simuladas** (modo demo, sin hardware real). |
| **DeviceProvisioningService** | Prepara la cuenta: crea el dispositivo/ambiente inicial o el de demostración. |
| **DeviceClaimService** | Vincula un dispositivo real a partir de su código de activación. |
| **EnvironmentPresetService** | Define los **presets** de ambiente (hogar, oficina, aula, dormitorio) con sus rangos sugeridos. |

---

## 9. Flujo de información (3 recorridos clave)

**A. Login y entrada al panel**
1. El usuario envía usuario + contraseña → `AccesoController::validarLogin`.
2. Se valida el formulario y se verifica la contraseña (bcrypt).
3. Se inicia sesión y se redirige a `/panel`.
4. `PanelController::index` pide los datos a `PanelService` y muestra el dashboard
   (o la pantalla de bienvenida si todavía no hay dispositivos).

**B. El ESP32 reportando datos (IoT)**
1. El ESP32 hace `POST /api/devices/{uid}/measurements` con su `api_token`.
2. `DeviceApiController` valida el token y guarda la fila en `measurements`.
3. `AutomationService` compara esa medición con los rangos del `space` y, si hace
   falta, deja un **comando** en `device_commands` y actualiza `device_states`.
4. El ESP32 hace `GET /api/devices/{uid}/commands/pending`, ejecuta lo que le toca
   y confirma con `POST .../executed`.
5. La próxima vez que el usuario abre el dashboard, ve la medición y el estado nuevo.

**C. El usuario controlando el ambiente**
1. En modo **manual**, el usuario toca un interruptor de actuador → `POST /panel/actuador`.
2. `PanelController::cambiarActuador` valida y llama a `CommandService`.
3. Se encola y ejecuta el comando, y se actualiza `device_states`.
4. El dashboard refleja el nuevo estado.

---

## 10. Validaciones (qué controla el sistema)

| Acción | Reglas principales |
|---|---|
| **Login** | Usuario (mín. 3) y contraseña requeridos. |
| **Registro** | Nombre/apellido requeridos, email válido y único, usuario único con caracteres permitidos, contraseña con mayúscula + minúscula + número (mín. 8) y confirmación que coincide. |
| **Ambiente** | Tipo dentro de una lista permitida; los rangos (temperatura/humedad) deben venir completos y con mínimo < máximo; CO₂ máximo > 0. |
| **Perfil / contraseña** | Mismas reglas de formato + verificación de la contraseña actual antes de guardar cambios. |
| **Medición manual** | Temperatura/humedad decimales, CO₂ entero, índice de aire 0–100. |
| **Modo / actuador** | Solo valores de una **lista blanca** (modos válidos, actuadores válidos, on/off). El control manual solo funciona en modo manual. |

---

# 11. BASE DE DATOS (sección detallada)

La base se llama **`tesina_esp32`**. Está pensada alrededor de una idea simple:
un **usuario** tiene un **ambiente** (con sus rangos ideales) y uno o más
**dispositivos**; cada dispositivo genera **mediciones** y tiene un **estado** de
actuadores; y existe una **cola de comandos** para hablar con el hardware.

### 11.1 Tablas principales y qué guarda cada campo

#### `users` — cuentas de usuario
| Campo | Significado |
|---|---|
| `id` | Identificador único del usuario. |
| `nombre`, `apellido` | Datos personales. |
| `email` *(único)* | Correo, también sirve para login y recuperación. |
| `usuario` *(único)* | Nombre de usuario para login. |
| `password_hash` | Contraseña **encriptada** (bcrypt). Nunca se guarda en texto plano. |
| `reset_token`, `reset_expires_at` | Token temporal para restablecer la contraseña. |
| `created_at`, `updated_at` | Fechas de alta y última modificación. |

#### `spaces` — el ambiente del usuario (perfil de rangos)
Es el **corazón de la lógica ambiental**: define qué es "normal" para ese usuario.
| Campo | Significado |
|---|---|
| `id` | Identificador del ambiente. |
| `user_id` *(único, FK→users)* | A qué usuario pertenece. Es **único**: un ambiente activo por usuario. |
| `environment_type` | Tipo de espacio (hogar, oficina, aula, dormitorio, personalizable). |
| `custom_name` | Nombre propio si es personalizado. |
| `min_temperature` / `max_temperature` | Rango ideal de temperatura. |
| `min_humidity` / `max_humidity` | Rango ideal de humedad. |
| `max_co2` | Límite máximo de CO₂ aceptable. |

> **Para qué sirve:** cada medición se compara contra estos rangos para decidir
> si el estado es **normal, advertencia o crítico**, y para disparar la
> automatización.

#### `devices` — dispositivos EdenAir (ESP32)
| Campo | Significado |
|---|---|
| `id` | Identificador del dispositivo. |
| `user_id` *(FK→users)* | Dueño del dispositivo. |
| `space_id` *(FK→spaces)* | Ambiente al que está asociado. |
| `name` | Nombre visible ("Sala", "Oficina"…). |
| `device_uid` *(único)* | Identificador público usado en la URL de la API. |
| `api_token` *(único)* | Credencial secreta para que el ESP32 use la API. |
| `is_simulated` | 1 si es un dispositivo de demo (sin hardware real). |
| `is_active` | 1 si es el dispositivo que se está viendo en el panel. |
| `status`, `last_seen_at`, `last_command_sync_at` | Estado de conexión y última actividad. |
| `mac_address`, `activation_code`, `notes` | Datos del hardware real al vincularlo. |

#### `device_states` — estado actual de cada dispositivo
| Campo | Significado |
|---|---|
| `device_id` *(único, FK→devices)* | Un estado por dispositivo. |
| `operating_mode` | `automatic` o `manual`. |
| `fan_state` | Ventilador `on`/`off`. |
| `aromatizer_state` | Aromatizador/humidificador `on`/`off`. |
| `alert_led_state` | LED de alerta `on`/`off`. |
| `last_reason` | Por qué quedó en ese estado (la última regla aplicada). |
| `updated_by` | Quién lo cambió (sistema, usuario, ESP32). |

#### `measurements` — mediciones (lo que ve el dashboard)
| Campo | Significado |
|---|---|
| `id` | Identificador de la medición. |
| `device_id` / `user_id` / `space_id` *(FK)* | A qué dispositivo, usuario y ambiente pertenece. |
| `source` | Origen: `device` (ESP32), `web` (carga manual) o `sim` (simulada). |
| `temperature`, `humidity` | Temperatura (°C) y humedad (%). |
| `co2_ppm` | Dióxido de carbono en partes por millón. |
| `air_quality_index` | Índice de calidad de aire (0–100). |
| `air_quality_label` | Etiqueta legible ("Buena", "Regular"…). |
| `captured_at` | Cuándo se tomó la medición. |

> **Para qué sirve:** es la **fuente principal del dashboard**. Las tarjetas de
> sensores, el índice general, el historial y la tendencia salen de acá.

#### `device_commands` — cola de comandos hacia el ESP32
| Campo | Significado |
|---|---|
| `device_id` *(FK→devices)* | A qué dispositivo va el comando. |
| `issued_by_user_id` *(FK→users)* | Quién lo originó (si fue una persona). |
| `source` | Origen del comando (usuario, automatización). |
| `command_type`, `target_value` | Qué hacer (ej.: encender ventilador → `on`). |
| `status`, `executed_at` | Si está pendiente/ejecutado y cuándo. |

#### `device_activation_codes` — vinculación de hardware real
Guarda los **códigos** que vienen con un EdenAir físico para asociarlo a una
cuenta (`code`, `status`, `claimed_by_user_id`, `device_id`, `claimed_at`…).

#### Tablas heredadas (legacy)
- **`ambientes`** y **`lecturas_ambientales`**: versión inicial del modelo de
  datos (ambientes con un enum simple y sus lecturas). Hoy el sistema usa
  `spaces` + `measurements`, más completas. Se conservan por compatibilidad
  histórica del proyecto.
- **`migrations`**: tabla interna de CodeIgniter que lleva el control de qué
  migraciones (cambios de estructura) ya se aplicaron.

### 11.2 Relaciones entre tablas

```
users ──1:1── spaces            (un usuario, un ambiente activo)
users ──1:N── devices           (un usuario, varios dispositivos)
spaces ─1:N── devices           (un ambiente, varios dispositivos)
devices ─1:1── device_states    (un dispositivo, un estado)
devices ─1:N── device_commands  (un dispositivo, muchos comandos)
devices ─1:N── measurements     (un dispositivo, muchas mediciones)
ambientes ─1:N── lecturas_ambientales   (legacy)
```

Estas relaciones están reforzadas con **claves foráneas** reales en la base, lo
que garantiza que no queden datos "sueltos" (por ejemplo, una medición sin
dispositivo).

### 11.3 Cómo la base impacta en lo que se ve en pantalla

1. El usuario abre `/panel`.
2. `PanelService` lee la **última medición** (`measurements`) del dispositivo
   activo y el **estado** (`device_states`).
3. Compara cada valor contra los **rangos del ambiente** (`spaces`).
4. Con eso calcula el **estado general** (normal/advertencia/crítico), arma las
   **tarjetas de sensores**, el **historial** y las **reglas de automatización**.
5. La vista del dashboard recibe ese paquete de datos ya calculado y lo dibuja.

En otras palabras: **todo lo que el usuario ve en el dashboard es un reflejo
directo de estas tablas**, interpretadas por los servicios. Cambiar un rango en
`spaces` cambia los estados; una medición nueva en `measurements` cambia los
números; un comando en `device_commands` cambia lo que hace el hardware.
