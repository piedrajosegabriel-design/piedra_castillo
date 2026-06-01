# Hito 2 — Eden Air

## ¿Qué es Eden Air?

Eden Air es una plataforma web pensada para acompañar a un sistema inteligente de **monitoreo y ambientación automática de espacios interiores**. La idea del producto es simple: un pequeño dispositivo Eden Air mide en tiempo real la temperatura, la humedad, la concentración de CO₂ y la calidad general del aire de un ambiente, y la página web le permite al usuario ver toda esa información de forma clara, configurar sus espacios, administrar sus dispositivos y, más adelante, automatizar acciones.

El proyecto integra dos partes: la página web (lo que estamos entregando en este hito) y el hardware con ESP32 + sensores, que se conectará más adelante. La web está pensada desde el primer día para que esa integración futura sea natural y no implique rehacer toda la interfaz.

## ¿Cómo era la página antes?

En el primer hito la página existía pero todavía era muy técnica y poco amigable. El dashboard mostraba muchos datos juntos, sin jerarquía clara. Había botones sueltos como "Editar datos" o "Comprar" puestos como ítems del menú principal, lo que confundía al usuario. La vista de compra obligaba a hacer scroll para ver el plan. La página de bienvenida no existía: cuando un usuario nuevo entraba, le aparecía el dashboard vacío o se le pedía elegir un ambiente apenas se logueaba, sin contexto. La idea de "dispositivo" y "ambiente" estaba mezclada y no se explicaba bien. El cambio entre modo claro y oscuro movía el scroll de la página.

## ¿Qué se mejoró ahora?

En este Hito 2 se hizo un trabajo profundo de diseño, organización y experiencia de usuario. Eden Air dejó de sentirse como una maqueta y empezó a sentirse como un producto real. Se pulieron las pantallas existentes, se crearon nuevas vistas que faltaban (mis dispositivos, ambientes, alta de dispositivo, bienvenida) y se unificó toda la navegación.

A continuación se detallan los cambios por área.

## Cambios en la landing page

La landing se afinó para que el primer contacto del usuario con Eden Air sea más claro y profesional. El hero principal comunica de entrada qué hace el producto, con un slogan más directo y botones de llamada a la acción visibles. Se ordenó la jerarquía visual de las secciones: primero qué es, después cómo funciona, después qué tecnología hay adentro, después la sección de compra y finalmente la sección de confianza y sustentabilidad. Se ajustaron las animaciones para que sean sutiles y premium, sin distraer.

El portfolio del equipo y el video del producto quedaron integrados sin sentir que son "pegados" desde otra parte. El modo claro y oscuro se ven correctos en todas las secciones. La landing es responsive y se ve cómoda tanto en escritorio como en celular.

## Cambios en el dashboard

El dashboard se reorganizó por completo. Ahora tiene una estructura clara con un menú lateral fijo y un encabezado consistente. Las secciones del menú son: Inicio, Mis dispositivos, Ambientes, Automatizaciones, Perfil y Plan/Comprar. Se eliminaron los botones sueltos sin lógica: "Perfil" reemplaza el viejo "Editar datos" y queda en su sección de cuenta, y "Comprar" se transformó en una acción destacada llamada Plan/Comprar.

Dentro del Inicio del dashboard se redibujó el "resumen del ambiente" como una sección protagonista (tipo hero del dashboard) con el estado general del ambiente, las métricas principales (temperatura, humedad, CO₂ y calidad del aire), última actualización, modo de operación y conexión del dispositivo. Cada sensor es ahora más legible: número grande, unidad clara, color según el estado y barra indicadora del rango ideal.

El bloque "Estado del sistema" se volvió más entendible: muestra si el sistema está en línea, si la ESP32 está preparada, cuántos actuadores hay activos, si hay alertas y en qué modo está funcionando (automático o manual). El selector entre automático y manual se rediseñó para que parezca lo que es: un control importante que cambia el comportamiento del sistema.

Los actuadores y las automatizaciones se explican en lenguaje del usuario, no en lenguaje técnico. Las lecturas recientes muestran solo las últimas y permiten expandir el historial con un botón "Ver más", para no saturar la pantalla.

## Cambios en la navegación

