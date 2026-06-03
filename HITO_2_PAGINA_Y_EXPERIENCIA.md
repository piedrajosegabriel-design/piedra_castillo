# HITO 2 — La página, la experiencia y las funcionalidades

Documento centrado en **lo que el usuario ve y hace**: la identidad visual, la
landing, el dashboard, la compra del producto y los elementos diferenciales del
proyecto (objeto 3D, secuencia de video por scroll y video inferior). Está
pensado para explicarle la página a otra persona y para que un usuario entienda
cómo usarla.

---

## 1. Idea de la experiencia

EdenAir tiene que transmitir **aire limpio, tecnología y calma**: una interfaz
**limpia, premium y ambiental**, no recargada. La página combina dos mundos:

- **La landing** (página pública): explica qué es el producto, cómo funciona y
  por qué comprarlo, con piezas visuales fuertes (3D y video) que lo hacen único.
- **El dashboard** (área privada): muestra el ambiente en tiempo real y permite
  controlarlo, de forma clara y ordenada.

---

## 2. Identidad visual (la nueva branding aplicada)

| Elemento | Definición |
|---|---|
| **Logo** | Marca oficial **"Corriente"**: un ícono squircle (cuadrado redondeado) con fondo verde en degradé y un glifo blanco de "corriente de aire" más un punto cítrico. Es el mismo símbolo en claro y oscuro porque trae su propio fondo, así siempre se ve premium. Está en navbar, footer, login, sidebar, loader y favicon. |
| **Paleta** | Fondos cálidos y claros (`#F6F4EC`, `#EEF7F4`); verde como color de marca (`#2F6B4F`) y verde profundo (`#143326`) solo para contraste; **aqua** (`#8FD6C8`), **menta** y **cítrico** (`#C9D870`) para los datos vivos. |
| **Tipografías** | **DM Serif Display** para títulos, **DM Sans** para textos y **DM Mono** para datos y etiquetas. |
| **Modo claro** | Domina el fondo claro y aireado; el verde aparece como acento. |
| **Modo oscuro** | Verde profundo (`#0E1F17`) con tarjetas elevadas (`#1A2C23`), **bordes con matiz aqua** (no gris) y brillos sutiles. Diseñado para verse limpio y elegante, nunca embarrado. |
| **Pantalla de carga (loader)** | Sobria y profesional: el logo Corriente, anillos suaves que giran y una barra de progreso. Sin elementos infantiles ni recargados. |

> **Regla de la marca:** el verde es la identidad, pero el **aqua y el cítrico
> son "el aire"**: se reservan para los datos vivos y por eso destacan.

---

## 3. La landing page (página de inicio)

La landing está pensada como un recorrido: primero impacta, después explica, al
final invita a comprar. Estas son sus secciones, **en orden**, y para qué sirve
cada una:

### 3.1 Navbar
Barra superior fija con el logo Corriente, los accesos a las secciones (Qué es,
Beneficios, Tecnología, Funcionamiento, Comprar), el menú de Portfolio, el
**cambio de tema** (claro/oscuro) y los botones de **Iniciar sesión** y
**Comprar**. En móvil se convierte en un menú desplegable.

