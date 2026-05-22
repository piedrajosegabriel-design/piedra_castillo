# Documentación EdenAir

> Guía completa para entender el proyecto sin necesidad de saber CodeIgniter 4 de memoria.
> Pensada para que cualquier persona (incluyéndote vos en 3 meses) pueda abrir el código y entender de dónde sale cada cosa.

---

## 1. Qué es EdenAir

EdenAir es una **plataforma web de monitoreo y automatización ambiental**.
Permite que un microcontrolador **ESP32** (real o simulado por la web) mida temperatura, humedad, CO₂ y calidad de aire de un espacio, los guarde en una base de datos MySQL y, según reglas configuradas, encienda o apague actuadores (aire acondicionado, aromatizador, LED de alerta).

**Problema que resuelve:** dar una vista clara, en un único panel, del estado del aire interior — y permitir actuar manual o automáticamente sobre él.

**Estado actual:** funciona end-to-end con datos simulados. La estructura para enchufar el ESP32 real ya está lista (sección 11).

---

## 2. Stack técnico

- PHP 8.2
- CodeIgniter 4 (framework MVC)
- MySQL (XAMPP local)
- HTML + CSS + JavaScript vanilla (sin frameworks frontend)
- Sin Node.js, sin React, sin librerías externas en el front

URL local: `http://localhost/piedra_castillo/public/`

---

## 3. Estructura del proyecto

```
piedra_castillo/
├── app/                          BACKEND — todo el código PHP
│   ├── Config/                   Configuración del framework (rutas, BD, filtros)
│   ├── Controllers/              Reciben peticiones HTTP, devuelven vistas o JSON
│   │   ├── AccesoController.php  Login, registro, logout, selección de ambiente
│   │   ├── PanelController.php   Dashboard + acciones (medir, cambiar modo/actuador)
│   │   └── Api/
│   │       └── DeviceApiController.php   API REST para el ESP32
│   ├── Models/                   Capa de datos — un archivo por tabla MySQL
│   ├── Services/                 Lógica de negocio reutilizable (NO toca HTTP)
│   ├── Filters/                  Middleware: AuthFilter, GuestFilter
│   ├── Views/                    FRONTEND — plantillas PHP que arman HTML
│   │   ├── partials/             Pedazos reutilizables (head, navbar, footer, logo)
│   │   ├── inicio.php            Landing pública
│   │   ├── login.php             Form de inicio de sesión
│   │   ├── registro.php          Form de registro
│   │   ├── seleccion_ambiente.php Elegir oficina/aula/hogar/dormitorio/custom
│   │   └── panel.php             Dashboard principal (usuario logueado)
│   └── Database/
│       ├── Migrations/           Crea las 6 tablas
│       └── Seeds/                Crea el usuario "demo"
│
├── public/                       FRONTEND ESTÁTICO — accesible por navegador
│   ├── index.php                 Punto de entrada (lo carga Apache)
│   ├── CSS/
│   │   ├── eden-brand.css        Tokens de diseño + branding (logo, colores, modo oscuro)
│   │   ├── dashboard.css         Layout del panel (sidebar, cards, tablas)
│   │   └── inicio.css            Landing pública
│   ├── JS/
│   │   ├── tema.js               Toggle claro/oscuro (lo usa todo el sitio)
│   │   ├── dashboard.js          Loader, sidebar, scroll-spy del panel
│   │   ├── inicio.js             Carrusel y animaciones de la landing
│   │   ├── login.js              Mostrar/ocultar contraseña
│   │   ├── registro.js           Validación visual del form de registro
│   │   ├── ambiente.js           Resaltado al elegir un preset
│   │   └── panel.js              (Mínimo — auxiliar histórico)
│   └── assets/                   Imágenes, favicons, branding
│
├── writable/                     Cache, logs, sesiones (vacíos en git)
├── system/                       Núcleo del framework — NO TOCAR
├── tests/                        Tests (estructura inicial)
├── .env                          Configuración de la base de datos (local)
├── composer.json                 Dependencias PHP
└── README.md                     Setup rápido + endpoints API
```

**Regla mental:**
- `app/Controllers/` y `app/Services/` y `app/Models/` → **backend**
- `app/Views/` y `public/` → **frontend**

---

## 4. MVC — quién hace qué

```
Navegador
   │
   ▼
public/index.php  ────►  Router (app/Config/Routes.php)
                                │
                                ▼
                          Filter (Auth / Guest)
                                │
                                ▼
                          Controller (recibe la petición)
                                │
                                ├──► Service (lógica de negocio)
                                │         │
                                │         ▼
                                │      Model (consulta MySQL)
                                │         │
                                │         ▼
                                │       MySQL (tablas)
                                │
                                ▼
                            View (arma HTML)
                                │
                                ▼
                          Navegador
```

