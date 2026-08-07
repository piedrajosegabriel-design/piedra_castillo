# HITO 2 — La página, la experiencia y las funcionalidades

Documento centrado en **lo que el usuario ve y hace** y en cómo está armado por
dentro. Cubre identidad visual, landing, dashboard, alta de dispositivo,
ambientes, perfil y compra. Cada sección lista los **archivos** involucrados,
las **variables/funciones** clave y las **conexiones** entre ellos. Al final
hay un **glosario** con términos y líneas de código importantes.

> Para entender el backend (rutas, controladores, modelos, base de datos) ver
> `HITO_1_BACKEND_Y_BASE_DE_DATOS.md`. Para la lógica de negocio (cálculos,
> reglas, vinculación) ver `services.md`.

> **Cómo estudiar el frontend en el código.** Las vistas principales
> (`inicio.php`, `portfolio.php`, `panel.php`) están marcadas con dos tipos
> de comentario: `<!-- ===== ESTRUCTURA: ... ===== -->` señala qué es cada
> bloque de HTML, y `<!-- ===== ANIMACIÓN: ... ===== -->` señala qué se anima
> y desde qué archivo JS. Además, cada archivo CSS empieza con un **ÍNDICE**
> de sus secciones. El detalle de esa organización está en la sección
> [1.bis Organización del CSS y el JS](#1bis-organización-del-css-y-el-js).

---

## 0. La nueva lógica de usuario (lo más importante)

**Antes** el sistema obligaba a elegir un ambiente al loguearse: si no tenías un
`space` configurado, te redirigía a `/panel/ambiente` y no te dejaba ver el
panel. Confundía: el ambiente solo tiene sentido cuando ya hay un dispositivo
físico. **Ahora** el flujo es así:

| Estado de la cuenta | Pantalla que ve el usuario |
|---|---|
| Recién registrada, **0 dispositivos** | Pantalla de **Bienvenida**: explica cómo funciona la cuenta y ofrece dos caminos — Agregar mi primer dispositivo · Comprar Eden Air. |
| **≥ 1 dispositivo** | **Panel monitor** del dispositivo activo, con switcher entre dispositivos en el header. |

### Modelo de dominio actual

```
Usuario (1) ──┬─ (N) Dispositivos  ──── (1) Ambiente
              │
              └─ (N) Ambientes     ──── (N) Dispositivos
```

- **Usuario** → cuenta única (nombre, apellido, email, usuario, contraseña).
- **Dispositivo** → tiene un `device_uid` público, un `api_token` secreto, su
  MAC, nombre, tipo y estado; pertenece a un usuario y a un ambiente. Se da de
  alta solo, al conectarse durante una ventana de vinculación.
- **Ambiente (`space`)** → lugar físico donde está el dispositivo. Tiene su
  configuración de confort (rangos de temperatura, humedad y CO₂). Puede tener
  varios dispositivos.

### Por qué se eliminó el paso intermedio

- Confundía a quien no tenía dispositivo (no sabe para qué elegir un ambiente
  sin algo que ponerle).
- Rompía el orden lógico: primero entendés Eden Air, después decidís si tenés
  un dispositivo, después lo configurás.
- El ambiente es parte del **alta del dispositivo**, no del alta del usuario.

### Qué archivos se tocaron para sostener este cambio

| Archivo | Cambio |
|---|---|
| `app/Filters/GuestFilter.php` | Si hay sesión, redirige siempre a `/panel` (antes consultaba `SpaceModel` y mandaba a `/panel/ambiente`). |
| `app/Controllers/AccesoController.php` | `redirigirDespuesDelLogin()` siempre devuelve `/panel`. Se eliminaron `seleccionAmbiente()`, `guardarAmbiente()` y todos sus helpers (`reglasAmbiente`, `validarFormularioAmbiente`, `validarAmbientePersonalizado`, `validarRangoOpcional`, `leerDatosAmbiente`, `AMBIENTE_PERSONALIZADO`, `redirigirConDato`). |
| `app/Controllers/PanelController.php` | `index()` ramifica bienvenida vs panel monitor según `count(devices)`. El guard `redireccionarSiFaltaDispositivo()` (renombrado desde `redireccionarSiFaltaAmbiente`) protege acciones que necesitan un dispositivo. |
| `app/Config/Routes.php` | Eliminadas las rutas `GET/POST panel/ambiente`. |
| `app/Views/seleccion_ambiente.php` | **Borrado.** |
| `app/Views/login.php` | Hint actualizado: *"Al entrar vas directo a tu panel; si todavía no tenés un dispositivo, lo conectás escaneando un QR, sin códigos ni configuración manual."* |

---

## 1. Identidad visual aplicada

| Elemento | Definición |
|---|---|
| **Marca** | "Corriente" — squircle con degradé verde y glifo blanco de corriente de aire + punto cítrico. Mismo símbolo en claro/oscuro (trae su propio fondo). |
| **Paleta** | Fondos cálidos (`#F6F4EC`, `#EEF7F4`); verde marca (`#2F6B4F`) y verde profundo (`#143326`) para contraste; **aqua** (`#8FD6C8`), menta y **cítrico** (`#C9D870`) para datos vivos. |
| **Tipografías** | DM Serif Display (titulares), DM Sans (textos), DM Mono (datos y etiquetas). |
| **Modo claro** | Fondo claro y aireado; verde como acento. |
| **Modo oscuro** | Verde profundo `#0E1F17` con tarjetas `#1A2C23`, bordes con matiz **aqua** (no gris). |
| **Loader** | Logo Corriente + anillos suaves + barra de progreso. |

> **Regla de marca:** el verde es la identidad, pero el **aqua y el cítrico son
> "el aire"** — se reservan para datos vivos.

**Archivos:** `public/CSS/eden-brand.css` (tokens, logo, botones premium,
modo oscuro), `public/CSS/inicio.css` (landing), `public/CSS/dashboard.css`
(dashboard, welcome, wizard, switcher).

---

## 1.bis Organización del CSS y el JS

### CSS: un archivo global + un archivo por página (decisión de arquitectura)

El proyecto usa el patrón **"global + por página"**, que es la mejor práctica
para un sitio multipágina sin bundler como este:

| Archivo | Alcance | Qué tiene |
|---|---|---|
| `eden-brand.css` | **Global** — lo carga `partials/head.php` en todas las páginas | Tokens (variables de color/easing), modo oscuro, navbar, botones, forms, footer: todo lo compartido. |
| `inicio.css` | Solo la **landing** | Hero, núcleo 3D, video por scroll, secciones de la página pública. |
| `portfolio.css` | Solo el **portfolio** | Las secciones numeradas 00–06 del recorrido. |
| `dashboard.css` | Toda el **área privada** | Panel, sidebar, sensores, wizard de dispositivos, ambientes, perfil, compra. |

**Por qué así y no "un CSS por vista":** las vistas privadas (panel, perfil,
dispositivos, ambientes…) comparten el mismo shell (sidebar + header + cards),
así que partir `dashboard.css` en 7 archivos duplicaría estilos o exigiría más
requests sin beneficio. Y lo común de verdad (botones, navbar) ya está aislado
en `eden-brand.css`. Cada página termina cargando **exactamente 2 CSS**:
el global + el suyo.

**Para encontrar el CSS de cualquier cosa:** abrí el archivo de la página y
mirá el **ÍNDICE comentado del inicio** — lista las secciones en orden con su
línea aproximada. Los separadores internos (`/* ===== NOMBRE ===== */`) se
buscan con Ctrl+F.

**Excepciones documentadas (estilos embebidos en vistas):**
- `panel.php` tiene un `<style>` dentro de `<noscript>`: es el fallback sin
  JavaScript (oculta el loader). No puede moverse a un archivo porque perdería
  la condición *noscript*.
- `partials/theme_toggle.php` lleva sus estilos adentro a propósito: el toggle
  es autocontenido y funciona en cualquier página sin depender de otro CSS.

### JS: misma lógica, por página

| Archivo | Página | Rol |
|---|---|---|
| `tema.js` | todas | Modo claro/oscuro (lee/escribe `localStorage`). |
| `ea-scrollbar.js` | landing, portfolio, panel | Barra de scroll flotante custom. |
| `inicio.js` / `inicio-gsap.js` | landing | Menú + datos del hero / animaciones GSAP-ScrollTrigger. |
| `eden-core-3d.js` | landing | Núcleo 3D del hero (Three.js, módulo ES). |
| `portfolio.js` / `portfolio-gsap.js` | portfolio | Scrollspy + gráficos Chart.js / animaciones. |
| `dashboard.js` / `dashboard-gsap.js` | área privada | Loader, sidebar, "ver más" / scroll suave + reveals. |
| `acceso.js` | login, registro, recuperar, restablecer | Mostrar/ocultar contraseña y bloqueo del botón al enviar. |
| `registro.js` | registro | Medidor de seguridad y coincidencia de contraseñas. |
| `conectar.js` / `vinculacion.js` | conexión por QR | Sondeo del estado de la vinculación. |
| `compra.js` | compra | Aviso de compra simulada. |
| `navbar.js` | páginas públicas | Mega menú de Portfolio. |

**Convención `*-gsap.js`:** la interacción "funcional" (menús, formularios)
vive en el JS base de la página; **todo lo que es animación** vive en el
archivo `-gsap` correspondiente. Si una animación falla o se quiere tocar,
siempre se busca en el `-gsap` de esa página.

---

## 2. La landing (`app/Views/inicio.php`)

Recorrido pensado como narrativa: primero impacta (3D + video), después explica,
al final invita a comprar.

### 2.1 Secciones, en orden

1. **Navbar.** Logo + accesos (Qué es, Beneficios, Tecnología, Funcionamiento, Comprar), menú Portfolio, toggle de tema, **Iniciar sesión** + **Comprar** (CTA destacado).
2. **Hero con OBJETO 3D** *(protegido)*. Mensaje + botones a la izquierda; objeto 3D interactivo (núcleo Eden Air) con paneles HUD a la derecha. Lo arma `eden-core-3d.js` (Three.js cargado vía importmap/unpkg). Datos en vivo desde `GET api/sensores`.
3. **Secuencia de VIDEO por scroll** *(protegida)*. Al hacer scroll, el video avanza cuadro por cuadro. Sección `.ea-experience`.
4. **Núcleo y módulos** (Beneficios). Sección `.ea-core-section` con animación del núcleo y los 7 módulos.
5. **Tecnología interna con VIDEO inferior** *(protegida)*. Vista explosionada del dispositivo (`#tecnologia-interna`) + 4 tarjetas flotantes.
6. **Funcionamiento.** "Sensa. Decide. Actúa." — el ciclo del sistema.
7. **Compra del producto** (`#comprar`). Tarjeta "Eden Air Core", precio de referencia, beneficios, CTA destacado.
8. **Cierre y footer.**

### 2.2 Cómo se invoca

- Ruta `GET /` → `AccesoController::inicio()` → `view('inicio')`.
- La navbar usa `$eaNavActions`: invitados ven *Iniciar sesión* + *Comprar*; logueados ven *Entrar al dashboard* + *Comprar*.
- Animaciones por `data-reveal` / `data-reveal-child`; respetan `prefers-reduced-motion`.

### 2.3 SEO y accesibilidad

- Meta `description`, `keywords`, **Open Graph** y **Twitter Card** completos.
- `h1` en el hero, `h2` por sección con eyebrows numerados, `h3` en tarjetas; secciones con `aria-labelledby`.

---

## 3. El dashboard

### 3.0 Layout único — `app/Views/layouts/panel.php`

Las 8 pantallas internas comparten un solo esqueleto: `<head>`, menú lateral,
barra superior, mensajes flash y scripts viven **una sola vez**, en el layout.
Cada vista escribe únicamente su contenido:

```php
<?php $this->setData([
    'tituloPagina'  => 'EdenAir · Mis dispositivos',
    'sidebarActivo' => 'dispositivos',
    'cabecera'      => ['titulo' => 'Mis dispositivos', 'bajada' => '2 vinculados'],
]) ?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>
    ... solo el HTML de esta pantalla ...
<?= $this->endSection() ?>
```

Opciones del layout: `tituloPagina`, `descripcion`, `sidebarActivo`,
`cantidadEquipos`, `cabecera`, `claseContenido`, `attrsApp`, `conLoader`,
`conScrollSuave`, `scripts`. Están documentadas en el encabezado del archivo.

**Agregar un botón a la barra superior** es una línea en la config de la vista,
sin copiar clases de otro botón:

```php
'cabecera' => ['titulo' => '...', 'botones' => [
    ['texto' => 'Conectar', 'href' => site_url('panel/dispositivos/conectar'), 'icono' => 'mas'],
]],
```

Las piezas del layout: `partials/panel_header.php` (barra superior),
`partials/panel_sidebar.php` (menú), `partials/flashes.php` (avisos) y
`partials/panel_loader.php` (pantalla de carga, solo en `/panel`).

Las cuatro pantallas de acceso usan el mismo mecanismo con
`layouts/acceso.php`.

### 3.1 Sidebar único — `app/Views/partials/panel_sidebar.php`

Componente reutilizable presente en **todas** las vistas internas.

**Variables que recibe:**

| Variable | Tipo | Para qué |
|---|---|---|
| `$active` | string | Clave del ítem activo: `inicio`, `dispositivos`, `ambientes`, `perfil`, `compra`. Se compara para agregar `is-active` y `aria-current="page"`. |
| `$devicesCount` | int (opcional) | Pinta un badge con la cantidad de dispositivos al lado de "Mis dispositivos". |

**Helpers locales:**

```php
$cls  = fn($key) => $active === $key ? 'ea-sidebar-item is-active' : 'ea-sidebar-item';
$aria = fn($key) => $active === $key ? ' aria-current="page"' : '';
```

**Estructura:** Inicio · Mis dispositivos · Ambientes · Automatizaciones ·
(sección "Cuenta") · Perfil · **Comprar EdenAir** (clase `.ea-sidebar-item--cta`,
no es un ítem plano sino un CTA destacado).

**Conecta con:** todas las vistas del dashboard
(`panel.php`, `panel/bienvenida.php`, `dispositivos/index.php`,
`dispositivos/conectar.php`, `ambientes/index.php`, `ambientes/editar.php`,
`perfil_usuario.php`, `compra_mercadopago.php`).

### 3.2 Pantalla de Bienvenida — `app/Views/panel/bienvenida.php`

Se muestra cuando el usuario tiene **0 dispositivos**.

**Renderizada por:** `PanelController::index()` cuando `count(devices) === 0`.

**Variables que recibe:**

| Variable | Para qué |
|---|---|
| `$usuario` | Array con `nombre` y `apellido` del usuario, traído por `UserModel::obtenerPorId()`. |

**Variables locales:**

```php
$nombre  = trim((string) ($usuario['nombre'] ?? '')) ?: 'usuario';
$initial = strtoupper(mb_substr($nombre, 0, 1) ?: 'U');  // letra del avatar
```

**Estructura:**
- Hero con saludo personalizado (eyebrow + título con el nombre).
- 3 bullets explicando *Dispositivos*, *Ambientes*, *Monitoreo*: qué significa
  cada concepto dentro de la cuenta, para que el usuario entienda el modelo
  antes de configurar nada.
- Grid con 2 tarjetas CTA:
  - **Conectar mi primer dispositivo** (marcada como *Recomendado*) →
    `panel/dispositivos/conectar`, la pantalla del QR.
  - **Comprar Eden Air** (*Plan único*) → `panel/compra`.
- Texto de ayuda aclarando que no hace falta ningún código ni buscar nada en la
  caja: el QR se genera en el momento y el equipo se da de alta solo.

**Por qué solo estos dos caminos:** la pantalla responde a la única pregunta
real del usuario sin equipo en el panel — *"¿ya tengo un Eden Air o todavía no?"*.
El panel monitor aparece recién cuando hay un dispositivo vinculado, así lo que
se ve en el dashboard siempre corresponde a un equipo de esa cuenta.

**Conecta con:**
- `DispositivosController::conectar` (pantalla del QR).
- `PanelController::compra` (vista compra).
- `partials/panel_sidebar` con `$active='inicio'` y `$devicesCount=0`.

### 3.3 Panel monitor — `app/Views/panel.php`

Se muestra cuando el usuario tiene **≥ 1 dispositivo**.

**Renderizado por:** `PanelController::index()` con `view('panel', ['panel' => $panelService->obtenerVistaPanel($userId, $activeDeviceId)])`.

**Cómo decide qué dispositivo mostrar:**

```php
// PanelController::dispositivoActivo()
$candidato = (int) session()->get('active_device_id');   // dispositivo elegido
// Valida que exista y pertenezca al usuario, si no devuelve null.
```

```php
// PanelService::obtenerDatos()
$dispositivo = $dispositivos[0];                          // por defecto el primero
if ($activeDeviceId !== null) {
    foreach ($dispositivos as $d) {
        if ((int) $d['id'] === $activeDeviceId) { $dispositivo = $d; break; }
    }
}
```

**Variables clave que recibe la vista** (todas dentro de `$panel`):

| Bloque | Contenido |
|---|---|
| `$panel['user']` | `id`, `nombre`. |
| `$panel['space']` | `tipo`, `tipo_label`, `nombre`, `resumen` (rangos), `perfil` (min/max). |
| `$panel['device']` | `nombre`, `uid`, `token`, `ultimo_envio`, `ultima_consulta`. |
| `$panel['state']` | `modo` (`automatic`/`manual`), `modo_label`, `detalle`. |
| `$panel['metrics']` | 4 tarjetas (temperatura, humedad, CO₂, calidad). |
| `$panel['actuators']` | Lista de actuadores con estado y tono. |
| `$panel['history']` | Últimas 6 mediciones formateadas. |
| `$panel['devices_list']` | Lista corta para el **switcher** del header. |
| `$panel['view']` | Bloque con todo precalculado (sparkline, sensorCards, automationRules, generalTone, etc.) — la vista solo dibuja. |

**Switcher de dispositivo:**

- Si `count(devices_list) > 1` → `<select>` en el header que postea a `panel/dispositivo-activo`.
- Si solo hay uno → chip discreto con su nombre.
- El dispositivo elegido queda guardado en `active_device_id` (sesión) y se
  revalida en cada request: si no pertenece al usuario, se descarta.

**Conecta con:**
- `PanelService::obtenerVistaPanel`, `PanelService::obtenerDatos`.
- `PanelController::seleccionarDispositivo` (POST del switcher).
- `partials/panel_sidebar` con `$active='inicio'`.

### 3.4 Mis dispositivos — `app/Views/dispositivos/index.php`

**Ruta:** `GET panel/dispositivos` → `DispositivosController::index()`.

**Variables que recibe:**

| Variable | Origen |
|---|---|
| `$dispositivos` | `DevicePairingService::listarDeUsuario($userId)` — array con `id`, `nombre`, `tipo`, `espacio`, `uid`, `estado`, `estado_label`, `estado_tono`, `mac`, `notas`, `visto`. |

**Estructura:** grid de tarjetas (`auto-fill minmax(280px,1fr)`) + tarjeta extra "Conectar otro dispositivo".

**Estados visuales** (devueltos por `DevicePairingService::estadoLegible()`):
- `active` → "Conectado" / tono `success`.
- `offline` → "Sin conexión" / tono `danger`.
- default → "Esperando primera lectura" / tono `info`.

### 3.5 "Conectá tu Eden Air" — `app/Views/dispositivos/conectar.php`

> **Reemplazó al wizard de 4 pasos.** El flujo por **código de activación**
> (`EDEN-XXXX-XXXX`) se eliminó por completo: ya no hay que buscar nada en la
> caja ni tipear ningún código. Ver §3.5.1 para el detalle de qué se borró.

**Ruta:** `GET panel/dispositivos/conectar` → `DispositivosController::conectar()`.

**Variables que recibe:**

| Variable | Origen |
|---|---|
| `$ssid` | `DevicePairingService::ssid()` — nombre del WiFi de configuración del equipo. |
| `$minutos` | `DevicePairingService::MINUTOS_VENTANA` — cuánto dura la ventana (10). |

**Una sola pantalla, cuatro paneles que se alternan** (los maneja
`public/JS/conectar.js`):

| Panel | Cuándo se ve | Qué muestra |
|---|---|---|
| `reposo` | Al entrar | Los 3 pasos y el botón **Conectar**. |
| `vivo` | Tras apretar Conectar | El **QR generado en el momento**, el nombre y la clave de la red, la cuenta regresiva y el estado en vivo. |
| `ok` | Cuando el equipo aparece | "«Eden Air» quedó conectado" + accesos al panel. |
| `fin` | Si se vence o se cancela | Motivo + botón **Intentar de nuevo**. |

**El ciclo completo:**

1. **Clic en Conectar** → `POST panel/dispositivos/conectar` →
   `DispositivosController::iniciar()` → `DevicePairingService::abrirVentana()`.
   Devuelve JSON con el **SVG del QR ya dibujado**, `ssid`, `password`, `token`,
   `expira_en` y el hash CSRF nuevo.
2. **El usuario escanea** el QR con la cámara del celular. El QR está en el
   formato estándar `WIFI:T:WPA;S:EdenAir-Setup;P:...;;`, así que el celular
   ofrece conectarse a la red del equipo sin escribir nada.
3. **En el portal del equipo** elige el WiFi de su casa y pone la clave.
4. **El equipo llama a la API**: `POST api/devices/pair` con su MAC →
   `DevicePairingService::registrarDispositivo()`. Queda dado de alta en la
   cuenta que tenga la ventana abierta, con ambiente y estado inicial, y recibe
   `device_uid` + `api_token`.
5. **La página lo detecta**: sondea `GET panel/dispositivos/conectar/estado?token=…`
   cada 2,5 s (GET → exento de CSRF) y salta al panel `ok`.

**Cancelar:** `POST panel/dispositivos/conectar/cancelar`. También se dispara con
`navigator.sendBeacon()` al cerrar la pestaña, para no dejar la ventana colgada.

**El QR se genera en el servidor** con `app/Libraries/QrCode.php` (ISO/IEC 18004
escrito a mano en PHP, modo byte, nivel M, versiones 1–10, salida SVG). No hay
CDN ni Composer: la pantalla funciona sin internet.

#### 3.5.1 Qué se eliminó del flujo anterior

| Se borró | Reemplazo |
|---|---|
| `app/Views/dispositivos/agregar.php` (wizard de 4 pasos) | `conectar.php` (una pantalla) |
| `app/Services/DeviceClaimService.php` | `DevicePairingService.php` |
| `app/Models/DeviceActivationCodeModel.php` | `DevicePairingModel.php` |
| Tabla `device_activation_codes` y columna `devices.activation_code` | Tabla `device_pairings` |
| `GET panel/dispositivos/validar` (validación en vivo del código) | — (no hay código que validar) |
| `POST panel/dispositivos` (alta por formulario) | El equipo se da de alta solo contra la API |
| `POST api/devices/provision` (credenciales a cambio del código) | `POST api/devices/pair` (credenciales a cambio de la MAC, durante la ventana) |
| Seeder del lote de códigos | El seeder solo cierra ventanas vencidas |

La migración que hace el cambio de esquema es
`2026-08-02-000001_ReplaceClaimCodesWithPairing.php`.

### 3.6 Ambientes — `app/Views/ambientes/index.php` + `editar.php`

**Listado** (`GET panel/ambientes` → `AmbientesController::index()`):

| Variable | Origen |
|---|---|
| `$ambientes` | Mapeo de `SpaceModel` + dispositivos asociados: `id`, `nombre`, `tipo`, `rango_temp`, `rango_hum`, `max_co2`, `devices` (lista de `id`+`name`+`tipo`). |

**Edición** (`GET panel/ambientes/{id}/editar` → `editar()`):
- Valida pertenencia: `(int) $ambiente['user_id'] === $userId`.
- Recibe `$ambiente` (fila completa de `spaces`).
- `POST panel/ambientes/{id}` → `actualizar()`:
  - Reglas: `min_temperature < max_temperature`, `min_humidity < max_humidity`, `max_co2 > 0`.
  - Actualiza solo los campos del rango (no toca `environment_type` ni el usuario dueño).

### 3.7 Perfil — `app/Views/perfil_usuario.php`

**Ruta:** `GET panel/perfil` → `PanelController::perfil()`.

**Dos formularios separados:**

| Form | Ruta | Campos | Reglas |
|---|---|---|---|
| Datos | `POST panel/perfil` → `actualizarPerfil` | `nombre`, `apellido`, `usuario`, `email`, `current_password` | nombre/apellido requeridos (2–120), email válido, usuario `[A-Za-z0-9._-]`, contraseña actual obligatoria para confirmar. |
| Contraseña | `POST panel/password` → `actualizarPassword` | `current_password`, `password`, `password_confirm` | min 8 con mayús + minús + número; confirmación coincidente. |

**Sin roles** (el proyecto no los usa). **Email se conserva** porque es la vía
de recuperación; quitarlo rompería el flujo `recuperar`/`restablecer`.

### 3.8 Compra — `app/Views/compra_mercadopago.php`

**Ruta:** `GET panel/compra` → `PanelController::compra()`.

- Marco visual del dashboard (sidebar + header).
- Producto: "Eden Air Core", **pago único**.
- El checkout todavía **no procesa el cobro**: la pasarela de MercadoPago no
  está integrada y la vista lo aclara (*"Compra simulada · sin integración de
  pago todavía"*). El precio que se muestra es de referencia.

---

## 4. Modo claro / oscuro (sin saltos de scroll)

**Archivos:** `public/JS/tema.js`, `app/Views/partials/theme_toggle.php`.

**Problema original.** Al cambiar de tema el scroll se movía. La causa raíz era
la **View Transitions API** (`document.startViewTransition`) que tomaba una
instantánea de cada estado y, si las alturas diferían en sub-píxeles, corría el
scroll.

**Solución (rebuild del módulo):**

1. **Se eliminó por completo la View Transitions API** del cambio de tema
   (`tema.js` + reglas `::view-transition-*` en `eden-brand.css`).
2. Se introdujo `aplicarTemaPreservandoScroll()` con **3 checkpoints**:
   - **Síncrono** inmediatamente después de cambiar `data-theme`.
   - **Microtask** (`Promise.resolve().then`), antes del primer paint.
   - **rAF** (`requestAnimationFrame`), después del primer paint.
3. Antes del cambio se fuerza `scroll-behavior: auto` en `html` y `body` para
   que las restauraciones sean instantáneas.

**Toggle accesible:** `<label>` que envuelve un `<input type="checkbox">`. **No**
es un `<a href="#">` (no navega, no recarga, no muta la URL). Focus visible,
animación suave del thumb, OK en claro y oscuro.

---

## 5. Video de la "vista explosionada"

Origen: `Smart_device_exploded_view_anima…_202605310022.mp4` (8 s, 1280×720,
con audio). Procesado con FFmpeg.

| Archivo | Detalle |
|---|---|
| `public/videos/eden-air-exploded.mp4` | H.264, **sin audio**, `-crf 24`, `+faststart` · ~934 KB |
| `public/videos/eden-air-exploded.webm` | VP9, **sin audio**, `-crf 34` · ~682 KB |
| `public/videos/eden-air-exploded-poster.jpg` | Póster (frame a 3 s) · ~52 KB |

**Atributos del `<video>`:** `autoplay`, `muted`, `loop`, `playsinline` (+
`webkit-playsinline`), `preload="metadata"`, fuente WebM + fallback MP4,
`poster`.

**Performance:** `IntersectionObserver` reproduce solo cuando está en pantalla
y pausa al salir. Con `prefers-reduced-motion`, no autoreproduce y muestra el
póster.

**Comandos FFmpeg de referencia:**

```bash
# MP4 sin audio
ffmpeg -i origen.mp4 -an -c:v libx264 -profile:v high -pix_fmt yuv420p \
       -crf 24 -preset slow -movflags +faststart eden-air-exploded.mp4
# WebM VP9
ffmpeg -i origen.mp4 -an -c:v libvpx-vp9 -crf 34 -b:v 0 -row-mt 1 eden-air-exploded.webm
# Póster
ffmpeg -ss 00:00:03 -i origen.mp4 -frames:v 1 -q:v 3 eden-air-exploded-poster.jpg
```

---

## 6. Responsive y accesibilidad

- **Landing:** menú hamburguesa móvil con los nuevos accesos + CTA de compra; sección de video pasa de tarjetas flotantes a grilla 2×/1×; compra pasa de 2 columnas a 1.
- **Dashboard:** grid `auto-fill minmax(280px,1fr)`; toolbar apilada en mobile; wizard a ancho completo.
- **Competencia (portfolio):** tabla con scroll horizontal en desktop, acordeón por competidor en mobile (≤720px).
- **Cuidado:** sin overflow horizontal, respeto a `prefers-reduced-motion` en todas las animaciones.
- **Accesibilidad:** `aria-label`/`aria-live` en el feedback del código, `radiogroup` para ambientes, foco visible, navegación por teclado, labels en todos los campos.

---

## 7. Archivos del Hito 2

> **Es un registro histórico.** Algunos de los archivos que se listan acá ya no
> existen: el flujo de conexión del dispositivo se rehízo después (ver §3.5.1) y
> también se eliminaron `SimulationService` y `DeviceProvisioningService`. Para
> el estado actual de la capa de servicios, mirar `services.md`.

**Nuevos:**

- `app/Controllers/DispositivosController.php`
- `app/Controllers/AmbientesController.php`
- `app/Services/DeviceClaimService.php`
- `app/Models/DeviceActivationCodeModel.php`
- `app/Database/Migrations/2026-05-31-000002_CreateDeviceClaimSchema.php`
- `app/Database/Migrations/2026-05-31-000003_AllowMultipleSpacesPerUser.php`
- `app/Views/partials/panel_sidebar.php`
- `app/Views/panel/bienvenida.php`
- `app/Views/dispositivos/index.php`
- `app/Views/dispositivos/agregar.php`
- `app/Views/ambientes/index.php`
- `app/Views/ambientes/editar.php`
- `public/videos/eden-air-exploded.{mp4,webm,jpg}`

**Modificados (esta auditoría incluida):**

- `app/Controllers/AccesoController.php` — eliminado todo el flujo de selección de ambiente al login.
- `app/Controllers/PanelController.php` — `index()` ramifica bienvenida/panel; `seleccionarDispositivo()` para el switcher; guard renombrado a `redireccionarSiFaltaDispositivo`.
- `app/Services/PanelService.php` — `obtenerVistaPanel($userId, $activeDeviceId)` multi-dispositivo; `devices_list` para el switcher.
- `app/Services/DeviceClaimService.php` — `vincular()` admite `space_id` existente o `space` nuevo.
- `app/Services/DeviceProvisioningService.php` — eliminado `hasConfiguredSpace()` (huérfano).
- `app/Models/DeviceModel.php` — `allowedFields` ampliados + `obtenerDeUsuario()`.
- `app/Filters/GuestFilter.php` — siempre redirige a `/panel`.
- `app/Config/Routes.php` — añadidas rutas del Hito 2; eliminadas `panel/ambiente`.
- `app/Views/inicio.php` — navbar/CTA, hero, menú mobile, sección video, sección compra, SEO.
- `app/Views/panel.php` — sidebar única, switcher de dispositivos.
- `app/Views/perfil_usuario.php`, `compra_mercadopago.php` — sidebar única.
- `app/Views/portfolio.php` — análisis de competencia.
- `app/Views/login.php` — hint actualizado a la nueva lógica.
- `public/JS/tema.js` — sin View Transitions; triple checkpoint de scroll.
- `public/CSS/inicio.css`, `eden-brand.css`, `dashboard.css`, `portfolio.css` — estilos nuevos.
- `mysql_setup.sql` — índice multi-ambiente, columnas nuevas en `devices`, tabla `device_activation_codes` + seed.

**Eliminados:**

- `app/Views/seleccion_ambiente.php` (era la vista del flujo viejo).

---

## 8. Pendiente para integración con ESP32

Con el flujo nuevo **no hay nada que imprimir ni que precargar en la base**: no
existen códigos de activación. Lo que tiene que hacer el firmware es:

1. **Al arrancar sin WiFi configurado**, levantar un punto de acceso con
   **exactamente** estas credenciales (son las que la web mete adentro del QR,
   ver `DevicePairingService::AP_SSID_DEFECTO`):

   | | |
   |---|---|
   | SSID | `EdenAir-Setup` |
   | Clave | `edenair.setup` |

   Si en el firmware se eligen otras, hay que cambiarlas también en el `.env`
   del servidor (`edenair.apSsid` / `edenair.apPassword`).

2. **Servir un portal cautivo** en `192.168.4.1` que liste las redes WiFi
   cercanas y acepte SSID + contraseña de la red de la casa.

3. **Al conectarse a esa red**, llamar a:

   ```
   POST /api/devices/pair
   { "mac": "<su MAC>", "firmware": "1.0.0" }
   ```

   - **200** → guardar `device_uid` y `api_token` en memoria no volátil y
     empezar a medir.
   - **202** → el dueño todavía no apretó "Conectar" en la web: reintentar a los
     `reintentar_en` segundos (15).

4. **Después de eso**, usar los endpoints que ya existen (mediciones y comandos)
   con el header `X-Device-Token`.

Otros pendientes de hardware:

- Confirmar voltajes (3.3 V/5 V) y consumo.
- Probar lectura individual de cada componente (DHT, CO₂, calidad, ventilador, humidificador, aromatizador, LED).
- Ya existe `POST api/devices/{uid}/measurements` con autenticación por `X-Device-Token`; falta el envío real del firmware.
- Ya existen `GET api/devices/{uid}/commands/pending` y `POST .../commands/{id}/executed`; falta conectarlos.
- Alternar `status` `active`/`offline` según `last_seen_at` (el alta del
  `device_uid` ya la resuelve `POST api/devices/pair`).
- Pago real (MercadoPago / Stripe): falta integrar la pasarela; hoy el checkout
  no cobra.

---

## 9. Cómo probar cada vista

> Base local: `http://localhost/piedra_castillo/public/`
> Migrar antes: `php spark migrate`.

**Landing** (`/`)
- Slogan con impacto; el "7" de **24/7** se ve completo.
- Sección **Tecnología interna** reproduce el video (sin audio) y pausa al salir de pantalla.
- Sección **Comprar** muestra Eden Air Core con su precio de referencia.
- Cambio de **tema** estando scrolleado: la página **no salta**.

**Login y entrada** (`/login`)
- Logueate. Vas **directo a `/panel`** (sin paso intermedio).
- Si la cuenta no tiene dispositivos → ves **Bienvenida** con los dos CTAs.
- Si tiene 1+ → ves el **panel monitor**.

**Mis dispositivos** (`/panel/dispositivos`)
- Lista los dispositivos con estado y ambiente; botón **Conectar dispositivo**.

**Conectar dispositivo** (`/panel/dispositivos/conectar`)

Sin hardware a mano se puede probar el ciclo entero simulando la ESP32 con una
sola llamada HTTP:

1. Apretá **Conectar** → aparece el QR y arranca la cuenta regresiva.
2. Escaneá el QR con el celular → te tiene que ofrecer conectarte a la red
   **EdenAir-Setup**. (Eso comprueba que el QR está bien generado.)
3. Simulá que el equipo se conectó, desde otra terminal:

   ```bash
   curl -X POST http://localhost/piedra_castillo/public/api/devices/pair -H "Content-Type: application/json" -d "{\"mac\":\"AA:BB:CC:11:22:33\",\"firmware\":\"1.0.0\"}"
   ```

4. En menos de 3 segundos la página tiene que saltar sola a **"quedó conectado"**.
5. Volvé a *Mis dispositivos*: el equipo aparece como **Conectado**, con su MAC.

Casos que conviene mirar:

| Prueba | Resultado esperado |
|---|---|
| Correr el `curl` **sin** haber apretado Conectar | HTTP **202** `esperando_vinculacion` — no crea nada |
| Repetir el `curl` con la **misma MAC** | HTTP 200 con **el mismo** `device_uid` y `api_token`; no se duplica |
| Esperar los 10 minutos sin conectar nada | La pantalla pasa sola a **"Se agotó el tiempo"** |
| Apretar **Cancelar** y correr el `curl` | HTTP 202: una ventana cancelada no vincula |

**Ambientes** (`/panel/ambientes`)
- Listado con rangos y dispositivos asignados.
- Editar uno → validaciones (min < max, CO₂ > 0).

**Perfil** (`/panel/perfil`)
- Cambiar nombre/apellido confirmando con contraseña actual.
- Cambiar contraseña (actual + nueva + confirmación).
- No se muestran roles.

**Compra** (`/panel/compra`) — vista de checkout; el cobro todavía no se procesa.

---

## 10. Checklist final

| # | Punto | Estado |
|---|---|---|
| 1 | Login va directo al dashboard, no fuerza ambiente | ✅ Corregido (limpieza completa: rutas, métodos, vista, helpers y constantes legacy eliminados) |
| 2 | Pantalla de bienvenida cuando hay 0 dispositivos | ✅ `panel/bienvenida.php` |
| 3 | Modelo: cuenta tiene N dispositivos y N ambientes | ✅ `spaces` multi-row + `devices` |
| 4 | Conexión del equipo sin pasos intermedios | ✅ **Reemplazó al wizard de 4 pasos**: una pantalla, un botón, un QR (§3.5) |
| 5 | Sin código de activación ni nada que buscar en la caja | ✅ Esquema y flujo eliminados (§3.5.1) |
| 6 | El equipo se asigna a un ambiente solo | ✅ Reusa el primero del usuario, o crea uno "hogar" |
| 7 | El QR se genera en el servidor, sin dependencias | ✅ `app/Libraries/QrCode.php` |
| 8 | 7 ambientes sugeridos | ✅ Catálogo de `EnvironmentPresetService` (editable en /panel/ambientes) |
| 9 | Múltiples dispositivos por cuenta + switcher | ✅ `active_device_id` en sesión |
| 10 | Sección "Ambientes" en el dashboard | ✅ `/panel/ambientes` + editar |
| 11 | Sidebar único en TODAS las vistas internas | ✅ `partials/panel_sidebar.php` |
| 12 | Orden del menú: Inicio · Dispositivos · Ambientes · Automatizaciones · Plan · Perfil | ✅ |
| 13 | "Editar datos" dentro de Perfil | ✅ |
| 14 | "Comprar" como CTA destacado | ✅ `.ea-sidebar-item--cta` |
| 15 | Modo claro/oscuro sin mover el scroll | ✅ View Transitions eliminada + triple checkpoint |
| 16 | Toggle de tema es checkbox, no `<a href="#">` | ✅ |
| 17 | Landing: hero con slogan intacto | ✅ |
| 18 | Landing: sección de video "Ingeniería interna" | ✅ |
| 19 | Landing: sección de compra premium | ✅ `#comprar` |
| 20 | "24/7" se ve completo en todos los tamaños | ✅ |
| 21 | Análisis de competencia | ✅ Cuadro doble entrada + acordeón mobile |
| 22 | Perfil: nombre+apellido requeridos, no roles | ✅ |
| 23 | Responsive desktop/tablet/celular sin overflow | ✅ |
| 24 | SEO: title, description, Open Graph, Twitter | ✅ |
| 25 | Accesibilidad: labels, focus visible, aria-live, prefers-reduced-motion | ✅ |
| 26 | Performance: video lazy IO, preload metadata, WebM+fallback, cache-bust | ✅ |
| 27 | Documentación final con nueva lógica de usuario | ✅ |
| 28 | Endpoint real de telemetría conectado al ESP32 | ⏳ Hardware |
| 29 | Lecturas reales de sensores y comandos a actuadores | ⏳ Hardware |
| 30 | Firmware: punto de acceso `EdenAir-Setup` + portal cautivo + `POST api/devices/pair` | ⏳ Hardware (§8) |
| 31 | Pago real (MercadoPago / Stripe) | ⏳ Pendiente de integrar la pasarela |

---

## 11. Glosario y líneas de código importantes

### Términos

| Término | Significado |
|---|---|
| **Bienvenida** | Vista mostrada cuando el usuario tiene 0 dispositivos. Dos CTAs: conectar dispositivo y comprar. |
| **Panel monitor** | Vista del dashboard con métricas, actuadores, automatizaciones e historial del dispositivo activo. |
| **Switcher** | Selector en el header que permite cambiar entre dispositivos del usuario. Persiste en `active_device_id` (sesión). |
| **Ventana de vinculación** | Los 10 minutos que se abren al apretar "Conectar". El equipo que se presente a la API durante esa ventana queda asociado a esa cuenta. Fila en `device_pairings`. |
| **QR de vinculación** | El código que muestra la pantalla, generado en el momento. Lleva las credenciales del WiFi de configuración del equipo en el formato estándar `WIFI:T:WPA;S:…;P:…;;`. |
| **Punto de acceso de configuración** | El WiFi que publica la ESP32 cuando todavía no tiene red (`EdenAir-Setup`). Es una constante del firmware, porque la web arma el QR sin poder preguntarle nada al equipo. |
| **Claim code** *(obsoleto)* | Código `EDEN-XXXX-XXXX` del flujo viejo. **Eliminado**: ya no existe la tabla ni el paso. |
| **Catálogo de ambientes** | `EnvironmentPresetService::PRESETS` — perfiles con sus rangos de confort. |
| **Sidebar único** | `partials/panel_sidebar.php` — mismo componente en todas las vistas internas. |
| **CTA destacado** | Botón con identidad propia, gradiente y glow (clase `.ea-button-buy` o `.ea-sidebar-item--cta`). |
| **`data-reveal`** | Atributo que activa la animación de aparición al entrar en viewport. |
| **`prefers-reduced-motion`** | Preferencia del SO; si está activa, se evitan las animaciones grandes y los videos no autoreproducen. |
| **View Transitions API** | API de transiciones del navegador; se **eliminó** del cambio de tema porque corría el scroll. |
| **Triple checkpoint** | Las 3 restauraciones de scroll de `tema.js`: síncrono, microtask y rAF. |

### Líneas de código importantes

```php
// PanelController::index() — qué pantalla ver según cantidad de dispositivos.
if ($cantidadDispositivos === 0) {
    return view('panel/bienvenida', ['usuario' => $usuario ?? [...]]);
}
return view('panel', [
    'panel' => (new PanelService())->obtenerVistaPanel($userId, $activeDeviceId),
]);
```

```php
// PanelController::dispositivoActivo() — el dispositivo del switcher tiene que pertenecerle.
$valido = (new DeviceModel())->where('id', $candidato)
    ->where('user_id', $userId)->countAllResults() > 0;
if (! $valido) { session()->remove('active_device_id'); return null; }
```

```php
// DispositivosController::iniciar() — el clic en "Conectar".
// Abre la ventana y devuelve el QR ya dibujado, más el hash CSRF nuevo
// (el proyecto regenera el token en cada request).
$paquete = (new DevicePairingService())->abrirVentana(
    $this->usuarioActual(),
    $this->request->getIPAddress()
);
return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()] + $paquete);
```

```php
// DevicePairingService::payloadWifi() — lo que viaja adentro del QR.
// Formato estándar de WiFi: al escanearlo, el celular ofrece conectarse solo.
return 'WIFI:T:WPA;S:' . self::escaparWifi($ssid) . ';P:' . self::escaparWifi($password) . ';;';
```

```php
// DevicePairingService::registrarDispositivo() — un equipo que ya se dio de alta
// vuelve a entrar con SUS credenciales de siempre (reinicio, reflash) en vez de
// duplicarse.
$existente = $this->devices->where('mac_address', $mac)->first();
if ($existente) {
    $this->devices->update((int) $existente['id'], ['status' => 'active', 'last_seen_at' => date('Y-m-d H:i:s')]);
    return ['estado' => 'ok', 'mensaje' => '…', 'device' => $this->devices->find((int) $existente['id'])];
}
```

```php
// AmbientesController::editar() — un ambiente solo lo edita su dueño.
if (! $ambiente || (int) $ambiente['user_id'] !== $userId) {
    return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
}
```

```php
// partials/panel_sidebar.php — clase activa y aria-current basadas en $active.
$cls  = fn($key) => $active === $key ? 'ea-sidebar-item is-active' : 'ea-sidebar-item';
$aria = fn($key) => $active === $key ? ' aria-current="page"' : '';
```

```php
// panel/bienvenida.php — sin dispositivos no se inventa nada: la pantalla
// solo ofrece los dos caminos reales (conectar el equipo o comprarlo).
<a href="<?= site_url('panel/dispositivos/conectar') ?>"
   class="ea-button ea-button-primary ea-button-buy ea-button-block">Conectá tu Eden Air</a>
```

```js
// tema.js — sin View Transitions; tres checkpoints para que el scroll no salte.
const y = window.scrollY;
document.documentElement.dataset.theme = nuevoTema;
window.scrollTo(0, y);                                  // sincrónico
Promise.resolve().then(() => window.scrollTo(0, y));    // microtask
requestAnimationFrame(() => window.scrollTo(0, y));     // rAF
```
