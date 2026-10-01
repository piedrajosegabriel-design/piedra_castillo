<?php
/**
 * "Seguí tu vinculación" — la pantalla que ve el CELULAR al volver del portal
 * del equipo, después de haberle configurado el WiFi.
 *
 * Es deliberadamente autónoma: no usa el layout del dashboard ni pide login.
 * El teléfono que llega acá viene de una red que ya no existe (la del equipo),
 * puede no ser el teléfono del dueño, y lo único que trae es el código que le
 * dio la ESP32. Cargar todo el CSS del panel para mostrar un spinner sería
 * lento justo en el peor momento: el usuario recién recuperó la conexión.
 *
 * Variables esperadas:
 *   $sesion string  el código que venía en ?s= (puede estar vacío)
 */
$sesion = (string) ($sesion ?? '');

/*
 * RUTAS SIN HOST, A PROPÓSITO.
 *
 * site_url() arma las URLs con `app.baseURL`, que en desarrollo apunta a
 * `localhost`. Esta pantalla es la única del proyecto que se abre SIEMPRE
 * desde otro dispositivo —el celular, entrando por la IP de red de la PC—, y
 * ahí "localhost" es el propio teléfono: el sondeo se preguntaría a sí mismo
 * y la pantalla quedaría cargando para siempre.
 *
 * Dejando solo la ruta, el navegador la resuelve contra el host por el que
 * entró. Funciona igual desde localhost, desde 192.168.x.x o desde donde sea,
 * sin tener que tocar el .env en cada máquina.
 */
$ruta = static fn (string $destino): string
    => '/' . ltrim((string) parse_url(site_url($destino), PHP_URL_PATH), '/');

$rutaEstado   = $ruta('vinculacion/seguir/estado');
$rutaPanel    = $ruta('panel');
$rutaConectar = $ruta('panel/dispositivos/conectar');

// El <script> tiene el mismo problema: si sale con host `localhost`, el
// celular ni siquiera llega a descargar el JS y la pantalla queda muda.
$rutaJs = '/' . ltrim((string) parse_url(base_url('JS/vinculacion.js'), PHP_URL_PATH), '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light dark">
<title>Eden Air · Conectando tu equipo</title>
<?php /* Estilos: public/CSS/vistas/vinculacion/seguir.css. La marca la
         cambia app/Libraries/Vista.php por el <link>, con la ruta sin host
         (igual que los links y el JS de esta vista). */ ?>
<?= \App\Libraries\Vista::MARCA_ESTILOS ?>
</head>
<body>
<div class="caja"
     data-seguir
     data-sesion="<?= esc($sesion, 'attr') ?>"
     data-url-estado="<?= esc($rutaEstado, 'attr') ?>"
     data-url-panel="<?= esc($rutaPanel, 'attr') ?>">

    <div class="marca">Eden Air</div>

    <!-- ===== Esperando a que el equipo aparezca ===== -->
    <div data-panel="espera">
        <div class="icono espera" aria-hidden="true"></div>
        <h1>Conectando tu Eden Air</h1>
        <p role="status" aria-live="polite" data-mensaje>
            Tu equipo se está conectando a la red. Esto tarda unos segundos.
        </p>
        <div class="nota">
            Podés cerrar esta página: el equipo se conecta igual.
            La dejamos abierta para avisarte cuando termine.
        </div>
    </div>

    <!-- ===== Listo ===== -->
    <div data-panel="listo" hidden>
        <div class="icono listo" aria-hidden="true">&#10003;</div>
        <h1><span class="equipo" data-nombre>Tu Eden Air</span> quedó conectado</h1>
        <p>Ya está midiendo el aire. En unos minutos vas a ver las primeras lecturas.</p>
        <div class="acciones">
            <a class="boton primario" data-ir-panel href="<?= esc($rutaPanel, 'attr') ?>">Ir al panel</a>
        </div>
        <div class="nota">
            Para ver las mediciones vas a tener que iniciar sesión con tu cuenta,
            como siempre.
        </div>
    </div>

    <!-- ===== No se pudo ===== -->
    <div data-panel="problema" hidden>
        <div class="icono problema" aria-hidden="true">!</div>
        <h1 data-problema-titulo>No pudimos seguir esta vinculación</h1>
        <p data-problema-texto>Volvé a la web y apretá «Conectar» para empezar de nuevo.</p>
        <div class="acciones">
            <a class="boton secundario" href="<?= esc($rutaConectar, 'attr') ?>">Volver a intentar</a>
        </div>
    </div>
</div>

<script src="<?= esc($rutaJs, 'attr') ?>"></script>
</body>
</html>
