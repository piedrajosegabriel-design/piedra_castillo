# Guía de funcionamiento de la web Eden Air

Esta guía está pensada para entender la página tal como está hoy, sin entrar en código. Sirve para estudiar, repasar el flujo, presentar el proyecto en clase y saber dónde está cada cosa.

---

## 1. Explicación general de Eden Air

Eden Air es una plataforma web vinculada a un sistema físico de monitoreo ambiental. La idea es que un pequeño dispositivo (con sensores y una ESP32) mida en tiempo real la calidad del aire de un ambiente interior, y que la página web permita visualizar todos esos datos, organizar los espacios y, en el futuro, automatizar acciones (encender un ventilador, un aromatizador, un LED de alerta, etc.).

Aunque el hardware todavía no está conectado por completo, la web ya está pensada para recibirlo. Hoy funciona con datos simulados pero con la misma estructura que tendría con datos reales.

## 2. Qué partes tiene la página

La página tiene dos grandes mundos:

- **Mundo público**: lo que cualquier persona puede ver sin estar registrada (landing, portfolio, login, registro, recuperación de contraseña).
- **Mundo privado (dashboard)**: lo que el usuario ve cuando inicia sesión. Acá vive todo lo importante: bienvenida, mis dispositivos, ambientes, automatizaciones, perfil y compra.

Las dos zonas comparten la misma identidad visual (tipografías, colores, sensación), pero el dashboard tiene un menú lateral que la landing no tiene.

## 3. Cómo funciona la landing

La landing es la primera puerta de entrada. Su trabajo es explicar qué es Eden Air, por qué importa y cómo empezar. Se construye en una sola página larga, dividida en secciones que se leen de arriba hacia abajo. Tiene animaciones sutiles cuando el usuario hace scroll, hover en las tarjetas y video o imagen del producto.

Si el usuario ya tiene cuenta puede ir directamente a iniciar sesión desde la barra de navegación de la landing. Si quiere comprar, hay una sección dedicada que lo explica. Si quiere conocer al equipo, está el acceso al Portfolio.

## 4. Qué secciones tiene la landing

- **Hero principal**: nombre del producto, slogan, frase corta de qué hace y botones de acción (registrarse, conocer más).
- **Qué es Eden Air**: explica el producto en lenguaje simple.
- **Cómo funciona**: muestra el flujo (sensores que miden → la web que muestra → acciones automáticas).
- **Beneficios**: por qué le sirve al usuario (aire más saludable, control desde donde sea, ahorro, etc.).
- **Tecnología interna**: cuenta cómo está hecho por dentro (ESP32, sensores, dashboard web).
- **Compra o reserva**: muestra el plan disponible y permite avanzar.
- **Sustentabilidad y confianza**: aporta credibilidad al producto.
- **Footer**: enlaces a portfolio, redes, contacto.

## 5. Cómo funciona el login y el registro

Las vistas de login y registro están fuera del dashboard. Son páginas tranquilas, con un panel lateral oscuro tipo "panel de marca" y, a la derecha, el formulario.

- **Registro**: se piden nombre, apellido, usuario, email y contraseña. La contraseña se valida con un medidor de fuerza. Al registrarse, el usuario es llevado al dashboard.
- **Login**: se piden usuario y contraseña. Si la combinación es correcta, se entra al dashboard.
- **Recuperación de contraseña**: el usuario indica su email y recibe un enlace de recuperación.

Una vez logueado, la sesión queda activa y el sistema usa filtros internos para que las páginas privadas solo sean visibles para quienes tienen sesión.

## 6. Qué pasa cuando el usuario entra al dashboard

El sistema mira si la cuenta del usuario ya tiene al menos un dispositivo vinculado.

- **Si todavía no tiene ningún dispositivo**: se muestra la **pantalla de bienvenida**. Es una vista cálida y guiada, que explica qué son los dispositivos y los ambientes, y ofrece tres caminos: agregar mi primer dispositivo, probar la demo o ir a comprar.
- **Si ya tiene al menos un dispositivo**: se muestra el **dashboard normal** con el resumen del ambiente.

Esto evita que el usuario nuevo se asuste con un panel lleno de datos sin saber qué hacer.

## 7. Cómo está organizado el dashboard

El dashboard se divide en tres bloques:

