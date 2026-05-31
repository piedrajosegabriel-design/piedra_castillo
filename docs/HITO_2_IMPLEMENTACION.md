# Eden Air — Hito 2 · Documentación de implementación

> Stack respetado: **PHP + CodeIgniter 4 + MySQL + HTML/CSS/JS** (sin migrar de framework).
> Fecha: 2026-05-31.

Este documento explica qué se hizo, cómo funciona cada cosa nueva, dónde está en el
proyecto y qué queda pendiente para cuando esté el hardware real (ESP32).

---

## 0. Nueva lógica de usuario (lo más importante del Hito 2)

**Antes** el sistema obligaba a elegir un ambiente al loguearse: si el usuario
no tenía un `space` configurado, lo redirigía a `/panel/ambiente` y no lo dejaba
ver el panel. Eso confundía: el ambiente es un concepto que sólo tiene sentido
cuando ya hay un dispositivo físico. **Ahora** el flujo es:

| Estado de la cuenta | Pantalla que ve el usuario |
|---|---|
| Recién registrada, **0 dispositivos** | Pantalla de **Bienvenida** con 3 caminos posibles: Agregar mi primer dispositivo · Ver demo del sistema · Comprar Eden Air. |
| **≥ 1 dispositivo** | **Panel monitor** del dispositivo activo, con switcher entre dispositivos en el header. |

### Modelo de dominio

```
Usuario (1) ──┬─ (N) Dispositivos  ──── (1) Ambiente
              │
              └─ (N) Ambientes     ──── (N) Dispositivos
```

- **Usuario** → cuenta única (nombre, apellido, email, usuario, contraseña).
- **Dispositivo** → tiene un código de activación único, nombre, tipo, estado,
  pertenece a un usuario y a un ambiente. Una cuenta puede tener N dispositivos.
- **Ambiente** → lugar físico donde está el dispositivo (Dormitorio, Living,
  Aula, Oficina, Cocina, Laboratorio, Otro). Tiene su propia configuración de
  confort (rangos de temperatura, humedad y CO₂). Un ambiente puede tener
  varios dispositivos.

### Flujo paso a paso

1. Usuario crea cuenta.
2. Usuario inicia sesión → **va directo al dashboard** (sin ningún paso
   intermedio).
3. Si no tiene dispositivos → pantalla **"Bienvenido a Eden Air"** con 3 CTAs.
4. Toca *"Agregar dispositivo"* → wizard de 4 pasos:
   1. **Código de activación** (con validación en vivo y explicación).
   2. **Datos del dispositivo** (nombre + tipo).
   3. **Ambiente**: usar uno existente o crear uno nuevo.
   4. **Confirmación** con resumen.
5. Al finalizar, el dispositivo aparece en **Mis dispositivos** y, si fue un
   ambiente nuevo, también en **Ambientes**.
6. Desde el dashboard puede:
   - Ver el panel monitor con switcher entre dispositivos.
   - Gestionar dispositivos (Mis dispositivos).
   - Gestionar ambientes (Ambientes) y editar rangos.
   - Ver/configurar automatizaciones (vista en preparación).
   - Comprar o cambiar de plan (Compra/Plan).
   - Editar perfil (Perfil).

### ¿Por qué ya no se pide ambiente al loguearse?

- Confundía a quien todavía no tiene dispositivo (no sabe para qué elige un
  ambiente sin tener algo que ponerle).
- Rompía el orden de aprendizaje: primero entendés qué es Eden Air, después
  decidís si tenés un dispositivo, después lo configurás.
- El ambiente forma parte del **alta del dispositivo**, no del alta del usuario.

Los archivos `app/Views/seleccion_ambiente.php` y la ruta `panel/ambiente`
siguen existiendo para no romper nada, pero **ya no se invocan en el flujo
normal**. La función `AccesoController::redirigirDespuesDelLogin()` siempre
devuelve `redirect()->to('/panel')` y es `PanelController::index()` el que
decide qué vista renderizar.

---

## Índice

