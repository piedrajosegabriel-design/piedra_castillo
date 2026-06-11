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
| Recién registrada, **0 dispositivos** | Pantalla de **Bienvenida** con 3 CTAs: Agregar dispositivo · Ver demo · Comprar. |
| **≥ 1 dispositivo** | **Panel monitor** del dispositivo activo, con switcher entre dispositivos en el header. |

### Modelo de dominio actual

```
Usuario (1) ──┬─ (N) Dispositivos  ──── (1) Ambiente
              │
              └─ (N) Ambientes     ──── (N) Dispositivos
```

- **Usuario** → cuenta única (nombre, apellido, email, usuario, contraseña).
- **Dispositivo** → tiene un código de activación único, nombre, tipo y estado;
  pertenece a un usuario y a un ambiente.
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
| `app/Views/login.php` | Hint actualizado: *"Al entrar vas directo a tu panel; si todavía no tenés un dispositivo, te guiamos para vincularlo o probar la demo."* |
| `app/Services/DeviceProvisioningService.php` | Eliminado `hasConfiguredSpace()` (quedó sin consumidores). |

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
(dashboard, welcome, wizard, switcher, banner claim).

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
| `login.js`, `registro.js`, `panel.js`, `ambiente.js` | sus vistas | Interacciones puntuales de cada formulario. |

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
7. **Compra del producto** (`#comprar`). Tarjeta "Eden Air Core", precio demo, beneficios, CTA destacado.
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

### 3.1 Sidebar único — `app/Views/partials/dashboard_sidebar.php`

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
`dispositivos/agregar.php`, `ambientes/index.php`, `ambientes/editar.php`,
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
- 3 bullets explicando *Dispositivos*, *Ambientes*, *Monitoreo*.
- Grid con 3 tarjetas CTA:
  - **Agregar mi primer dispositivo** → `panel/dispositivos/agregar`.
  - **Ver demo del sistema** → form POST a `panel/demo` con `csrf_field()`.
  - **Comprar Eden Air** → `panel/compra`.
- Texto de ayuda sobre el código de activación.

**Conecta con:**
- `DispositivosController::agregar` (wizard).
- `PanelController::iniciarDemo` (botón "Probar la demo").
- `PanelController::compra` (vista compra).
- `partials/dashboard_sidebar` con `$active='inicio'` y `$devicesCount=0`.

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
- Si el usuario solo tiene el simulado auto-provisionado → **banner "¿Ya tenés tu Eden Air?"** que linkea al wizard.

**Conecta con:**
- `PanelService::obtenerVistaPanel`, `PanelService::obtenerDatos`.
- `PanelController::seleccionarDispositivo` (POST del switcher).
- `partials/dashboard_sidebar` con `$active='inicio'`.

### 3.4 Mis dispositivos — `app/Views/dispositivos/index.php`

**Ruta:** `GET panel/dispositivos` → `DispositivosController::index()`.

**Variables que recibe:**

| Variable | Origen |
|---|---|
| `$dispositivos` | `DeviceClaimService::listarDeUsuario($userId)` — array con `id`, `nombre`, `tipo`, `espacio`, `uid`, `estado`, `estado_label`, `estado_tono`, `mac`, `codigo`, `notas`, `es_simulado`. |

**Estructura:** grid de tarjetas (`auto-fill minmax(280px,1fr)`) + tarjeta extra "Agregar otro dispositivo".

**Estados visuales** (devueltos por `DeviceClaimService::estadoLegible()`):
- `active` → "Activo" / tono `success`.
- `offline` → "Sin conexión" / tono `danger`.
- `pending` → "Pendiente de configuración" / tono `warning`.
- default → "Simulado" / tono `info`.

### 3.5 Wizard "Conectá tu Eden Air" — `app/Views/dispositivos/agregar.php`

**Ruta:** `GET panel/dispositivos/agregar` → `DispositivosController::agregar()`.

**Variables que recibe:**