| Capa | Carpeta | Responsabilidad | Ejemplo |
|------|---------|-----------------|---------|
| **Controller** | `app/Controllers/` | Lee el request, valida y delega | `PanelController::index()` |
| **Service** | `app/Services/` | Lógica de negocio reusable | `AutomationService::processMeasurement()` |
| **Model** | `app/Models/` | Acceso a una tabla MySQL | `MeasurementModel` |
| **View** | `app/Views/` | Arma HTML (no decide nada) | `panel.php` |
| **Filter** | `app/Filters/` | Bloquea/redirige antes de entrar | `AuthFilter` |

---

## 5. Rutas — qué URL ejecuta qué

Archivo: [`app/Config/Routes.php`](app/Config/Routes.php)

### Públicas (sin sesión)

| Método | URL | Controlador → método | Vista |
|--------|-----|----------------------|-------|
| GET | `/` | `AccesoController::inicio` | `inicio.php` |
| GET | `/login` | `AccesoController::login` | `login.php` |
| POST | `/login` | `AccesoController::validarLogin` | redirect |
| GET | `/registro` o `/register` | `AccesoController::registro` | `registro.php` |
| POST | `/registro` o `/register` | `AccesoController::guardarRegistro` | redirect |

### Requieren sesión (filtro `auth`)

| Método | URL | Controlador → método | Vista |
|--------|-----|----------------------|-------|
| GET | `/logout` | `AccesoController::logout` | redirect a login |
| GET | `/panel/ambiente` | `AccesoController::seleccionAmbiente` | `seleccion_ambiente.php` |
| POST | `/panel/ambiente` | `AccesoController::guardarAmbiente` | redirect a panel |
| GET | `/panel` o `/dashboard` | `PanelController::index` | `panel.php` |
| POST | `/panel/medicion` | `PanelController::guardarMedicion` | redirect a panel |
| POST | `/panel/modo` | `PanelController::cambiarModo` | redirect a panel |
| POST | `/panel/actuador` | `PanelController::cambiarActuador` | redirect a panel |

### API REST para ESP32 (sin sesión web, autenticada con `X-Device-Token`)

| Método | URL | Controlador → método |
|--------|-----|----------------------|
| POST | `/api/devices/{uid}/measurements` | `DeviceApiController::storeMeasurement` |
| GET | `/api/devices/{uid}/commands/pending` | `DeviceApiController::pendingCommands` |
| POST | `/api/devices/{uid}/commands/{id}/executed` | `DeviceApiController::markCommandExecuted` |

---

## 6. Controladores en detalle

### AccesoController — autenticación y onboarding
[`app/Controllers/AccesoController.php`](app/Controllers/AccesoController.php)

| Método | Qué hace |
|--------|----------|
| `inicio()` | Muestra la landing pública |
| `login()` | Muestra el form de login |
| `validarLogin()` | Verifica credenciales, regenera sesión, redirige |
| `registro()` | Muestra el form de registro |
| `guardarRegistro()` | Valida y crea el usuario; redirige a login |
| `seleccionAmbiente()` | Muestra los presets de ambiente |
| `guardarAmbiente()` | Crea Space + Device + DeviceState + mediciones iniciales |
| `logout()` | Cierra sesión |

### PanelController — el dashboard y sus acciones
[`app/Controllers/PanelController.php`](app/Controllers/PanelController.php)

| Método | Qué hace | Llamado por |
|--------|----------|-------------|
| `index()` | Arma datos del panel vía `PanelService` y muestra `panel.php` | `GET /panel` |
| `guardarMedicion()` | Crea una medición simulada y dispara automation | Form del panel |
| `cambiarModo()` | Cambia entre `automatic` y `manual` | Botón del panel |
| `cambiarActuador()` | Enciende/apaga fan / aromatizer / alert_led (sólo manual) | Botón del panel |

### Api\DeviceApiController — interfaz para el ESP32
[`app/Controllers/Api/DeviceApiController.php`](app/Controllers/Api/DeviceApiController.php)

Autentica al dispositivo por `X-Device-Token` y procesa:
- recepción de mediciones (`storeMeasurement`)
- consulta de comandos pendientes (`pendingCommands`)
- confirmación de comando ejecutado (`markCommandExecuted`)

Devuelve siempre JSON.

---

## 7. Servicios — la lógica de negocio