Todas las vistas internas comparten la misma barra lateral. Se creó un componente único de navegación, así nunca más vamos a tener una pantalla con un menú distinto. El ítem activo siempre se marca visualmente, el botón de cerrar sesión es visible y claro, y el botón "hamburguesa" funciona bien en celular (cuando la pantalla es chica, el menú se convierte en un cajón lateral que se abre y cierra con animación).

Se reordenó la jerarquía: primero la navegación funcional del producto (Inicio, Mis dispositivos, Ambientes, Automatizaciones), después la sección de cuenta (Perfil) y al final la acción comercial (Plan/Comprar).

## Cambios en la vinculación de dispositivos

Se creó una vista nueva, "Conectá tu Eden Air", que es un asistente de cuatro pasos pensado para que cualquier persona pueda vincular un dispositivo sin pedir ayuda. Los pasos son: ingresar el código de activación, ponerle un nombre y elegir el tipo, elegir un ambiente (existente o nuevo) y, finalmente, revisar y confirmar.

Se explica con claridad qué es el código de activación: es el identificador único que viene con el producto, sirve para asociarlo a la cuenta y se puede usar una sola vez. Se aclara que puede estar en la caja, en una etiqueta o en un QR. Para que el profesor y los compañeros puedan probar el flujo sin un dispositivo real, hay un código demo (EDEN-DEMO-2026) que carga un dispositivo simulado.

La dirección MAC quedó como dato técnico interno, ya no se le pide al usuario. El asistente valida el código en vivo, sugiere un nombre por defecto y guía paso a paso. Si no hay JavaScript, igual funciona, solo que muestra todo en una sola página.

## Cambios en los ambientes

Antes la palabra "ambiente" estaba mezclada con "dispositivo". Ahora la lógica es clara: un usuario tiene una cuenta, puede tener varios dispositivos y puede crear varios ambientes. Cada dispositivo se asigna a un ambiente. Un ambiente es el lugar físico (dormitorio, aula, oficina, laboratorio…) y tiene sus propios rangos de confort (temperatura mínima y máxima, humedad mínima y máxima, CO₂ máximo).

Se creó la vista "Ambientes", donde el usuario ve todos sus ambientes, cuántos dispositivos hay en cada uno y puede editar los rangos. También se creó la vista de edición de ambiente con un formulario claro y validado.

## Cambios en compra o plan

Se rediseñó por completo. La compra ya no obliga a hacer scroll para ver el plan. Ahora apenas se entra a la sección de Plan/Comprar se ve una sola tarjeta principal, centrada, con el nombre del plan, el precio destacado, los beneficios incluidos y un botón claro de compra. La tarjeta deja en evidencia que la compra todavía es simulada (no hay integración de pago real), pero se ve como una sección de producto real y bien pensada. Es responsive y se adapta correctamente a celular.

## Cambios en perfil

La sección "Perfil" reemplaza al viejo "Editar datos". Tiene una introducción breve (no se ve como un formulario técnico) y se divide en dos tarjetas: una para los datos personales (nombre y apellido) y otra para cambiar la contraseña. El campo de email se muestra solo si la cuenta ya lo tenía, y queda como dato no editable. No se piden roles ni datos innecesarios. Cualquier cambio pide la contraseña actual para confirmar, lo cual da seguridad. El diseño es consistente con el resto del dashboard.

## Cambios en modo claro y oscuro

Se corrigió un problema importante: antes, cuando el usuario alternaba entre modo claro y oscuro, el scroll de la página saltaba al inicio. Ahora el cambio de tema mantiene exactamente la posición del scroll. El switch es un botón accesible (no es un enlace) y el cambio se hace sin recargar la página ni cambiar la ruta.

Visualmente, ambos modos se cuidaron: el contraste es suficiente, los colores tienen intención (verde para estado correcto, ámbar/clay para advertencia, coral para crítico, azul/cian para info técnica) y los bordes, sombras y fondos quedan equilibrados.

## Cambios en responsive

Toda la página se revisó en escritorio, tablet y celular. La barra lateral del dashboard se transforma en menú hamburguesa cuando la pantalla es chica. Las tarjetas se reacomodan en columnas adecuadas, no quedan apretadas. Los formularios son cómodos de usar con el teclado del celular. Las tablas de lecturas usan scroll horizontal solo cuando hace falta. No hay scroll horizontal indeseado en ninguna vista.

