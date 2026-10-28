<?php
/* =============================================================================
   VISTA: politicas.php — POLÍTICAS DE PRIVACIDAD (ruta "politicas")
   CSS:  public/CSS/politicas.css (se carga solo, ver app/Libraries/Vista.php)
   JS:   ninguno. La página es texto: tiene que leerse igual sin JavaScript.

   POR QUÉ EXISTE
   El pie (partials/footer.php) enlaza acá desde la landing. Antes el enlace
   apuntaba a una ruta que no existía y daba 404.

   QUÉ CUENTA
   Solo lo que el sistema REALMENTE guarda, mirando el esquema de la base
   (mysql_setup.sql) y el comando de limpieza (app/Commands/LimpiarCuentas.php).
   Si mañana se guarda un dato nuevo, hay que agregarlo acá también.
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
              '<meta name="description" content="Qué datos guarda Eden Air, para qué los usa, cuánto los conserva y cómo pedir que se borren.">'
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
                Eden Air es un proyecto de tesina: un equipo con ESP32 que mide el aire de
                un ambiente y una web para verlo y controlarlo. Para que eso funcione hace
                falta guardar algunos datos. Acá está, sin vueltas, qué se guarda y para qué.
            </p>
            <p class="ea-legal-date">Última actualización: <?= date('d/m/Y') ?></p>
        </header>

        <!-- ===== ESTRUCTURA: qué se guarda ===== -->
        <section class="ea-legal-section">
            <h2>1. Qué datos se guardan</h2>

            <h3>De tu cuenta</h3>
            <ul>
                <li>Nombre, apellido, usuario y correo electrónico.</li>
                <li>
                    La contraseña <strong>no</strong> se guarda. Se guarda un hash
                    (<code>bcrypt</code>), que no permite recuperar la original.
                </li>
                <li>
                    La fecha en que creaste la cuenta y la de tu último inicio de sesión
                    (sirve para detectar cuentas abandonadas, ver el punto 4).
                </li>
            </ul>

            <h3>De tus ambientes y equipos</h3>
            <ul>
                <li>El nombre que les pongas y los límites que configures.</li>
                <li>
                    Las mediciones del equipo: temperatura, humedad, CO₂ y un índice de
                    calidad del aire, con la fecha de cada una.
                </li>
                <li>
                    Qué hizo el equipo (si encendió el aire o el atomizador) y si cada
                    sensor estaba respondiendo.
                </li>
                <li>El identificador del equipo y la última vez que reportó.</li>
            </ul>

            <h3>De las compras</h3>
            <ul>
                <li>
                    El cobro lo procesa <strong>Mercado Pago</strong>. Eden Air
                    <strong>nunca</strong> ve ni guarda los datos de tu tarjeta.
                </li>
                <li>
                    De cada compra se guarda el identificador del pago, el estado y el
                    monto, para poder mostrarte tu historial.
                </li>
            </ul>

            <p class="ea-legal-note">
                No hay publicidad, no hay cookies de seguimiento y los datos no se venden
                ni se comparten con terceros. La única cookie es la de tu sesión, que
                existe para mantenerte logueado.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: para qué se usan ===== -->
        <section class="ea-legal-section">
            <h2>2. Para qué se usan</h2>
            <ul>
                <li>Para que puedas entrar a tu cuenta y ver solo tus ambientes.</li>
                <li>
                    Para que el equipo sepa con qué límites decidir: los números los
                    configurás vos desde el panel y el equipo los aplica.
                </li>
                <li>Para dibujar los gráficos y el historial del dashboard.</li>
                <li>Para avisarte por correo si pedís recuperar tu contraseña.</li>
            </ul>
            <p>
                Las mediciones describen un <em>ambiente</em> (una habitación), no a una
                persona: no se usan para perfilar a nadie.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: quién puede verlos ===== -->
        <section class="ea-legal-section">
            <h2>3. Quién puede verlos</h2>
            <ul>
                <li>Vos, desde tu cuenta.</li>
                <li>
                    Quien administre el servidor donde esté instalado Eden Air (en una
                    instalación casera, vos mismo).
                </li>
                <li>
                    Mercado Pago, únicamente para procesar un pago que vos hayas iniciado.
                </li>
            </ul>
        </section>

        <!-- ===== ESTRUCTURA: cuánto se conservan ===== -->
        <section class="ea-legal-section">
            <h2>4. Cuánto tiempo se conservan</h2>
            <ul>
                <li>
                    Mientras tengas la cuenta activa. Si no iniciás sesión y ninguno de tus
                    equipos reporta durante <strong>180 días</strong>, la cuenta se
                    considera sin uso y se borra junto con sus ambientes, equipos y
                    mediciones.
                </li>
                <li>
                    Para no acumular datos de más, de cada equipo se conservan solo las
                    mediciones recientes; las más viejas se van descartando.
                </li>
                <li>
                    Los registros de pagos no se borran automáticamente: una cuenta que
                    alguna vez inició una compra queda excluida de la limpieza.
                </li>
            </ul>
        </section>

        <!-- ===== ESTRUCTURA: derechos ===== -->
        <section class="ea-legal-section">
            <h2>5. Tus derechos</h2>
            <p>
                Podés pedir ver, corregir o borrar tus datos, y también llevarte una copia.
                Para hacerlo, escribinos desde la dirección de correo de tu cuenta.
            </p>
            <p>
                En Argentina esto está amparado por la <strong>Ley 25.326</strong> de
                Protección de los Datos Personales. La autoridad de control es la Agencia
                de Acceso a la Información Pública, ante la que podés reclamar si considerás
                que tus datos no fueron tratados correctamente.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: seguridad ===== -->
        <section class="ea-legal-section">
            <h2>6. Seguridad</h2>
            <ul>
                <li>Las contraseñas se guardan hasheadas, nunca en texto plano.</li>
                <li>
                    Cada equipo se autentica con un token propio: no puede leer ni escribir
                    datos de otro.
                </li>
                <li>
                    Los formularios están protegidos contra CSRF y las consultas a la base
                    usan sentencias preparadas.
                </li>
            </ul>
            <p class="ea-legal-note">
                Aun así, esto es un proyecto académico y no una plataforma comercial
                auditada. Si lo instalás en tu casa, servilo detrás de HTTPS y no lo
                expongas a internet sin entender qué estás abriendo.
            </p>
        </section>

        <!-- ===== ESTRUCTURA: cambios y contacto ===== -->
        <section class="ea-legal-section">
            <h2>7. Cambios y contacto</h2>
            <p>
                Si esta política cambia, se actualiza la fecha del encabezado. Por cualquier
                duda sobre tus datos, escribinos desde el correo de tu cuenta.
            </p>
        </section>

        <p class="ea-legal-back">
            <a class="ea-button ea-button-secondary" href="<?= site_url('/') ?>">Volver al inicio</a>
        </p>

    </main>

    <?= view('partials/footer') ?>

</body>
</html>