| Variable | Origen |
|---|---|
| `$tipos` | `DeviceClaimService::TIPOS` (4 radio-cards). |
| `$espacios` | `DeviceClaimService::ESPACIOS` (catálogo: dormitorio, living, aula, oficina, cocina, laboratorio, otro). |
| `$ambientesExistentes` | `SpaceModel`: ambientes ya creados por el usuario, con `id`, `label`, `tipo` (usados en el tab "Usar uno existente"). |

**4 pasos con barra de progreso:**

1. **Código de activación.** Input `EDEN-XXXX-XXXX`. Validación en vivo (debounce) llamando a `GET panel/dispositivos/validar?code=...`. Estados: `vacio`, `formato`, `inexistente`, `usado`, `deshabilitado`, `disponible`. Atajo: autocompletar `EDEN-DEMO-2026`.
2. **Datos del dispositivo.** `name` (sugerido por el código si trae `default_name`) + `device_type` (radio-cards).
3. **Ambiente.** Tabs *"Usar uno existente"* (solo si `count($ambientesExistentes) > 0`) vs *"Crear uno nuevo"* (catálogo + nombre si elige "Otro"). El tab elegido se manda en `space_mode` (`existing` | `new`).
4. **Confirmación.** Resumen + botón **Finalizar vinculación** (POST `panel/dispositivos`).

**Validación en vivo (paso 1):**
- Ruta `GET panel/dispositivos/validar` → `DispositivosController::validar()`.
- Por ser GET está exenta del filtro CSRF.
- Internamente: `DeviceClaimService::inspeccionarCodigo($codigo)`.

**Degradación elegante:** sin JavaScript, los 4 pasos se muestran completos y el form igual se puede enviar; el servidor valida igual con `DeviceClaimService::vincular()`.

**Al enviar (POST `panel/dispositivos`)** — `DispositivosController::guardar()`:
1. Lee `code`, `name`, `device_type`, `space_mode`, `space_id`, `space`, `space_custom`, `notes`.
2. Valida reglas básicas (longitudes).
3. Según `space_mode`:
   - `existing`: exige `space_id > 0`.
   - `new`: exige `space` válido (clave del catálogo).
4. Llama a `DeviceClaimService::vincular()` dentro de **una transacción**:
   - Revalida el código.
   - Crea (o reutiliza) el ambiente.
   - Crea el dispositivo (`device_uid` y `api_token` aleatorios).
   - Crea su `device_states` inicial.
   - Siembra historial simulado con `SimulationService::seedHistoryForDevice()`.
   - Marca el código como `claimed` (evita doble canje).
5. Redirige a `panel/dispositivos` con mensaje de éxito.

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
- Compra **simulada** (sin cobro real). Nota visible: *"Precio simulado para la presentación. No representa un valor comercial final."*

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

**Nuevos:**

- `app/Controllers/DispositivosController.php`
- `app/Controllers/AmbientesController.php`
- `app/Services/DeviceClaimService.php`
- `app/Models/DeviceActivationCodeModel.php`
- `app/Database/Migrations/2026-05-31-000002_CreateDeviceClaimSchema.php`
- `app/Database/Migrations/2026-05-31-000003_AllowMultipleSpacesPerUser.php`
- `app/Views/partials/dashboard_sidebar.php`
- `app/Views/panel/bienvenida.php`
- `app/Views/dispositivos/index.php`
- `app/Views/dispositivos/agregar.php`
- `app/Views/ambientes/index.php`
- `app/Views/ambientes/editar.php`
- `public/videos/eden-air-exploded.{mp4,webm,jpg}`

**Modificados (esta auditoría incluida):**

- `app/Controllers/AccesoController.php` — eliminado todo el flujo de selección de ambiente al login.
- `app/Controllers/PanelController.php` — `index()` ramifica; `iniciarDemo()`, `seleccionarDispositivo()`; guard renombrado a `redireccionarSiFaltaDispositivo`.
- `app/Services/PanelService.php` — `obtenerVistaPanel($userId, $activeDeviceId)` multi-dispositivo; `devices_list` para el switcher.
- `app/Services/DeviceClaimService.php` — `vincular()` admite `space_id` existente o `space` nuevo.
- `app/Services/DeviceProvisioningService.php` — eliminado `hasConfiguredSpace()` (huérfano).
- `app/Models/DeviceModel.php` — `allowedFields` ampliados + `obtenerDeUsuario()`.
- `app/Filters/GuestFilter.php` — siempre redirige a `/panel`.
- `app/Config/Routes.php` — añadidas rutas del Hito 2; eliminadas `panel/ambiente`.
- `app/Views/inicio.php` — navbar/CTA, hero, menú mobile, sección video, sección compra, SEO.
- `app/Views/panel.php` — sidebar única, banner claim, switcher de dispositivos.
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

