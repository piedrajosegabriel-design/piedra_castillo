<?php
/* =============================================================================
   VISTA: politicas.php — POLÍTICAS DE PRIVACIDAD (ruta "politicas")
   CSS:  public/CSS/politicas.css (se carga solo, ver app/Libraries/Vista.php)
   JS:   ninguno. Es texto: tiene que leerse igual sin JavaScript.

   POR QUÉ EXISTE
   El pie de la landing y el formulario de registro enlazan acá.

   LA REGLA AL EDITARLA
   Acá va SOLO lo que el código hace de verdad. Cada cosa que se afirma salió
   de mirar el código, y queda anotado de dónde:

     - users (mysql_setup.sql)      → qué datos de la cuenta se guardan
     - UserModel::crearUsuario      → password_hash() con bcrypt
     - AccesoController:281         → el token de recuperación vive 15 min
     - UserModel::hashToken         → del token se guarda el SHA-256, no el token
     - MeasurementModel::POR_EQUIPO → solo las últimas 15 mediciones por equipo
     - LimpiarCuentas               → cuentas sin uso 180 días; ventanas, 7 días
     - CompraService:103            → a Mercado Pago NO se le manda el email
     - BajaCuentaService            → qué se borra y qué se conserva en la baja
     - Config/Session, Config/Cookie→ la sesión y sus cookies

   Si mañana el sistema guarda un dato nuevo, usa un servicio nuevo o cambia
   un plazo, hay que cambiarlo acá también. Una política que promete algo que
   el código no hace es peor que no tenerla.
   ============================================================================= */
$conSesion = (bool) session()->get('user_id');

// Mismas acciones que la landing, para que la barra no cambie de forma.
ob_start(); ?>
    <?php if ($conSesion): ?>
        <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary">Entrar al dashboard</a>
    <?php else: ?>
        <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Iniciar sesión</a>
    <?php endif; ?>
<?php $eaNavActions = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'     => 'Políticas de Privacidad · Eden Air',
        'extraHead' =>
              '<meta name="description" content="Qué datos guarda Eden Air, para qué los usa, cuánto los conserva y cómo pedir que se borren. El equipo mide el ambiente: no graba audio ni toma imágenes.">'
            . '<meta name="robots" content="index, follow">',
    ]) ?>