| Servicio | Para qué sirve |
|----------|----------------|
| `PanelService` | Arma TODOS los datos que necesita el dashboard (métricas, gráficos, actuadores, alertas, historial). |
| `AutomationService` | Aplica las reglas: dada una medición, decide si encender ventilador / aromatizador / LED. |
| `SimulationService` | Genera mediciones (aleatorias o con valores manuales) y precarga 6 lecturas iniciales. |
| `CommandService` | Inserta comandos en la cola, los marca como ejecutados y mantiene el estado del dispositivo. |
| `DeviceProvisioningService` | Cuando un usuario nuevo entra, crea su Space + Device + DeviceState + mediciones iniciales. |
| `EnvironmentPresetService` | Maneja los 5 perfiles de ambiente (oficina, aula, hogar, dormitorio, personalizable). |

**Cuándo crear un servicio nuevo:** cuando una pieza de lógica se usa desde más de un lugar, o cuando el controlador empieza a tener más de unas pocas decenas de líneas con if/foreach.

---

## 8. Modelos y tablas MySQL

| Modelo | Tabla | Para qué |
|--------|-------|----------|
| `UserModel` | `users` | Cuentas (nombre, email, usuario, password_hash) |
| `SpaceModel` | `spaces` | Ambiente de cada usuario + rangos ideales (min/max temp, humedad, max CO₂) |
| `DeviceModel` | `devices` | ESP32 (real o simulado) + UID + token API |
| `MeasurementModel` | `measurements` | Historial de lecturas (temp, humedad, CO₂, AQI) |
| `DeviceStateModel` | `device_states` | Estado actual de cada dispositivo (modo + ON/OFF de cada actuador) |
| `DeviceCommandModel` | `device_commands` | Cola de comandos enviados al dispositivo |

Los modelos son **delgados**: sólo configuran tabla, primary key, campos permitidos y timestamps.
La consulta `find()`, `where()->first()`, `insert()` etc. la hace el modelo de CI4 por debajo.
El `UserModel` es el único con métodos extra (`buscarParaLogin`, `crearUsuario`, etc.) porque la autenticación se merecía nombres más claros.

Migración (crea las tablas): [`app/Database/Migrations/2026-05-06-000001_CreateTesinaSimulationSchema.php`](app/Database/Migrations/2026-05-06-000001_CreateTesinaSimulationSchema.php)
Seeder (carga el usuario `demo` / `123456`): [`app/Database/Seeds/DatabaseSeeder.php`](app/Database/Seeds/DatabaseSeeder.php)

---

## 9. Vistas — quién muestra qué

| Vista | Mostrada por | Qué contiene |
|-------|--------------|--------------|
| `inicio.php` | `AccesoController::inicio()` | Landing pública con hero, beneficios, carrusel, CTA |
| `login.php` | `AccesoController::login()` | Formulario de login |
| `registro.php` | `AccesoController::registro()` | Formulario de alta de usuario |
| `seleccion_ambiente.php` | `AccesoController::seleccionAmbiente()` | Cards con los 5 presets + form custom |
| `panel.php` | `PanelController::index()` | Dashboard completo (sidebar + sensores + actuadores + reglas + tabla de lecturas) |
| `partials/head.php` | Otras vistas | `<meta>`, `<title>`, CSS base, script anti-flash del tema |
| `partials/navbar.php` | Vistas públicas | Logo + links + acciones |
| `partials/footer.php` | Vistas públicas | Footer común |
| `partials/logo.php` | Reutilizable | SVG del logo EdenAir con variantes |
| `partials/theme_toggle.php` | Cualquier vista | Botón ☀️ / 🌙 |

---

## 10. MAPA DE FLUJO DE DATOS

### Ejemplo 1 — un usuario logueado abre el panel

```
1. Usuario abre  GET /panel
2. AuthFilter verifica sesión              [app/Filters/AuthFilter.php]
3. Router envía a  PanelController::index  [app/Config/Routes.php]
4. PanelController llama a PanelService::obtenerDatos(userId)
                                            [app/Services/PanelService.php]
5. PanelService consulta:
   - UserModel        → tabla users
   - SpaceModel       → tabla spaces
   - DeviceModel      → tabla devices
   - DeviceStateModel → tabla device_states
   - MeasurementModel → tabla measurements (últimas 6 + última suelta)
   - DeviceCommandModel (vía CommandService)
6. PanelService arma un array enorme con TODO procesado (métricas, gráficos, alertas...)
7. PanelController devuelve  view('panel', ['panel' => $datos])
8. panel.php pinta el HTML usando ese array
9. Navegador recibe el HTML + CSS (dashboard.css, eden-brand.css) + JS (dashboard.js, tema.js)
```