- Imprimir/etiquetar el claim code (y/o QR) en cada unidad y cargarlo en `device_activation_codes` con su `mac_address` de fábrica.
- Confirmar voltajes (3.3 V/5 V) y consumo.
- Probar lectura individual de cada componente (DHT, CO₂, calidad, ventilador, humidificador, aromatizador, LED).
- Ya existe `POST api/devices/{uid}/measurements` con autenticación por `X-Device-Token`; falta el envío real del firmware.
- Ya existen `GET api/devices/{uid}/commands/pending` y `POST .../commands/{id}/executed`; falta conectarlos.
- Validar WiFi y alta del `device_uid`; alternar `status` `active`/`offline` según `last_seen_at`.
- Pago real (MercadoPago / Stripe): hoy es demo.

---

## 9. Cómo probar cada vista

> Base local: `http://localhost/piedra_castillo/public/`
> Migrar antes: `php spark migrate`.

**Landing** (`/`)
- Slogan con impacto; el "7" de **24/7** se ve completo.
- Sección **Tecnología interna** reproduce el video (sin audio) y pausa al salir de pantalla.
- Sección **Comprar** muestra Eden Air Core con precio demo.
- Cambio de **tema** estando scrolleado: la página **no salta**.

**Login y entrada** (`/login`)
- Logueate. Vas **directo a `/panel`** (sin paso intermedio).
- Si la cuenta no tiene dispositivos → ves **Bienvenida** con 3 CTAs.
- Si tiene 1+ → ves el **panel monitor**.

**Mis dispositivos** (`/panel/dispositivos`)
- Lista los dispositivos con estado y ambiente; botón **Agregar dispositivo**.

**Agregar dispositivo** (`/panel/dispositivos/agregar`)
1. Ingresá `EDEN-DEMO-2026` (o tocá el atajo) → debe decir **"Código válido"**.
2. Probá un código falso → debe rechazarlo.
3. Poné nombre, tipo y ambiente (existente o nuevo; probá *Otro* para el nombre extra).
4. Confirmá → vuelve a *Mis dispositivos* con el nuevo equipo.
5. Reintentá el mismo código → debe decir **"ya utilizado"**.

**Ambientes** (`/panel/ambientes`)
- Listado con rangos y dispositivos asignados.
- Editar uno → validaciones (min < max, CO₂ > 0).

**Perfil** (`/panel/perfil`)
- Cambiar nombre/apellido confirmando con contraseña actual.
- Cambiar contraseña (actual + nueva + confirmación).
- No se muestran roles.

**Compra** (`/panel/compra`) — vista de pago demo.

---

## 10. Checklist final

| # | Punto | Estado |
|---|---|---|
| 1 | Login va directo al dashboard, no fuerza ambiente | ✅ Corregido (limpieza completa: rutas, métodos, vista, helpers y constantes legacy eliminados) |
| 2 | Pantalla de bienvenida cuando hay 0 dispositivos | ✅ `panel/bienvenida.php` |
| 3 | Modelo: cuenta tiene N dispositivos y N ambientes | ✅ `spaces` multi-row + `devices` |
| 4 | Wizard de 4 pasos (código → datos → ambiente → confirmar) | ✅ |
| 5 | Wizard explica qué es el código de activación | ✅ Microcopy + `<details>` colapsable |
| 6 | Wizard permite ambiente existente o nuevo | ✅ Tabs + `space_mode` |
| 7 | 4 tipos de dispositivo seleccionables | ✅ Radio-cards |
| 8 | 7 ambientes sugeridos | ✅ Catálogo `DeviceClaimService::ESPACIOS` |
| 9 | Múltiples dispositivos por cuenta + switcher | ✅ `active_device_id` en sesión |
| 10 | Sección "Ambientes" en el dashboard | ✅ `/panel/ambientes` + editar |
| 11 | Sidebar único en TODAS las vistas internas | ✅ `partials/dashboard_sidebar.php` |
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
| 30 | Etiquetado físico de claim codes / QR | ⏳ Hardware |
| 31 | Pago real (MercadoPago / Stripe) | ⏳ Demo |