</head>
<body class="ea-body ea-legal">

    <?= view('partials/navbar', [
        'subtitle'  => 'Monitoreo ambiental',
        'conSesion' => $conSesion,
        'actions'   => $eaNavActions,
        'navLinks'  => [
            ['href' => site_url('/'), 'label' => 'Volver al inicio'],
        ],
    ]) ?>

    <main class="ea-page ea-legal-page">

        <!-- ===== ESTRUCTURA: encabezado ===== -->
        <header class="ea-legal-head">
            <h1>Políticas de Privacidad</h1>
            <p class="ea-legal-lead">
                Eden Air mide el aire de un ambiente y te lo muestra en una web. Para que
                eso funcione hacen falta algunos datos tuyos. Acá está, sin vueltas, qué
                guardamos, para qué, cuánto tiempo y cómo pedir que lo borremos.
            </p>
            <p class="ea-legal-date">Última actualización: 29/10/2026</p>
        </header>

        <!-- ===== ESTRUCTURA: lo que el equipo no hace ===== -->
        <section class="ea-legal-section">
            <h2>1. Lo primero: qué NO hace el equipo</h2>
            <p>
                El dispositivo Eden Air tiene sensores de temperatura, humedad, dióxido de
                carbono y calidad del aire. Nada más. En concreto:
            </p>
            <ul>
                <li><strong>No tiene micrófono.</strong> No graba audio.</li>
                <li><strong>No tiene cámara.</strong> No toma fotos ni video.</li>
                <li><strong>No identifica personas.</strong> No sabe quién hay en la sala,
                    ni cuántos son, ni quiénes.</li>
                <li><strong>No sabe dónde está.</strong> No tiene GPS ni geolocalización.</li>
            </ul>
            <p>
                Lo que mide son <em>propiedades del aire de una habitación</em>. Un número
                de CO₂ no dice quién lo exhaló.
            </p>

            <h3>Si el equipo está en un aula</h3>
            <p>
                Vale exactamente lo mismo. Los datos son <strong>del aula</strong>, no de
                los alumnos: no hay registro de quién entró, ni cuándo, ni de asistencia.
                La medición dice "en esta sala hay 900 ppm de CO₂", y eso no se puede
                atribuir a ninguna persona.
            </p>
            <p>
                La cuenta pertenece a quien instaló el equipo — la institución o el docente
                —, y es la única que ve esas mediciones.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: quién es el responsable ===== -->
        <section class="ea-legal-section">
            <h2>2. Quién es responsable de tus datos</h2>
            <p>
                Eden Air es el responsable de la base de datos. Para cualquier cosa
                relacionada con tus datos personales:
            </p>
            <ul>
                <li>Correo: <a href="mailto:edenair.safe@gmail.com">edenair.safe@gmail.com</a></li>
                <li>Sitio: <a href="https://www.edenair.com.ar" rel="noopener">www.edenair.com.ar</a></li>
            </ul>
        </section>

        <!-- ===== ESTRUCTURA: datos de la cuenta ===== -->
        <section class="ea-legal-section">
            <h2>3. Qué datos guardamos</h2>

            <h3>De tu cuenta</h3>
            <ul>
                <li>Nombre, apellido, nombre de usuario y correo electrónico.</li>
                <li>
                    La contraseña <strong>no se guarda</strong>. Se guarda un hash con el
                    algoritmo <code>bcrypt</code>, del que no se puede volver a la
                    contraseña original. Ni nosotros podemos verla.
                </li>
                <li>La fecha en que creaste la cuenta y la de tu último inicio de sesión.</li>
            </ul>

            <h3>De tus ambientes y equipos</h3>
            <ul>
                <li>El nombre que les pongas y los límites que configures.</li>
                <li>
                    Un identificador del equipo, su clave de acceso a la API y su dirección
                    MAC (el número de fábrica de su placa de red, que el propio equipo
                    informa la primera vez que se conecta).
                </li>
                <li>
                    Las mediciones: temperatura, humedad, CO₂ y un índice de calidad del
                    aire, con la fecha de cada una.
                </li>
                <li>
                    Qué hizo el equipo con cada medición (si encendió el aire o el
                    atomizador) y si cada sensor estaba respondiendo bien.
                </li>
            </ul>

            <h3>Al vincular un equipo nuevo</h3>
            <p>
                Mientras dura la vinculación se guarda la dirección IP del navegador desde
                el que la iniciaste. Sirve para una sola cosa: si hay varias ventanas de
                vinculación abiertas a la vez, saber cuál corresponde a tu equipo. Esa
                ventana <strong>dura 10 minutos</strong> y se borra sola a la semana.
            </p>
            <p class="ea-legal-note">
                Es el único dato de conexión que guardamos de una persona. No registramos
                tu IP al navegar, ni al iniciar sesión.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: cuánto duran ===== -->
        <section class="ea-legal-section">
            <h2>4. Cuánto tiempo los guardamos</h2>
            <p>
                Menos de lo que probablemente imaginás. No armamos un historial largo de
                tu ambiente:
            </p>
            <ul>
                <li>
                    <strong>Mediciones: solo las últimas 15 de cada equipo.</strong> Cada
                    vez que llega una nueva, se borra la más vieja. Como el equipo mide
                    cada 30 segundos, en la base hay alrededor de <strong>7 minutos</strong>
                    de datos. Lo anterior ya no existe.
                </li>
                <li>
                    <strong>Tu cuenta:</strong> mientras la uses. Si pasan 180 días sin que
                    inicies sesión y sin que ninguno de tus equipos reporte, se borra
                    entera, con sus ambientes, equipos y mediciones.
                </li>
                <li>
                    <strong>Ventanas de vinculación:</strong> valen 10 minutos y se eliminan
                    a los 7 días.
                </li>
                <li>
                    <strong>Token de recuperación de contraseña:</strong> vive 15 minutos y
                    se borra apenas lo usás. Además, de ese token guardamos solo un hash
                    SHA-256: aunque alguien leyera la base, no podría usarlo para entrar a
                    tu cuenta.
                </li>
            </ul>
        </section>

        <!-- ===== ESTRUCTURA: para qué ===== -->
        <section class="ea-legal-section">
            <h2>5. Para qué los usamos</h2>
            <ul>
                <li>Para que entres a tu cuenta y veas solo tus ambientes.</li>
                <li>
                    Para que el equipo sepa con qué límites decidir: los números los ponés
                    vos desde el panel y el equipo los aplica.
                </li>
                <li>Para dibujar los gráficos y el estado del dashboard.</li>
                <li>Para mandarte el correo de recuperación, si lo pedís.</li>
            </ul>
            <p>
                Y nada más. <strong>No hacemos perfilado, no mostramos publicidad, no
                vendemos ni cedemos datos a nadie, y no tomamos decisiones automatizadas
                sobre vos.</strong> El sistema decide cuándo prender un ventilador, no
                cosas sobre personas.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: cookies ===== -->
        <section class="ea-legal-section">
            <h2>6. Cookies</h2>
            <p>Usamos dos, las dos imprescindibles para que el sitio funcione:</p>
            <ul>
                <li>
                    <code>ci_session</code> — te mantiene con la sesión iniciada. Dura
                    2 horas. El contenido de la sesión queda en nuestro servidor, no dentro
                    de la cookie.
                </li>
                <li>
                    <code>csrf_cookie_name</code> — evita que otra página pueda enviar
                    formularios en tu nombre. También dura 2 horas.
                </li>
            </ul>
            <p>
                <strong>No hay cookies de analítica, de publicidad ni de seguimiento.</strong>
                No usamos Google Analytics, ni píxeles, ni ninguna herramienta de medición
                de audiencia.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: terceros ===== -->
        <section class="ea-legal-section">
            <h2>7. Servicios de terceros</h2>
            <p>
                Preferimos ser explícitos: aunque no les mandamos datos tuyos a propósito,
                tu navegador se conecta a estos servidores al abrir el sitio, y esa
                conexión les deja ver tu dirección IP.
            </p>
            <ul>
                <li>
                    <strong>Google Fonts</strong> (<code>fonts.googleapis.com</code>,
                    <code>fonts.gstatic.com</code>) — las tipografías del sitio. También se
                    cargan dentro del correo de recuperación.
                </li>
                <li>
                    <strong>Cloudflare CDN</strong> (<code>cdnjs.cloudflare.com</code>) y
                    <strong>unpkg</strong> — las librerías de animación y el modelo 3D de
                    la portada.
                </li>
                <li>
                    <strong>Gmail (Google)</strong> — por ahí sale el correo de recuperación
                    de contraseña. Recibe tu dirección de correo, porque es a donde se
                    manda el mensaje.
                </li>
                <li>
                    <strong>Mercado Pago</strong> — solo si comprás. Ver el punto siguiente.
                </li>
            </ul>
        </section>

        <!-- ===== ESTRUCTURA: pagos ===== -->
        <section class="ea-legal-section">
            <h2>8. Pagos</h2>
            <p>
                El cobro lo procesa íntegramente <strong>Mercado Pago</strong>. Te
                redirigimos a su sitio y volvés cuando terminaste.
            </p>
            <ul>
                <li>
                    <strong>Nunca vemos ni guardamos los datos de tu tarjeta.</strong> No
                    pasan por nuestro servidor en ningún momento.
                </li>
                <li>
                    Lo único que les enviamos es tu <strong>nombre y apellido</strong>, el
                    producto, el precio y un código de referencia nuestro.
                    <strong>Tu correo electrónico no se les manda.</strong>
                </li>
                <li>
                    De vuelta guardamos: el código de referencia, el producto, el monto, la
                    moneda, el estado del pago, los identificadores de Mercado Pago, el
                    medio de pago (por ejemplo "visa") y la fecha de acreditación.
                </li>
            </ul>
            <p>
                Mercado Pago trata esos datos bajo su propia política de privacidad, que no
                controlamos.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: datos públicos ===== -->
        <section class="ea-legal-section">
            <h2>9. Un dato que se muestra en público</h2>
            <p>
                La página de inicio muestra, en vivo, la <strong>última medición registrada
                en el sistema</strong>: temperatura, humedad, CO₂ y calidad del aire. Es una
                demostración de que el equipo funciona.
            </p>
            <p>
                Esa medición puede ser la de cualquier equipo conectado, incluido el tuyo.
                <strong>Va sin ningún identificador</strong>: no se publica de qué equipo
                viene, ni de qué cuenta, ni de qué ambiente. Son cuatro números sueltos y la
                hora. Aun así preferimos decirlo antes que esconderlo.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: derechos ===== -->
        <section class="ea-legal-section">
            <h2>10. Tus derechos</h2>
            <p>
                La <strong>Ley 25.326 de Protección de los Datos Personales</strong> y su
                decreto reglamentario 1558/2001 te dan derecho a:
            </p>
            <ul>
                <li><strong>Acceso:</strong> saber qué datos tuyos tenemos.</li>
                <li><strong>Rectificación:</strong> corregir los que estén mal.</li>
                <li><strong>Supresión:</strong> pedir que los borremos.</li>
                <li><strong>Actualización</strong> de los que hayan quedado viejos.</li>
            </ul>

            <h3>Cómo ejercerlos</h3>
            <p>
                Para <strong>borrar tu cuenta no hace falta pedirnos nada</strong>: entrá a
                <em>Perfil</em> dentro del panel y usá "Borrar mi cuenta". Se eliminan tu
                cuenta, tus ambientes, tus equipos y todas tus mediciones, en el momento.
            </p>
            <p>
                Para cualquier otro pedido — acceso, rectificación, una copia de tus datos —
                escribinos a
                <a href="mailto:edenair.safe@gmail.com">edenair.safe@gmail.com</a> desde la
                dirección de tu cuenta. Te respondemos dentro de los plazos que fija la ley:
                10 días corridos para el acceso y 5 días hábiles para la rectificación o
                supresión.
            </p>

            <h3>Qué se conserva después de la baja</h3>
            <p>
                Si hiciste alguna compra, el comprobante no se borra: quedan el monto, la
                fecha y el número de pago, <strong>sin tu nombre ni ningún dato que te
                identifique</strong>. Es el respaldo de una operación de dinero. Al quedar
                desvinculado de vos, deja de ser un dato personal.
            </p>

            <h3>Autoridad de control</h3>
            <p>
                Si considerás que no tratamos bien tus datos, podés reclamar ante la
                <strong>Agencia de Acceso a la Información Pública (AAIP)</strong>, el
                organismo de control de la Ley 25.326 en Argentina.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: seguridad ===== -->
        <section class="ea-legal-section">
            <h2>11. Seguridad</h2>
            <ul>
                <li>Las contraseñas se guardan hasheadas con bcrypt, nunca en texto plano.</li>
                <li>
                    Cada equipo se autentica con una clave propia: no puede leer ni escribir
                    datos de otro equipo ni de otra cuenta.
                </li>
                <li>
                    Los formularios están protegidos contra CSRF y las consultas a la base
                    usan sentencias preparadas.
                </li>
                <li>
                    Para cambiar tus datos, tu contraseña o borrar tu cuenta hay que volver
                    a ingresar la contraseña actual.
                </li>
            </ul>
            <p class="ea-legal-note">
                Para ser honestos: Eden Air nació como un proyecto académico. Si lo instalás
                en una red propia, serví el sitio detrás de HTTPS y no lo expongas a internet
                sin saber qué estás abriendo. Ningún sistema es infalible, y preferimos
                decírtelo a prometerte seguridad absoluta.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: menores ===== -->
        <section class="ea-legal-section">
            <h2>12. Menores de edad</h2>
            <p>
                Las cuentas son para mayores de 18 años. Si el equipo está en un aula, la
                cuenta la abre la institución o un docente, nunca los alumnos — y, como
                dijimos en el punto 1, las mediciones son del ambiente y no permiten
                identificar a nadie.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: cambios ===== -->
        <section class="ea-legal-section">
            <h2>13. Cambios en esta política</h2>
            <p>
                Si cambiamos algo, actualizamos la fecha del encabezado. Si el cambio afecta
                de verdad a tus datos, te avisamos por correo antes de aplicarlo.
            </p>
        </section>

        <p class="ea-legal-back">
            <a class="ea-button ea-button-secondary" href="<?= site_url('/') ?>">Volver al inicio</a>
        </p>

    </main>

    <?= view('partials/footer') ?>

</body>
</html>