### Ejemplo 2 — el usuario registra una medición manual desde el panel

```
1. Usuario completa el form y envía POST /panel/medicion
2. AuthFilter pasa (hay sesión)
3. PanelController::guardarMedicion
   ├── valida los campos (temperature, humidity, co2_ppm, AQI)
   ├── obtiene device + space del usuario
   └── llama a SimulationService::createMeasurement
                                            [app/Services/SimulationService.php]
4. SimulationService
   ├── completa valores faltantes con un cálculo basado en la última lectura
   ├── inserta la fila en  measurements
   └── llama a AutomationService::processMeasurement
                                            [app/Services/AutomationService.php]
5. AutomationService
   ├── lee el DeviceState (modo actual)
   ├── si está en modo automatic, decide si encender fan / aromatizer / alert_led
   ├── inserta los comandos en  device_commands  (estado: pending)
   └── devuelve un resumen
6. PanelController redirige a /panel con flashdata 'success'
7. El usuario ve el panel actualizado con la nueva lectura
```

### Ejemplo 3 — el ESP32 real envía una medición

```
1. ESP32 hace POST  /api/devices/SIM-ABCD1234/measurements
   Header: X-Device-Token: <token>
   Body JSON: { temperature, humidity, co2_ppm, air_quality_index }
2. Router envía a  DeviceApiController::storeMeasurement
   [app/Controllers/Api/DeviceApiController.php]
3. Busca el device por device_uid, verifica el token
4. Valida el payload
5. Llama a SimulationService::createMeasurement (igual que el flujo manual)
6. Actualiza el campo last_seen_at del device
7. Devuelve JSON: { status, measurement, automation }
```

---

## 11. Datos reales vs datos simulados vs hardcodeados

| Dato | Origen real | ¿Hay fallback? |
|------|-------------|----------------|
| Usuario logueado | sesión PHP (`session()->get('user_id')`) | — |
| Nombre del ambiente | `spaces.environment_type` + `EnvironmentPresetService::getDisplayName` | — |
| Rangos ideales (min/max) | `spaces.min_temperature`, etc. (DB) | Default por preset en `EnvironmentPresetService` |
| Mediciones del historial | `measurements` (DB) | **Sí**, panel.php inventa 6 filas demo si no hay |
| Última medición | `measurements ORDER BY captured_at DESC LIMIT 1` | **Sí**, panel.php usa un objeto fijo si no hay |
| Tarjetas de sensores (Temp/Hum/AQI/CO₂) | calculadas desde la última medición | **Sí**, panel.php usa `defaultMetrics` |
| Reglas de automatización | calculadas en panel.php con la última medición | son fijas (umbrales del space) |
| QuickPulse (picos, hora 14:22, 06:10, 15:05) | **HARDCODEADO** en panel.php | siempre fijo, sólo se actualiza el número visible |
| Estado de actuadores | `device_states` (DB) | **Sí**, panel.php usa `defaultActuators` |
| URLs de la API | construidas en `PanelService::obtenerDatos` con `site_url()` | — |
| Sparkline (curva de 24h) | mini-serie de mediciones reales, si no hay usa array fijo | **Sí** |

> Si una vez Mostrás el panel sin haber generado mediciones, igual ves "ejemplo visual" porque la vista tiene defaults. Esos defaults están explícitamente marcados como `(EJEMPLO)` o `Datos de ejemplo` en la tabla y el footer del card de lecturas.

---

## 12. Preparado para ESP32 real

La sección **API** del panel (collapsible al final) muestra:
- `device_uid` único por usuario
- `api_token` único por usuario
- Las tres URLs que el ESP32 necesita

El firmware del ESP32 sólo tiene que:
1. Leer sensores DHT22/MQ-135/SGP30/etc.
2. POST a `/api/devices/{uid}/measurements` cada N segundos
3. GET a `/api/devices/{uid}/commands/pending` para ver si hay órdenes
4. Ejecutar (activar relé del aire, del aromatizador, etc.)
5. POST a `/api/devices/{uid}/commands/{id}/executed` para confirmar

**Lo que ya está listo en el back:** todo lo anterior.
**Lo que falta:** que exista el firmware. La web sigue funcionando con simulación mientras tanto.

---

## 13. Seguridad y validaciones presentes