---

## 11. Glosario y líneas de código importantes

### Términos

| Término | Significado |
|---|---|
| **Bienvenida** | Vista mostrada cuando el usuario tiene 0 dispositivos. Tres CTAs: agregar, ver demo, comprar. |
| **Panel monitor** | Vista del dashboard con métricas, actuadores, automatizaciones e historial del dispositivo activo. |
| **Switcher** | Selector en el header que permite cambiar entre dispositivos del usuario. Persiste en `active_device_id` (sesión). |
| **Banner claim** | Aviso "¿Ya tenés tu Eden Air?" cuando el único dispositivo es el simulado, para invitar a vincular uno real. |
| **Wizard** | Asistente de 4 pasos para vincular un dispositivo nuevo. |
| **Claim code** | Código `EDEN-XXXX-XXXX` que viene con el producto. De un solo uso. Validable en vivo. |
| **`space_mode`** | Decisión del wizard en el paso 3: `existing` (usa un ambiente ya creado) o `new` (crea uno desde el catálogo). |
| **Catálogo de ambientes** | `DeviceClaimService::ESPACIOS` — 7 espacios sugeridos con su preset asociado. |
| **Tipos de dispositivo** | `DeviceClaimService::TIPOS` — 4 productos seleccionables en el alta. |
| **Sidebar único** | `partials/dashboard_sidebar.php` — mismo componente en todas las vistas internas. |
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
// DispositivosController::guardar() — el wizard decide entre ambiente existente o nuevo.
if ($datos['space_mode'] === 'existing') {
    if ($datos['space_id'] <= 0) { /* error: elegí uno */ }
} else {
    if (! $servicio->esEspacioValido($datos['space'])) { /* error: clave inválida */ }
}
$resultado = $servicio->vincular($this->usuarioActual(), $datos);
```

```php
// DispositivosController::validar() — validación en vivo del código (JSON, GET → sin CSRF).
$inspeccion = (new DeviceClaimService())->inspeccionarCodigo((string) $this->request->getGet('code'));
return $this->response->setJSON([
    'ok' => $inspeccion['ok'], 'estado' => $inspeccion['estado'], 'mensaje' => $inspeccion['mensaje'],
    'device_type' => $inspeccion['code']['device_type'] ?? null,
    'default_name' => $inspeccion['code']['default_name'] ?? null,
]);
```

```php
// AmbientesController::editar() — un ambiente solo lo edita su dueño.
if (! $ambiente || (int) $ambiente['user_id'] !== $userId) {
    return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
}
```

```php
// partials/dashboard_sidebar.php — clase activa y aria-current basadas en $active.
$cls  = fn($key) => $active === $key ? 'ea-sidebar-item is-active' : 'ea-sidebar-item';
$aria = fn($key) => $active === $key ? ' aria-current="page"' : '';
```

```html
<!-- panel/bienvenida.php — la demo es una acción explícita (POST), no auto-magia. -->
<form method="post" action="<?= site_url('panel/demo') ?>" class="ea-welcome-form">
    <?= csrf_field() ?>
    <button type="submit" class="ea-button ea-button-secondary ea-button-block">Probar la demo</button>
</form>
```

```js
// tema.js — sin View Transitions; tres checkpoints para que el scroll no salte.
const y = window.scrollY;
document.documentElement.dataset.theme = nuevoTema;
window.scrollTo(0, y);                                  // sincrónico
Promise.resolve().then(() => window.scrollTo(0, y));    // microtask
requestAnimationFrame(() => window.scrollTo(0, y));     // rAF
```