### 3.2 Hero con OBJETO 3D *(elemento diferencial — protegido)*
La primera pantalla. A la izquierda, el mensaje principal ("Respirá mejor, viví
más cómodo.") con su descripción y botones. A la derecha, un **objeto 3D
interactivo**: el "núcleo" EdenAir renderizado en tiempo real, rodeado de
**paneles flotantes (HUD)** con datos (temperatura, humedad, CO₂, calidad,
ventilador, humidificación). Transmite de entrada que esto es un producto
tecnológico real.

### 3.3 Secuencia de VIDEO por scroll *(elemento diferencial — protegido)*
Una sección a pantalla completa donde, **al hacer scroll, el video avanza**
cuadro por cuadro (scroll sequence) y van apareciendo textos que cuentan la
historia: el ambiente se detecta, se analiza en tiempo real, se regula
automáticamente y queda equilibrado. Es el momento "cine" de la página: el
usuario controla el relato con el scroll.

### 3.4 Núcleo y módulos (Beneficios)
Una animación del núcleo con sus módulos en órbita, acompañada de los beneficios
clave: sensado continuo, estados claros, control de actuadores, historial,
perfiles de ambiente y API preparada.

### 3.5 Tecnología interna con VIDEO inferior *(elemento diferencial — protegido)*
Sección que muestra el **video de la "vista explosionada"** del dispositivo (sus
partes por dentro), con tarjetas que explican qué hay adentro: sensores, control
inteligente, automatización y diseño eficiente.

### 3.6 Funcionamiento ("Sensa. Decide. Actúa.")
Explica el ciclo del sistema en pasos simples: **sensa** el ambiente, **decide**
según los rangos y **actúa** sobre los actuadores.

### 3.7 Compra del producto
Presenta **EdenAir Core** como **compra de producto** (no como un plan ni una
suscripción): el dispositivo + el acceso al dashboard + la configuración de
ambientes, a un precio de **$89.999 ARS (pago único)**. Incluye la lista de todo
lo que viene con el producto y un botón de compra. *(La compra está simulada para
la presentación: no hay cobro real integrado todavía.)*

### 3.8 Cierre y footer
Un llamado final a la acción y el pie con el logo, enlaces y la firma de la marca.

> **Importante:** el objeto 3D, la secuencia de video por scroll y el video
> inferior son las piezas que hacen **original** al proyecto. El rediseño se
> aplicó **alrededor** de ellas (fondos, textos, botones, espaciados, branding),
> sin reemplazarlas ni quitarlas.

---

## 4. Cómo interactúa el usuario con la landing

- **Scrollea** para recorrer la historia: el video de la secuencia avanza con el
  scroll y los textos aparecen en el momento justo.
- **Mueve/observa el objeto 3D** del hero, que reacciona y muestra datos.
- **Navega** por las secciones desde el menú (con scroll suave).
- **Cambia entre modo claro y oscuro** desde el navbar.
- **Va a comprar** o **inicia sesión** desde los botones destacados.

---

## 5. El dashboard (área privada)

Cuando el usuario inicia sesión entra al **dashboard**, que muestra el estado del
ambiente y permite controlarlo. Antes de cargar aparece el **loader** con el logo.

### 5.1 Estructura del dashboard
| Zona | Qué es |
|---|---|
| **Sidebar** (verde profundo) | Navegación: Inicio, Mis dispositivos, Ambientes, Automatizaciones, Perfil y **Comprar EdenAir**. Ancla la marca y da contraste premium. |
| **Header** | Título "Resumen", selector de dispositivo, estado general, cambio de tema y datos del usuario. |
| **Panel de estado (KPI)** | Tarjeta principal con **gradiente verde** que cambia de color según el estado general (verde = todo en rango, terracota = advertencia, rojo = crítico). Muestra el diagnóstico, las 4 métricas principales y una mini-tendencia de 24 h. |
| **Sensores** | Una tarjeta por variable con su valor, su rango ideal y su estado. |
| **Estado del sistema** | Resumen de conexión, ESP32, actuadores activos y alertas, más el **selector de modo** automático/manual. |
| **Actuadores** | Lista de actuadores (ventilación, humidificación/aroma, LED) con interruptores (en modo manual). |
| **Automatizaciones** | Las reglas activas ("cuando pasa X, hacer Y"). |
| **Lecturas** | Tabla con el historial de mediciones recientes. |

### 5.2 Qué muestran los datos y qué significan

| Dato (cómo se llama) | Qué significa | Para qué sirve |
|---|---|---|
| **Temperatura** (°C) | Temperatura del ambiente. | Confort térmico. |
| **Humedad** (%) | Humedad relativa. | Confort y salud (ni muy seco ni muy húmedo). |
| **CO₂** (ppm) | Dióxido de carbono en partes por millón. | Indica si el aire está "viciado"; CO₂ alto = falta ventilación. |
| **Calidad de aire** (índice 0–100 + etiqueta) | Resumen general de la calidad del aire. | Lectura rápida del estado del ambiente. |
| **Índice de aire / estado general** | Combinación de las variables comparadas con los rangos del ambiente. | Decir de un vistazo si el ambiente está **normal, en advertencia o crítico**. |
| **Tendencia 24 h** | Mini-gráfico de cómo evolucionó el ambiente. | Ver si está mejorando o empeorando. |
| **Modo** (automático/manual) | Quién maneja los actuadores. | Automático: el sistema decide; Manual: decide el usuario. |
| **Actuadores** | Ventilación, humidificación/aroma y LED de alerta, con su estado on/off. | Lo que el sistema (o el usuario) enciende para corregir el ambiente. |
| **Historial / Lecturas** | Mediciones anteriores con fecha, dispositivo y valores. | Revisar el pasado y justificar decisiones. |

> Todos estos datos salen de la base de datos (tablas `measurements`,
> `device_states` y `spaces`) y los calcula el backend antes de mostrarlos
> (ver HITO 1).

### 5.3 Estados visuales (normal / advertencia / crítico)
El sistema usa **siempre los mismos colores** para el estado: **verde** (normal),
**terracota suave** (advertencia, cerca del límite) y **terracota intenso/rojo**
(crítico, fuera de rango). El panel de estado principal incluso **cambia su
gradiente** según el estado general, para que se entienda sin leer.

### 5.4 Qué acciones puede hacer el usuario
- **Cambiar de dispositivo** (si tiene más de uno).
- **Cambiar el modo** automático/manual.
- **Encender/apagar actuadores** (en modo manual).
- **Cargar una medición manual**.
- **Ver el historial** y expandirlo.
- **Editar su perfil** y su contraseña.
- **Editar el ambiente** (sus rangos ideales).
- **Comprar el producto**.
- **Cambiar entre modo claro y oscuro**.
- **Cerrar sesión**.

---

## 6. La compra del producto

La sección de compra está pensada como **compra de un producto**, no como un plan:

- Se llama **"Comprar EdenAir"** y presenta **EdenAir Core**.
- Precio: **$89.999 ARS · pago único**.
- Muestra qué incluye el producto (el dispositivo ESP32, el acceso al dashboard,
  el monitoreo de las 4 variables, la automatización, la configuración de
  ambientes y el acceso multi-dispositivo).
- Está integrada en el mismo marco visual del dashboard (sidebar + header) y se
  ve completa **sin scroll incómodo**: el producto, el precio y el botón están a
  la vista.
- La compra es **simulada** para la presentación (sin cobro real todavía).

---

## 7. Cómo se usa el sistema (mirada del usuario)

1. Entra a la **landing**, recorre la historia (3D + video por scroll + video
   interno) y entiende qué es EdenAir.
2. **Crea una cuenta** o **inicia sesión**.
3. (La primera vez) **vincula un dispositivo** o **prueba la demo**, y configura
   su **ambiente** (los rangos ideales según el tipo de espacio).
4. Entra al **dashboard** y ve el estado de su ambiente en tiempo real.
5. Deja el sistema en **automático** para que regule solo, o pasa a **manual**
   para controlar los actuadores él mismo.
6. Revisa el **historial** y las **automatizaciones**.
7. Si quiere, **compra el producto** desde la sección de compra.

---

## 8. Detalles de experiencia (calidad de la interfaz)

- **Responsive:** funciona en escritorio, tablet y móvil; las grillas se apilan y
  el navbar pasa a menú desplegable.
- **Modo claro y oscuro** diseñados juntos, con buen contraste en ambos.
- **Animaciones sutiles:** aparición de secciones al hacer scroll, el contador del
  índice de aire, los anillos del loader. Nada exagerado.
- **Accesibilidad:** textos legibles, foco visible, etiquetas en los controles y
  respeto por la preferencia de "movimiento reducido" del sistema.
- **Rendimiento:** los videos se cargan/optimizan según se necesitan y el objeto
  3D tiene un estado de carga mientras inicializa.

---

### Resumen en una frase
EdenAir se ve **limpio, ambiental y premium**, conserva sus piezas
diferenciales (objeto 3D, secuencia de video por scroll y video interno) y
presenta el monitoreo del ambiente y la compra del producto de forma clara,
ordenada y fácil de usar.