| Mecanismo | Dónde |
|-----------|-------|
| Hash de contraseñas (`password_hash` / `password_verify`) | `UserModel`, `AccesoController::validarLogin` |
| Rehash automático si cambia el algoritmo | `AccesoController::actualizarHashLoginSiHaceFalta` |
| CSRF en formularios | `csrf_field()` en cada `<form>` del panel |
| `regenerate(true)` de la sesión al loguearse | `AccesoController::iniciarSesion` |
| Validación de inputs (`validateData`) | Todos los controladores |
| Filtro de rutas privadas | `AuthFilter` |
| Filtro de rutas públicas para usuarios ya logueados | `GuestFilter` |
| Autenticación API por token | `DeviceApiController::resolveAuthenticatedDevice` |
| `esc()` en TODAS las salidas a HTML | en las vistas |
| Mensajes amistosos en redirects con flashdata `success` / `error` | en todos los controladores |

---

## 14. Datos del usuario demo (seeder)

- Usuario: `demo`
- Email: `demo@edenair.com`
- Contraseña: `123456`
- Ambiente: oficina (creado automáticamente)
- Tiene 6 mediciones precargadas

Si lo borrás de la BD y volvés a correr `php spark db:seed DatabaseSeeder` se recrea.

---

## 15. Qué falta o convendría revisar después

### Pendiente real
- Conectar el firmware del ESP32 (depende de hardware).
- La regla **"Sugerir humidificador"** existe en el panel pero NO genera comando real (está marcada como `pending` visualmente). Es deliberado: aún no hay actuador `humidifier` en las tablas.
- Exportar lecturas (botón "Exportar" del card de lecturas) — está disabled, sólo visual.
- Crear nueva regla desde el panel — disabled, sólo visual.

### Mejoras sugeridas (no rompen nada)
- Borrar `public/JS/panel.js` si una auditoría confirma que ya no se incluye desde ninguna vista.
- Auditar `dashboard.css` (1700 líneas) para detectar selectores muertos.
- Sacar los `style="..."` inline que quedan en `inicio.php` y `panel.php` y moverlos al CSS.
- Mover el QuickPulse fijo del panel (picos hora 14:22 etc.) a datos reales calculados desde el historial.

### Cambios hechos en esta documentación
- Borrado `app/Views/hola.php` (archivo de prueba con "HOLA").
- Borrado `debug-5c2724.log` de la raíz.
- Limpieza de `writable/debugbar/` (20 archivos JSON viejos).
- Agregado `.gitignore` para que no se vuelvan a versionar logs/cache/sesión/debugbar.
- Refactor de `panel.php`: la lógica de armado del dashboard se movió a `PanelService` (ver `PanelService::obtenerVistaPanel`). La vista ahora sólo recorre arrays y dibuja.

---

## 16. Glosario express

| Término | Qué significa acá |
|---------|-------------------|
| MVC | Modelo-Vista-Controlador. División de responsabilidades. |
| Service | Clase que agrupa lógica de negocio. No es de CI4, lo agregamos nosotros. |
| Migration | Script PHP que crea o modifica tablas. Reemplaza al SQL a mano. |
| Seeder | Script PHP que carga datos iniciales (usuario demo). |
| Filter | Middleware. Se ejecuta antes/después del controller. |
| Flashdata | Datos que sobreviven 1 sólo request (mensajes de éxito/error). |
| CSRF | Token contra falsificación de peticiones. CI4 lo agrega solo. |
| Preset | En este proyecto, perfil de ambiente con rangos por defecto. |
| Actuador | Salida controlable: fan, aromatizer, alert_led. |
| Device UID | Identificador público del ESP32 (`SIM-XXXXXX...`). |
| API token | Clave secreta que el ESP32 envía en `X-Device-Token`. |

---

## 17. Atajos para abrir el código

| Quiero entender... | Abrir |
|--------------------|-------|
| La landing | [`app/Views/inicio.php`](app/Views/inicio.php) |
| El panel | [`app/Views/panel.php`](app/Views/panel.php) y [`app/Services/PanelService.php`](app/Services/PanelService.php) |
| Cómo se decide encender un actuador | [`app/Services/AutomationService.php`](app/Services/AutomationService.php) |
| Cómo se simula una medición | [`app/Services/SimulationService.php`](app/Services/SimulationService.php) |
| Cómo se autentica el ESP32 | [`app/Controllers/Api/DeviceApiController.php`](app/Controllers/Api/DeviceApiController.php) |
| Qué tablas hay | [`app/Database/Migrations/2026-05-06-000001_CreateTesinaSimulationSchema.php`](app/Database/Migrations/2026-05-06-000001_CreateTesinaSimulationSchema.php) |
| Qué rutas hay | [`app/Config/Routes.php`](app/Config/Routes.php) |