## Cambios en accesibilidad, SEO y rendimiento

En accesibilidad se trabajó el contraste de colores, los `focus` visibles en botones y enlaces, los `labels` de los formularios, los `aria-label` donde corresponde y la navegación por teclado. Se respeta `prefers-reduced-motion` para usuarios que prefieren menos animaciones.

En SEO se agregaron `meta description`, `meta robots` (las páginas privadas como el panel quedan como `noindex,nofollow`), `meta color-scheme`, titles únicos y descriptivos, y H1 únicos en cada vista. Los encabezados siguen una jerarquía correcta.

En rendimiento se evitó duplicar CSS, se removió código muerto de versiones anteriores, se mantuvieron las animaciones sutiles y se respeta el `prefers-reduced-motion`. La página se siente liviana al cargar.

## Cómo la página acompaña mejor al usuario

Ahora el recorrido tiene sentido. El usuario llega a la landing, entiende rápido qué es Eden Air, qué problema resuelve y por qué le sirve. Si quiere probar, puede registrarse. Cuando entra al dashboard por primera vez, no aparece una pantalla vacía y confusa: aparece una bienvenida personalizada que le explica los conceptos (dispositivo, ambiente, monitoreo) y le ofrece tres caminos claros: agregar su primer dispositivo, probar la demo o ir a comprar. Una vez que vincula un dispositivo, el dashboard cobra vida y muestra el estado real del ambiente. Desde el menú lateral siempre puede ir a Mis dispositivos, Ambientes, Automatizaciones, Perfil o Plan.

## Cómo queda preparada para la integración con ESP32 y sensores

La web ya tiene un canal de comunicación pensado para el hardware. Existe un controlador de API REST (`DeviceApiController`) con tres endpoints: uno para que el dispositivo envíe mediciones, otro para que consulte si hay comandos pendientes (encender ventilador, encender aromatizador, encender LED de alerta…) y otro para marcar comandos como ejecutados. La autenticación se hace con un token único por dispositivo. Esto significa que cuando el equipo de hardware termine la ESP32, lo único que va a hacer es apuntar a esas direcciones, mandar su token y empezar a transmitir datos reales. La interfaz no necesita cambios estructurales para soportar dispositivos reales.

## Qué partes todavía pueden ser simuladas

Mientras el hardware no esté terminado, la página acepta datos simulados sin perder coherencia. Por ejemplo: la compra es simulada (no hay pasarela de pago activa, pero queda muy claro visualmente que es una demo). El código de activación demo (EDEN-DEMO-2026) permite crear un dispositivo de prueba para recorrer el dashboard como si fuera real. Las mediciones, cuando no hay todavía un dispositivo real enviando datos, se muestran con valores de ejemplo claramente marcados como "Datos de ejemplo" para no confundir al usuario. La sección de automatizaciones muestra reglas (cuando CO₂ supera 1000 ppm, encender ventilación; cuando temperatura supera 26 °C, encender aire acondicionado, etc.) y deja preparado el camino para que, cuando el hardware esté, esas reglas se ejecuten realmente sobre los actuadores físicos.

## Qué valor aporta esto al proyecto de tesina

Este hito demuestra que Eden Air no es solo un proyecto técnico cerrado en sí mismo, sino un producto pensado integralmente. La web ya tiene la calidad de presentación de un producto real, la lógica de negocio (usuario, dispositivos, ambientes, automatizaciones) bien separada y modelada, y la puerta abierta para que la integración con el hardware sea un paso más, no un rediseño. Aporta a la tesina:

- Una experiencia de usuario cuidada, que puede presentarse en vivo en una defensa sin que algo se vea improvisado.
- Una arquitectura de software ordenada en CodeIgniter 4, con controladores, servicios, modelos y vistas separados, lista para escalar.
- Una identidad visual coherente (tipografías, colores, espaciados) que da seriedad al producto.
- Documentación que acompaña al desarrollo y permite que el proyecto sea entendido por terceros.

En síntesis: lo que se entregó en el Hito 2 deja a Eden Air en un punto donde la web puede presentarse como producto, recibir el hardware cuando esté listo, y seguir creciendo con automatizaciones reales sin tener que reescribir todo.