- **Barra lateral (sidebar)**: navegación principal. Está siempre presente. Muestra el logo, las secciones, el estado del sistema y el botón de cerrar sesión. En celular se convierte en menú hamburguesa.
- **Encabezado (header)**: muestra el título de la vista en la que estoy, el chip de estado, el selector de dispositivo activo si hay más de uno, el switch de modo claro/oscuro y el avatar del usuario.
- **Contenido**: la información de la vista actual. Cambia según la sección elegida en el menú.

Toda la estructura es responsive: la sidebar se colapsa en pantallas chicas, el contenido se reacomoda y los botones siguen siendo cómodos de tocar.

## 8. Qué hace cada sección del menú

- **Inicio**: el resumen del ambiente, las métricas principales en tiempo real, el estado del sistema, actuadores, automatizaciones y últimas lecturas.
- **Mis dispositivos**: la lista de dispositivos vinculados, su estado, su ambiente y la posibilidad de agregar uno nuevo.
- **Ambientes**: los espacios físicos del usuario, con sus rangos de confort (temperatura, humedad, CO₂) y la posibilidad de editarlos.
- **Automatizaciones**: reglas que decide ejecutar el sistema. Hoy se muestran como ejemplos comprensibles, listas para volverse reales cuando el hardware esté conectado.
- **Perfil**: datos personales (nombre, apellido) y contraseña.
- **Plan / Comprar**: muestra el plan disponible y el botón de compra simulada.

## 9. Qué es "Mis dispositivos"

Es la sección donde el usuario administra los aparatos físicos que tiene asociados a su cuenta. Cada dispositivo aparece como una tarjeta con su nombre, su tipo, su estado, el ambiente al que pertenece y un ID técnico.

Una cuenta puede tener varios dispositivos, lo que permite, por ejemplo, monitorear distintos cuartos de una casa o distintas aulas de una escuela. Si todavía no hay ningún dispositivo, se muestra un estado vacío amigable con un botón grande para agregar el primero.

## 10. Cómo se agrega un dispositivo

Desde "Mis dispositivos" se entra al asistente "Conectá tu Eden Air". El asistente tiene cuatro pasos:

1. **Código**: el usuario ingresa el código de activación. El sistema lo valida mientras se escribe.
2. **Datos**: el usuario le pone un nombre (por ejemplo "Eden Air del dormitorio"), elige el tipo y, si quiere, escribe una nota.
3. **Ambiente**: el usuario elige a qué ambiente pertenece el dispositivo. Puede usar uno existente o crear uno nuevo.
4. **Confirmar**: el sistema muestra un resumen y, al confirmar, el dispositivo queda vinculado.

Después de confirmar, el dispositivo aparece en "Mis dispositivos" y, si se creó un ambiente nuevo, también aparece en "Ambientes". El dashboard ya empieza a mostrarlo.

## 11. Qué es el código de activación

El código de activación es un texto único, parecido a `EDEN-XXXX-XXXX`, que viene con cada dispositivo Eden Air. Sirve para asociar ese aparato físico con una cuenta. Tiene tres características importantes:

- Se puede usar **una sola vez**.
- Lo trae el producto (en la caja, en una etiqueta o en un QR).
- Identifica al dispositivo de forma segura, sin que el usuario tenga que escribir cosas técnicas como una dirección MAC.

Para la demo del proyecto se incluyó un código especial: **EDEN-DEMO-2026**, que crea un dispositivo de prueba para poder mostrar todo el flujo en una presentación.

## 12. Qué es un ambiente

Un ambiente es el lugar físico donde está instalado un dispositivo. Por ejemplo: dormitorio, living, aula, oficina, sala de servidores. Cada ambiente tiene su propia configuración de "lo que se considera cómodo":

- Temperatura mínima y máxima.
- Humedad mínima y máxima.
- Valor máximo aceptable de CO₂.

A partir de esos rangos, Eden Air decide si el ambiente está estable o si requiere atención.

## 13. Cómo se relacionan usuario, dispositivo y ambiente

La lógica es:

- Un **usuario** tiene una cuenta única.
- Un **usuario** puede tener varios **dispositivos**.
- Un **usuario** puede tener varios **ambientes**.
- Cada **dispositivo** pertenece a un **ambiente**.
- Un **ambiente** puede tener uno o más **dispositivos**.

Esta relación es importante porque el ambiente define los rangos de confort y el dispositivo es el que mide. Por eso, antes de medir tiene sentido haber dicho dónde está el dispositivo.