1. [Cambios en la landing](#1-cambios-en-la-landing)
2. [Cambios en el dashboard](#2-cambios-en-el-dashboard)
3. [Cómo funciona la opción de compra](#3-cómo-funciona-la-opción-de-compra)
4. [Cómo funciona la edición de usuario](#4-cómo-funciona-la-edición-de-usuario)
5. [Cómo funciona el alta de dispositivo](#5-cómo-funciona-el-alta-de-dispositivo)
6. [Primera conexión del producto vendido](#6-primera-conexión-del-producto-vendido)
7. [Por qué claim code / QR en lugar de la MAC](#7-por-qué-claim-code--qr-en-lugar-de-la-mac)
8. [Soporte de múltiples dispositivos por cuenta](#8-soporte-de-múltiples-dispositivos-por-cuenta)
9. [Modo claro / oscuro (corrección del scroll)](#9-modo-claro--oscuro-corrección-del-scroll)
10. [Qué se hizo con el video](#10-qué-se-hizo-con-el-video)
11. [Cambios responsive](#11-cambios-responsive)
12. [Mejoras SEO y UI/UX](#12-mejoras-seo-y-uiux)
13. [Archivos tocados](#13-archivos-tocados)
14. [Pendiente para integración con ESP32](#14-pendiente-para-integración-con-esp32)
15. [Cómo probar cada vista](#15-cómo-probar-cada-vista)

---

## 1. Cambios en la landing

Archivo: [`app/Views/inicio.php`](../app/Views/inicio.php) · estilos en
[`public/CSS/inicio.css`](../public/CSS/inicio.css) y
[`public/CSS/eden-brand.css`](../public/CSS/eden-brand.css).

- **Slogan intacto.** Se mantiene la frase del hero (`Respirá mejor, viví más cómodo.`).
  Solo se reforzaron estilos y jerarquía alrededor; **no se cambió el texto del slogan**.
- **Navbar con jerarquía comercial.** El CTA de compra (`Comprar` / `Comprar Eden Air`)
  ahora tiene más peso visual que el login. La navbar recibe acciones a medida
  (`$eaNavActions`): para visitantes muestra *Iniciar sesión* (secundario) + *Comprar*
  (primario destacado); para usuarios logueados *Entrar al dashboard* + *Comprar*.
- **Botón de compra premium** (`.ea-button-buy`): gradiente de marca, glow cítrico y
  un *shine* sutil. Vive en `eden-brand.css` para estar disponible en todo el sitio
  (landing y panel) sin duplicar estilos.
- **Sección nueva "Ingeniería interna"** (`#tecnologia-interna`, eyebrow `03`): video
  exploded-view del dispositivo con 4 tarjetas flotantes (Sensores ambientales,
  Control inteligente, Automatización, Diseño eficiente). Ver punto 10.
- **Sección nueva de compra** (`#comprar`, eyebrow `07`): plan "Eden Air Core" con
  precio demo y beneficios. Ver punto 3.
- **Corrección del "7" de 24/7.** En *Environmental Control Core* el `7` de `24/7`
  se cortaba: el glifo en itálica con `background-clip: text` recortaba su borde
  derecho. Se corrigió en `.ea-core-facts li span` con `display:inline-block`,
  `padding: 0 .14em`, `line-height: 1.12` y `overflow: visible`. Se ve completo en
  desktop, tablet y celular.
- **Reveal/animaciones** existentes (`data-reveal`, `data-reveal-child`) se reutilizan;
  todas las animaciones nuevas respetan `prefers-reduced-motion`.

---

## 2. Cambios en el dashboard

- **Sidebar único** ([`app/Views/partials/dashboard_sidebar.php`](../app/Views/partials/dashboard_sidebar.php)):
  componente reutilizable que renderizan **todas** las vistas internas del
  dashboard. La estructura es la misma en cada pantalla:
  *Inicio · Mis dispositivos · Ambientes · Automatizaciones · Plan / Comprar · Perfil*.
  Acepta `$active` para resaltar el ítem actual y `$devicesCount` para el badge.
- *"Plan / Comprar"* se renderiza como **CTA destacado** (clase
  `.ea-sidebar-item--cta`) dentro de la sección Cuenta — ya no es un ítem plano
  ni un botón suelto en el header.
- **Vistas integradas con el sidebar único** (todas usan el partial):
  - `panel.php` (panel monitor con switcher de dispositivos)
  - `panel/bienvenida.php` (pantalla "Bienvenido a Eden Air")
  - `dispositivos/index.php` (Mis dispositivos)
  - `dispositivos/agregar.php` (wizard de alta)
  - `ambientes/index.php` (listado de ambientes)
  - `ambientes/editar.php` (editar rangos de un ambiente)
  - `perfil_usuario.php` (perfil)
  - `compra_mercadopago.php` (compra/plan)
- **Pantalla de Bienvenida**
  ([`app/Views/panel/bienvenida.php`](../app/Views/panel/bienvenida.php)):
  hero con saludo personalizado, 3 bullets explicando *dispositivos*,
  *ambientes* y *monitoreo*, y 3 tarjetas CTA (Agregar dispositivo · Ver demo ·
  Comprar). El usuario entiende en 5 segundos qué puede hacer.
- **Switcher de dispositivo activo** en el header del panel monitor cuando
  hay > 1 dispositivo (sesión `active_device_id` + ruta
  `POST panel/dispositivo-activo`).
- **"Mis dispositivos"** muestra grid de tarjetas con nombre, tipo, ambiente,
  estado con tono (Activo / Sin conexión / Pendiente / Simulado) y código
  utilizado para vincularlo. Tarjeta extra "Agregar otro dispositivo".
- **"Ambientes"** ([`app/Views/ambientes/index.php`](../app/Views/ambientes/index.php))
  lista los espacios del usuario con sus rangos de confort y los dispositivos
  asignados; cada uno linkea a la vista de edición.

---

## 3. Cómo funciona la opción de compra

- **En la landing** (`#comprar`): tarjeta premium **"Eden Air Core"** con badge
  *"Precio demo · presentación educativa"*, precio **USD 5** claramente marcado como
  *precio de prueba para la demo de tesina*, y un panel "Todo incluido" con beneficios
  concretos (configuración personalizada incluida, dashboard, multi-dispositivo,
  perfiles por espacio, preparado para automatización, enfoque sustentable).
  El ambiente personalizado **no se cobra aparte**: se comunica como beneficio incluido.
- **Destino del CTA:** si el usuario está logueado → `panel/compra`; si no →
  `registro` (crear cuenta y luego comprar). El secundario lleva a *Iniciar sesión*.
- **En el dashboard:** la sección **Comprar** (`panel/compra`, vista
  `compra_mercadopago`) sigue disponible desde la sidebar (sección Cuenta).
- El pago real **no** está activo: es una simulación/demo educativa, indicada
  visualmente con la nota *"Precio simulado para la presentación. No representa un
  valor comercial final."*

---

## 4. Cómo funciona la edición de usuario

Vista: [`app/Views/perfil_usuario.php`](../app/Views/perfil_usuario.php) ·
controlador: [`app/Controllers/PanelController.php`](../app/Controllers/PanelController.php)
(`perfil`, `actualizarPerfil`, `actualizarPassword`).

- Se presenta como **configuración de cuenta**, con dos formularios separados:
  1. **Datos** — Nombre, Apellido, Usuario, Email (+ contraseña actual para confirmar).
  2. **Contraseña** — contraseña actual, nueva y confirmación.
- **Sin roles** (el proyecto del cliente no los usa; la vista no muestra ninguno).
- **Validaciones** (en `PanelController`):
  - Nombre requerido (2–120). Apellido opcional.
  - Contraseña: solo cambia si el usuario completa el formulario de contraseña;
    exige mayúscula + minúscula + número y `password_confirm` coincidente.
  - Confirmación de identidad con la contraseña actual antes de aplicar cambios.
  - Mensajes de éxito/error vía flashdata.
- **Sobre el email:** se mantiene como campo porque es **necesario** para la
  recuperación de contraseña (flujo `recuperar` / `restablecer`). Quitarlo rompería
  ese flujo, por eso se conserva. No es un dato decorativo: es la vía de recuperación.

---

## 5. Cómo funciona el alta de dispositivo

Asistente **"Conectá tu Eden Air"** en `panel/dispositivos/agregar`
([`app/Views/dispositivos/agregar.php`](../app/Views/dispositivos/agregar.php)),
controlador [`DispositivosController`](../app/Controllers/DispositivosController.php),
lógica en [`DeviceClaimService`](../app/Services/DeviceClaimService.php).

Wizard de **4 pasos** con barra de progreso y explicación del código de activación:

1. **Código de activación.** El usuario ingresa el código `EDEN-XXXX-XXXX`.
   Validación **en vivo** (con *debounce*) contra `GET panel/dispositivos/validar`
   (JSON; GET para quedar exento de CSRF). Estados posibles: vacío, formato
   inválido, inexistente, ya usado, deshabilitado, disponible. Incluye un
   `<details>` que explica qué es el código, dónde encontrarlo, que es de un
   solo uso, etc. Atajo para autocompletar `EDEN-DEMO-2026`.
2. **Datos del dispositivo.** Nombre (pre-cargado con la sugerencia del código
   si existe) y **Tipo** elegido entre 4 opciones (Eden Air Core, Monitor
   ambiental, Ambientador inteligente, Prototipo educativo) presentadas como
   radio-cards con título y descripción.
3. **Ambiente.** Aparece con tabs *"Usar un ambiente existente"* /
   *"Crear uno nuevo"* — la primera solo se muestra si la cuenta ya tiene al
   menos un ambiente. En "existente" se elige un ambiente ya creado (radio
   list). En "nuevo" se elige de un catálogo (Dormitorio, Living, Aula,
   Oficina, Cocina, Laboratorio, Otro); si elige *Otro*, aparece un campo
   para nombrar el espacio.
4. **Confirmación.** Resumen de los 4 datos + microcopy ("El dispositivo
   quedará asociado a tu cuenta", "Una cuenta puede administrar varios
   dispositivos…", "Luego podrás configurar automatizaciones…") y botón
   **Finalizar vinculación**.

**Degradación elegante:** sin JavaScript, los tres pasos se muestran completos y el
formulario igual se puede enviar; el servidor valida igual con `DeviceClaimService`.

**Qué pasa al finalizar** (`POST panel/dispositivos` → `DispositivosController::guardar`
→ `DeviceClaimService::vincular`, dentro de una **transacción**):

1. Se revalida el código (existe y disponible).
2. Se crea un **ambiente** propio para el dispositivo (perfil por espacio).
3. Se crea el **dispositivo** asociado a la cuenta (tipo, `device_uid`, `api_token`,
   `status='simulated'`, MAC técnica si el código la trae, código de activación).
4. Se crea su **estado** inicial (`device_states`).
5. Se siembra **historial simulado** para que el panel tenga datos de inmediato.
6. Se marca el código como **`claimed`** (no se puede volver a usar).

Si algo falla, la transacción se revierte y se muestra un error claro sin dejar datos
a medias.

---

## 6. Primera conexión del producto vendido

El flujo web está listo aunque todavía no exista el hardware:

1. Encendé tu dispositivo Eden Air.
2. Buscá el código de activación del producto (o el generado para la maqueta).
3. Ingresá el código en la web (paso 1 del asistente).
4. Asignale nombre y ambiente (paso 2).
5. Finalizá la vinculación (paso 3): queda asociado a tu cuenta y aparece en
   *Mis dispositivos*.

**Datos simulados para pruebas:**

| Dato | Valor |
|------|-------|
| Código de ejemplo | `EDEN-DEMO-2026` |
| Dispositivo | Eden Air Core |
| Tipo | Monitor ambiental inteligente |
| Espacios sugeridos | Living / Aula / Dormitorio |

Además del código fijo, la migración siembra **8 códigos aleatorios** disponibles
(`EDEN-XXXX-XXXX`) para poder vincular varios dispositivos en la demo.

**Estructura de seguridad de la vinculación:**

- El código se marca como **usado** al canjearse (`status='claimed'`, con
  `claimed_by_user_id`, `device_id` y `claimed_at`).
- Un mismo código **no puede usarse en dos cuentas** (validado en servidor + índice
  `UNIQUE` en la columna `code`).
- Validación de formato y de campos, con **errores visuales** profesionales.
- No se exponen tokens sensibles en el frontend: el `api_token` del dispositivo se
  genera en el servidor y **no** se imprime en las vistas públicas.
- Hay un campo `mac_address` pensado como **dato privado/técnico** (ver punto 7).

---

## 7. Por qué claim code / QR en lugar de la MAC

> **Recomendación del proyecto: usar código de activación (o QR), no la MAC.**

- La **MAC** es un identificador *público y predecible* de la red: cualquiera que vea
  el dispositivo puede leerla. Usarla como contraseña permitiría que un tercero
  reclamara el equipo. Por eso aquí la MAC es **sólo un dato técnico interno**
  (`devices.mac_address`, `device_activation_codes.mac_address`) y **nunca** una
  credencial.
- El **claim code** (`EDEN-XXXX-XXXX`) es un secreto que viaja **con el producto
  físico** (etiqueta/QR): solo lo tiene quien compró el dispositivo. Es:
  - **Más seguro:** de un solo uso, revocable, marcable como usado, no derivable de la red.
  - **Más fácil para el cliente:** se escribe o se escanea por QR, sin tecnicismos.
- El esquema queda preparado para QR: el mismo código puede codificarse en un QR y el
  paso 1 del asistente aceptarlo igual (sólo cambia el método de ingreso).
- También se prevé un `api_token` por dispositivo como secreto privado para que el
  ESP32 autentique sus envíos a la API (no se muestra en frontend público).

---

## 8. Soporte de múltiples dispositivos por cuenta

- El esquema ya tenía `users`, `spaces`, `devices`, `measurements`, `device_states`,
  `device_commands`. Se sumaron `device_activation_codes` y metadatos en `devices`.
- **Bloqueo corregido:** `spaces` tenía `UNIQUE(user_id)` (un solo ambiente por
  usuario), lo que impedía varios dispositivos con su propio ambiente. La migración
  [`...000003_AllowMultipleSpacesPerUser`](../app/Database/Migrations/2026-05-31-000003_AllowMultipleSpacesPerUser.php)
  reemplaza ese índice único por uno normal (creando primero el índice nuevo, porque la
  FK `fk_spaces_user` exige un índice sobre `user_id` antes de poder quitar el UNIQUE).
- Ahora **una cuenta puede tener varios dispositivos**, cada uno con su ambiente/perfil.
  `DeviceModel::obtenerDeUsuario()` lista todos uniendo `spaces`.
- El **panel monitor** muestra el dispositivo activo del usuario. Cuando hay **más
  de uno**, aparece un **switcher** (`<select>` en el header) que permite cambiar
  entre dispositivos sin recargar contexto: el id elegido se guarda en sesión
  (`active_device_id`, validado contra los dispositivos del usuario) y `PanelService`
  reconstruye el panel con el dispositivo activo y su ambiente correspondiente.
  Cuando hay un único dispositivo, en su lugar se muestra un chip discreto con su
  nombre (no se invade el header con un selector innecesario).
- Si el usuario aún no vinculó un dispositivo real (solo tiene el auto-provisionado
  simulado), se muestra un **banner "¿Ya tenés tu Eden Air?"** en la parte superior
  del panel que enlaza directo al asistente de vinculación.

Tablas relevantes:

| Tabla | Rol |
|-------|-----|
| `users` | Cuentas. |
| `spaces` | Ambientes (ahora varios por usuario). |
| `devices` | Dispositivos: `device_type`, `status`, `mac_address`, `activation_code`, `notes`. |
| `device_activation_codes` | Claim codes (único, estado, quién/cuándo lo canjeó). |
| `measurements` | Lecturas ambientales (telemetría). |
| `device_states` | Modo y estado de actuadores. |
| `device_commands` | Comandos hacia el dispositivo. |

---

## 9. Modo claro / oscuro (corrección final del scroll)

Archivos: [`public/JS/tema.js`](../public/JS/tema.js),
[`app/Views/partials/theme_toggle.php`](../app/Views/partials/theme_toggle.php).

**Problema original.** Al cambiar de tema el scroll se movía o subía. Probamos
con preservación simple de `window.scrollY`; mejoraba pero el salto residual
seguía apareciendo en algunas secciones.

**Causa raíz identificada.** La **View Transitions API**
(`document.startViewTransition`) que usábamos para la transición circular toma
una "instantánea" del estado viejo y la nueva. El navegador re-mapea el scroll
contra la nueva instantánea y, si las alturas difieren mínimamente entre temas
(por ejemplo, scrollbars o sub-píxeles), corre la posición.

**Solución final (rebuild del módulo).**

1. **Se eliminó completamente la View Transitions API** del cambio de tema
   (tanto en `tema.js` como las reglas `::view-transition-*` en
   `eden-brand.css`). La transición visual queda como **CSS-only** sobre los
   colores del shell — más suave y sin tocar scroll.
2. Se introdujo `aplicarTemaPreservandoScroll()` con **3 checkpoints** de
   restauración:
   - **Síncrono** inmediatamente después de cambiar `data-theme`.
   - **Microtask** (`Promise.resolve().then`), antes del primer paint.
   - **rAF** (`requestAnimationFrame`), después del primer paint.
3. Antes del cambio se fuerza `scroll-behavior: auto` en `html` y `body` para
   que las restauraciones de scroll sean instantáneas (no animadas) y se
   restaura el valor previo al final.

**El toggle es accesible y correcto.** Es un `<label>` que envuelve un
`<input type="checkbox">` — **no** es un `<a href="#">`, no navega, no recarga,
no muta la URL. Tiene focus visible, animaciones suaves del thumb, y se ve
bien en claro y oscuro.

---

## 10. Qué se hizo con el video

Origen: `Smart_device_exploded_view_anima…_202605310022.mp4` (8 s, 1280×720, **con
audio**). Procesado con FFmpeg.

Salidas en [`public/videos/`](../public/videos/):

| Archivo | Detalle |
|---------|---------|
| `eden-air-exploded.mp4` | H.264, **sin audio**, `-crf 24`, `+faststart` · ~934 KB |
| `eden-air-exploded.webm` | VP9, **sin audio**, `-crf 34` · ~682 KB (mejor compresión) |
| `eden-air-exploded-poster.jpg` | Póster (frame a 3 s) · ~52 KB |

- **Sin audio:** se eliminó la pista con `-an` (no solo silenciado: el archivo no tiene
  audio).
- **Integración sofisticada** en la sección `#tecnologia-interna` de la landing:
  video centrado con `glow`/`vignette` y **tarjetas flotantes** alrededor en desktop
  (que pasan a grilla bajo el video en mobile).
- **Atributos:** `autoplay`, `muted`, `loop`, `playsinline` (+ `webkit-playsinline`),
  `preload="metadata"`, `<source>` WebM **+** fallback MP4, `poster`.
- **Performance:** un `IntersectionObserver` reproduce el video sólo cuando está en
  pantalla y lo pausa al salir (ahorra CPU/batería). Con `prefers-reduced-motion` no
  autoreproduce y muestra el póster estático.
- El video original pesado (`eden-air-scroll.mp4`, 26 MB) y el de scroll existente no
  se tocaron.

Comando de referencia (FFmpeg) usado:

```bash
# MP4 sin audio optimizado
ffmpeg -i origen.mp4 -an -c:v libx264 -profile:v high -pix_fmt yuv420p \
       -crf 24 -preset slow -movflags +faststart eden-air-exploded.mp4
# WebM VP9
ffmpeg -i origen.mp4 -an -c:v libvpx-vp9 -crf 34 -b:v 0 -row-mt 1 eden-air-exploded.webm
# Póster
ffmpeg -ss 00:00:03 -i origen.mp4 -frames:v 1 -q:v 3 eden-air-exploded-poster.jpg
```

---

## 11. Cambios responsive

- **Landing:** la navbar suma "Tecnología" y "Comprar"; el menú hamburguesa móvil
  incluye los nuevos accesos + CTA de compra destacado. La sección de video pasa de
  tarjetas flotantes (desktop) a grilla 2×/1× (mobile). La sección de compra pasa de
  2 columnas a 1 en pantallas chicas.
- **Dashboard:** grid de dispositivos con `auto-fill minmax(280px,1fr)`; toolbar que se
  apila en mobile; wizard con navegación a ancho completo en pantallas chicas.
- **Análisis de competencia:** tabla con **scroll horizontal** en tablet/desktop y
  **acordeón por competidor** en mobile (≤ 720px) usando los **mismos datos**.
- Se cuidó **no introducir overflow horizontal** y respetar `prefers-reduced-motion`
  en todas las animaciones nuevas.

---

## 12. Mejoras SEO y UI/UX

- **Jerarquía de encabezados** coherente: `h1` del hero, `h2` por sección con eyebrows
  numerados (01–08), `h3` en tarjetas. Las secciones nuevas usan `aria-labelledby`.
- **Accesibilidad:** `aria-label`/`aria-live` en el feedback del código, `radiogroup`
  para los ambientes, foco visible, navegación por teclado en el wizard, `region`
  con `tabindex` en la tabla scrolleable, labels en todos los campos.
- **Meta:** las vistas privadas (panel/dispositivos) llevan `robots: noindex,nofollow`
  y `color-scheme: light dark`. La landing mantiene su `title`/`description`.
- **Performance:** video lazy por visibilidad, `preload=metadata`, WebM + fallback,
  cache-busting por `filemtime` de CSS/JS/video, animaciones sólo donde aportan.
- **UI premium:** botón de compra con identidad propia, tarjetas con jerarquía,
  estados con tono semántico, microcopy de confianza, sin saturación visual.

---

## 13. Archivos tocados

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
- `public/videos/eden-air-exploded.mp4`
- `public/videos/eden-air-exploded.webm`
- `public/videos/eden-air-exploded-poster.jpg`
- `docs/HITO_2_IMPLEMENTACION.md` (este archivo)

**Modificados:**

- `app/Controllers/AccesoController.php` — quitado el redirect forzado a
  `panel/ambiente` después del login.
- `app/Controllers/PanelController.php` — `index()` ramifica entre bienvenida
  (0 dispositivos) y panel monitor (≥1); `iniciarDemo()`, `seleccionarDispositivo()`;
  guard antiguo de ambiente cambiado por guard de dispositivos.
- `app/Controllers/DispositivosController.php` — wizard de 4 pasos, soporte
  para ambiente existente o nuevo (`space_mode`).
- `app/Services/PanelService.php` — `obtenerVistaPanel($userId, $activeDeviceId)`
  multi-dispositivo; devuelve `devices_list` para el switcher.
- `app/Services/DeviceClaimService.php` — `vincular()` admite `space_id`
  existente o `space` nuevo.
- `app/Models/DeviceModel.php` — `allowedFields` + `obtenerDeUsuario()`.
- `app/Views/inicio.php` — navbar/CTA, hero, menú mobile, sección video,
  sección de compra, SEO meta tags (description + OG + Twitter).
- `app/Views/panel.php` — refactor a sidebar única (partial), banner
  "¿Ya tenés tu Eden Air?", switcher de dispositivos en header.
- `app/Views/perfil_usuario.php`, `compra_mercadopago.php` — refactor a
  sidebar única.
- `app/Views/portfolio.php` — análisis de la competencia completo (PDF Nº 3).
- `app/Config/Routes.php` — rutas `panel/dispositivos*`, `panel/ambientes*`,
  `panel/dispositivo-activo`, `panel/demo`.
- `public/JS/tema.js` — **eliminada** la View Transitions API; triple
  checkpoint de scroll restore + `scroll-behavior: auto` temporal.
- `public/CSS/inicio.css` — fix "24/7", estilos sección video/compra.
- `public/CSS/eden-brand.css` — `.ea-button-buy`, `.ea-button-sm`;
  eliminadas las reglas `::view-transition-*`.
- `public/CSS/dashboard.css` — estilos de welcome, wizard, ambientes,
  switcher, claim banner, sidebar CTA.
- `public/CSS/portfolio.css` — cuadro comparativo de competencia.
- `mysql_setup.sql` — índice multi-ambiente, columnas nuevas en `devices`,
  tabla `device_activation_codes` + seed de `EDEN-DEMO-2026`.

---

## 14. Pendiente para integración con ESP32

- **Definir el código físico real del dispositivo:** imprimir/etiquetar el claim code
  (y/o QR) en cada unidad y cargarlo en `device_activation_codes` (con su `mac_address`
  de fábrica como dato técnico).
- **Confirmar voltajes** de sensores y actuadores (3.3 V/5 V) y consumo.
- **Probar lectura individual** de cada componente (DHT/temp-humedad, CO₂, calidad de
  aire, ventilador, humidificador, aromatizador, LED de alerta).
- **Endpoint real de telemetría:** ya existe `POST api/devices/{uid}/measurements`
  ([`DeviceApiController`](../app/Controllers/Api/DeviceApiController.php)); validar el
  envío real del ESP32 autenticado con su `api_token`.
- **Endpoint de configuración/comandos:** ya existen
  `GET api/devices/{uid}/commands/pending` y `POST .../commands/{id}/executed`;
  conectar el firmware para recibir configuración ambiental por espacio.
- **Validar conexión WiFi** del dispositivo y el alta del `device_uid`.
- **Probar la vinculación real** con claim code / QR contra un equipo físico y marcar
  el `status` del dispositivo como `active`/`offline` según el último envío
  (`last_seen_at`).
- **Switch de dispositivo activo** en el panel monitor (parametrizar `PanelService`
  por `device_id`) para alternar la vista entre los dispositivos de la cuenta.

---

## 15. Cómo probar cada vista

> Base local: `http://localhost/piedra_castillo/public/`
> Migrar antes: `php spark migrate` (ya aplicado en este entorno).

**Landing** (`/`)
- El slogan se ve con impacto; el "7" de **24/7** aparece **completo**.
- La sección **Tecnología interna** reproduce el video (sin audio) y pausa al salir
  de pantalla.
- La sección **Comprar** muestra el plan Eden Air Core con precio demo.
- Cambiá de **tema** (sol/luna) estando scrolleado: **la página no salta**.

**Análisis de la competencia** (`/portfolio` → pestaña *Análisis de la competencia*,
ancla `#analisis-competencia`)
- En desktop: tabla comparativa con scroll horizontal (columna Eden Air resaltada).
- En mobile (≤ 720px): acordeón por competidor.
- Debajo: diferenciación, razones de compra, ventajas, sustentabilidad y conclusión.

**Mis dispositivos** (`/panel/dispositivos`, requiere login)
- Lista los dispositivos con estado y ambiente; botón **Agregar dispositivo**.
- Si no hay ninguno, muestra estado vacío con el código demo.

**Agregar dispositivo** (`/panel/dispositivos/agregar`)
1. Ingresá `EDEN-DEMO-2026` (o tocá el atajo) → debe validar **"Código válido"**.
   Probá un código falso → debe rechazarlo.
2. Poné nombre, tipo y ambiente (probá *Otro* para ver el campo extra).
3. Confirmá → vuelve a *Mis dispositivos* con el nuevo equipo listado.
4. Volvé a intentar el mismo código → debe decir **"ya utilizado"**.

**Editar datos** (`/panel/perfil`)
- Cambiá nombre/apellido confirmando con tu contraseña actual → mensaje de éxito.
- Cambiá la contraseña (actual + nueva + confirmación) → validaciones y éxito.
- No se muestran roles.

**Compra** (`/panel/compra`) — vista de pago demo (MercadoPago), sin cobro real.

---

### Nota sobre datos de la comparativa

Los **precios y datos de mercado** de la sección de competencia son **estimativos /
de referencia** (importación y tiendas oficiales) y están marcados como tales. Sirven
para la comparación académica y pueden ajustarse con relevamiento real sin cambiar la
estructura.

---

## 16. Cambios de la auditoría (post-implementación)

Tras una **auditoría profunda** del Hito 2 se detectaron y corrigieron varios puntos
que habían quedado débiles o incompletos:

- **Selector de dispositivo activo** en el header del panel monitor cuando el usuario
  tiene más de un dispositivo (antes mostraba siempre el primero). Implementado con
  sesión y validación de pertenencia. Si hay un solo dispositivo, se muestra un chip
  con su nombre en lugar del selector.
- **Banner "¿Ya tenés tu Eden Air?"** en el panel cuando el usuario sólo tiene el
  dispositivo simulado auto-provisionado, para invitar a la primera vinculación real
  (sin bloquear el uso del panel).
- **Sidebar del panel reordenada** según la recomendación del enunciado:
  *Dashboard · Mis dispositivos · (Este ambiente: Sensores · Actuadores · Lecturas ·
  Automatizaciones) · (Cuenta: Perfil · Plan / Comprar)*. *"Comprar"* dejó de ser un
  ítem plano: ahora se renderiza como **CTA destacado** dentro de la sección Cuenta
  (clase `.ea-sidebar-item--cta`).
- **Perfil:** `apellido` pasó de opcional a **requerido** (alineado con la consigna
  "Nombre y apellido no vacíos").
- **24/7 reforzado:** además del padding y line-height, se quitó el `letter-spacing`
  negativo (mala combinación con itálicas + `background-clip:text`) y se ajustaron
  paddings asimétricos para compensar el slant del glifo en cualquier tamaño/tema.
- **SEO meta tags** en la landing: `meta description`, `keywords`, **Open Graph**
  (type/title/description/image/locale) y **Twitter Card**. Antes solo había `title`.
- **Documentación final** con la **checklist obligatoria** al pie (sección 19).

---

## 19. Checklist final (post-rebuild)

| # | Punto | Estado |
|---|-------|--------|
| 1 | Login va directo al dashboard, no fuerza ambiente | ✅ Corregido |
| 2 | Pantalla de bienvenida cuando hay 0 dispositivos | ✅ Completado (`panel/bienvenida.php`) |
| 3 | Modelo: cuenta tiene N dispositivos y N ambientes | ✅ Completado (`spaces` multi-row + `devices`) |
| 4 | Wizard de 4 pasos (código → datos → ambiente → confirmar) | ✅ Completado |
| 5 | Wizard explica qué es el código de activación | ✅ Completado (microcopy + `<details>` colapsable) |
| 6 | Wizard permite ambiente existente o nuevo | ✅ Completado (tabs + `space_mode`) |
| 7 | 4 tipos de dispositivo seleccionables | ✅ Completado (radio-cards) |
| 8 | 7 ambientes sugeridos (Dormitorio, Living, Aula, Oficina, Cocina, Laboratorio, Otro) | ✅ Completado |
| 9 | Múltiples dispositivos por cuenta + switcher en panel | ✅ Completado |
| 10 | Sección "Ambientes" en el dashboard | ✅ Completado (`/panel/ambientes` + editar) |
| 11 | Sidebar único en TODAS las vistas del dashboard | ✅ Completado (`partials/dashboard_sidebar.php` en 8 vistas) |
| 12 | Orden del menú: Inicio · Dispositivos · Ambientes · Automatizaciones · Plan · Perfil | ✅ Completado |
| 13 | "Editar datos" dentro de Perfil | ✅ Completado |
| 14 | "Comprar" como CTA destacado, no botón suelto | ✅ Completado (`.ea-sidebar-item--cta`) |
| 15 | Modo claro/oscuro sin mover el scroll | ✅ Corregido (View Transitions eliminada + triple checkpoint) |
| 16 | Toggle de tema es `<input type="checkbox">`, no `<a href="#">` | ✅ Correcto desde el inicio |
| 17 | Landing: hero impactante con slogan intacto | ✅ Completado (frase original conservada, estilos reforzados) |
| 18 | Landing: sección de video "Ingeniería interna" | ✅ Completado (autoplay/muted/loop/playsinline + lazy IO) |
| 19 | Landing: sección de compra premium | ✅ Completado (#comprar) |
| 20 | "24/7" se ve completo en desktop/tablet/celular y claro/oscuro | ✅ Corregido (padding asimétrico + letter-spacing 0) |
| 21 | Análisis de la competencia (TP Nº 3) | ✅ Completado (cuadro doble entrada + acordeón mobile + conclusión) |
| 22 | Perfil: nombre+apellido requeridos, no roles | ✅ Corregido (`apellido` ahora required) |
| 23 | Responsive desktop/tablet/celular sin overflow | ✅ Completado |
| 24 | SEO: title, meta description, Open Graph, Twitter | ✅ Completado |
| 25 | Accesibilidad: labels, focus visible, aria-live, prefers-reduced-motion | ✅ Completado |
| 26 | Performance: video lazy IO, preload metadata, WebM+fallback, cache-bust | ✅ Completado |
| 27 | Documentación final con nueva lógica de usuario | ✅ Completado (sección 0 + este checklist) |
| 28 | Endpoint real de telemetría conectado al ESP32 | ⏳ Pendiente por hardware |
| 29 | Lecturas reales de sensores y comandos a actuadores | ⏳ Pendiente por hardware |
| 30 | Etiquetado físico de claim codes / QR en cada unidad | ⏳ Pendiente por hardware |
| 31 | Pago real (MercadoPago / Stripe) | ⏳ Pendiente (hoy es demo educativa) |