## 14. Cómo funciona la sección de automatizaciones

La sección de automatizaciones muestra las reglas del sistema en lenguaje natural, no como código. Por ejemplo:

- Cuando el CO₂ supera los 1000 ppm, se activa la ventilación.
- Cuando la temperatura supera los 26 °C, se enciende el aire acondicionado.
- Cuando la humedad cae por debajo del 35 %, se sugiere humidificación.
- Cuando la calidad del aire baja del 60 sobre 100, se activa el aromatizador.
- Cuando una lectura entra en rango crítico, se enciende un LED de alerta.

Cada regla tiene un estado visible: activa, en espera, preparada o requiere atención. Hoy estos estados se calculan a partir de las lecturas, y el día que el hardware esté conectado, las acciones (encender, apagar) pasarán al dispositivo real a través de la API.

## 15. Cómo funciona la sección compra o plan

La sección Plan / Comprar muestra una sola tarjeta principal con:

- El nombre del plan ("Plan Inicial").
- El precio (US$ 5, pago único, en versión demo).
- Los beneficios incluidos: acceso al dashboard, monitoreo de temperatura, humedad, CO₂ y calidad de aire, historial de mediciones, recomendaciones automáticas, modo claro y oscuro, acceso desde varios dispositivos.
- Un botón claro de "Comprar plan" que activa una confirmación simulada.

La interfaz deja explícito que la compra todavía no tiene integración de pago real, pero se ve como una sección de producto profesional. Es responsive y cabe en pantalla sin obligar a hacer scroll.

## 16. Cómo funciona la sección perfil

La sección Perfil reemplaza al viejo "Editar datos". Se divide en dos tarjetas:

- **Datos personales**: nombre y apellido. Si la cuenta tiene email cargado, se muestra como dato no editable. Cualquier cambio pide la contraseña actual del usuario para confirmar.
- **Cambiar contraseña**: contraseña actual, nueva contraseña y confirmación. Tiene una recomendación breve y al guardar pide confirmación.

Es una vista calma, con buena jerarquía, sin campos innecesarios.

## 17. Cómo funciona el modo claro y oscuro

En el encabezado del dashboard y en la barra de la landing está el botón de cambio de tema, representado como un switch entre sol y luna. Al hacer clic:

- La página cambia inmediatamente entre claro y oscuro.
- La preferencia queda guardada en el navegador, por lo que la próxima vez que el usuario entre, lo recibe en su modo favorito.
- El scroll mantiene su posición exacta. Antes saltaba; ahora no.
- No hay recarga de página ni cambio de dirección.
- Si el usuario tiene otra pestaña abierta de Eden Air, también se sincroniza.

Ambos modos están diseñados con cuidado: el modo claro es cómodo y editorial; el modo oscuro es elegante y tecnológico, sin perder contraste.

## 18. Qué partes están simuladas

Mientras el hardware no esté conectado al 100 %, hay tres tipos de simulación claramente identificadas:

- **Compra**: el botón funciona pero no hay pasarela de pago real. Se muestra un cartel "compra simulada".
- **Dispositivo demo**: con el código EDEN-DEMO-2026 se puede crear un dispositivo ficticio que sirve para recorrer el dashboard como si tuviéramos uno real.
- **Datos de ejemplo en el dashboard**: cuando una cuenta nueva todavía no recibió mediciones reales, el dashboard muestra valores de ejemplo claramente etiquetados como "Datos de ejemplo".

## 19. Qué partes están preparadas para hardware real

- **API REST de dispositivos**: hay tres endpoints listos (`measurements`, `commands/pending`, `commands/{id}/executed`) que esperan que la ESP32 envíe lecturas y reciba órdenes.
- **Token de dispositivo**: cada dispositivo tiene un token único, que es la forma segura en que se autentica al enviar datos.
- **Modelo de datos**: usuarios, dispositivos, ambientes, mediciones y comandos están separados en tablas y servicios bien definidos, listos para recibir información real sin tener que rediseñar nada.
- **Vistas**: tanto el resumen del ambiente como las tablas de lecturas, los actuadores y las automatizaciones ya están preparados para conectarse a datos reales.

## 20. Qué faltaría hacer cuando se conecte la ESP32

- Que la ESP32 use su token para hacer una llamada periódica al endpoint de mediciones, enviando los valores de los sensores.
- Que la ESP32 consulte el endpoint de comandos pendientes y aplique las órdenes recibidas (encender ventilador, apagar LED, etc.).
- Que la ESP32 confirme la ejecución de los comandos al servidor.
- Algunos ajustes finos visuales para reaccionar mejor cuando los datos cambian en vivo (por ejemplo, animar transiciones de valores).
- Cuando ya esté en producción real, sumar pasarela de pago y avisos por correo.

## 21. Dónde se encuentra cada parte dentro de la web

- **Landing (`/`)**: el archivo `inicio.php`, con sus estilos en `inicio.css`.
- **Portfolio (`/portfolio`)**: vista `portfolio.php`.
- **Login (`/login`)**: vista `login.php`.
- **Registro (`/registro`)**: vista `registro.php`.
- **Recuperación de contraseña**: `recuperar.php` y `restablecer_password.php`.
- **Bienvenida del dashboard**: `panel/bienvenida.php`.
- **Inicio del dashboard (`/panel`)**: `panel.php`.
- **Mis dispositivos (`/panel/dispositivos`)**: `dispositivos/index.php`.
- **Agregar dispositivo (`/panel/dispositivos/agregar`)**: `dispositivos/agregar.php` (el asistente).
- **Ambientes (`/panel/ambientes`)**: `ambientes/index.php`.
- **Editar ambiente (`/panel/ambientes/{id}/editar`)**: `ambientes/editar.php`.
- **Perfil (`/panel/perfil`)**: `perfil_usuario.php`.
- **Compra (`/panel/compra`)**: `compra_mercadopago.php`.
- **Sidebar única reusable**: `partials/dashboard_sidebar.php`.
- **Switch de tema**: `partials/theme_toggle.php` + lógica en `JS/tema.js`.
- **Estilos del dashboard**: `CSS/dashboard.css`.
- **Estilos comunes de marca**: `CSS/eden-brand.css`.

## 22. Hasta dónde llega actualmente el sistema

Hoy el sistema cubre todo el flujo del usuario "del lado de la web":

- Llegar a la página.
- Entender el producto.
- Registrarse, iniciar sesión o recuperar la contraseña.
- Ser recibido con una pantalla de bienvenida si no tiene dispositivos.
- Agregar uno o varios dispositivos con un código de activación.
- Crear o reutilizar ambientes y asignar dispositivos a ellos.
- Ver el resumen del ambiente con las métricas principales.
- Visualizar lecturas históricas, actuadores y automatizaciones.
- Cambiar entre modo automático y manual.
- Editar su perfil y cambiar su contraseña.
- Ver el plan disponible y comprar (en forma simulada).
- Cerrar sesión.

El camino del lado del hardware (que la ESP32 envíe datos reales y reciba comandos) está preparado pero falta cablearlo cuando el hardware esté listo.

## 23. Cómo explicarlo oralmente en una presentación

Una buena forma de explicarlo en clase es seguir el camino del usuario:

1. "Eden Air es una plataforma que ayuda a mantener ambientes interiores saludables. Hay un dispositivo físico con sensores y una página web. Nosotros trabajamos la página web y la dejamos lista para conectar el hardware."
2. Mostrar la landing: "Acá un usuario nuevo entiende qué es el producto, ve los beneficios y puede registrarse."
3. Registrarse o usar una cuenta de prueba.
4. Entrar al dashboard y mostrar la pantalla de bienvenida: "Como esta cuenta no tiene dispositivos, le mostramos qué puede hacer."
5. Tocar "Agregar mi primer dispositivo" y recorrer el asistente paso a paso: "Acá explicamos qué es el código de activación, le ponemos nombre, elegimos ambiente y confirmamos. Para la demo usamos EDEN-DEMO-2026."
6. Volver al dashboard: "Ahora sí, este es el panel real: estado general, métricas, sistema, actuadores, automatizaciones y lecturas."
7. Mostrar el menú lateral: "Desde acá voy a Mis dispositivos, Ambientes, Automatizaciones, Perfil o Plan."
8. Mostrar el modo claro/oscuro: "Cambio el tema y el scroll no se mueve. Mi preferencia queda guardada."
9. Cerrar diciendo: "Lo que falta para tener el sistema completo es conectar la ESP32 a los endpoints que ya están preparados. La web no necesita rediseño para eso."

Con ese recorrido se cubre todo el alcance del proyecto y se transmite claridad.
