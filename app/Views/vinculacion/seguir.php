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
<style>
 *{box-sizing:border-box}
 :root{
   --fondo:#f6f4ec; --tinta:#14231b; --suave:#6e7d73; --verde:#2f6b4f;
   --caja:#fff; --linea:#e4e7dc; --ok-fondo:#dff0e4; --aviso:#eef1e8;
 }
 @media (prefers-color-scheme: dark){
   :root{
     --fondo:#101613; --tinta:#e8efe9; --suave:#93a29a; --verde:#5aa47c;
     --caja:#18211d; --linea:#26322c; --ok-fondo:#1b3527; --aviso:#1a231e;
   }
 }
 body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:var(--fondo);
      color:var(--tinta);display:flex;align-items:center;justify-content:center;
      min-height:100vh;padding:24px 20px}
 .caja{width:100%;max-width:400px;text-align:center}
 .marca{font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
        color:var(--verde);margin-bottom:18px}
 .icono{width:66px;height:66px;margin:0 auto 16px;border-radius:50%;
        display:flex;align-items:center;justify-content:center;font-size:30px}
 .icono.espera{border:3px solid var(--linea);border-top-color:var(--verde);
        animation:girar 1s linear infinite}
 @keyframes girar{to{transform:rotate(360deg)}}
 @media (prefers-reduced-motion: reduce){ .icono.espera{animation-duration:3s} }
 .icono.listo{background:var(--ok-fondo);color:var(--verde)}
 .icono.problema{background:var(--aviso);color:var(--suave)}
 h1{font-size:22px;line-height:1.3;margin:0 0 10px}
 p{color:var(--suave);font-size:14.5px;line-height:1.55;margin:0 0 8px}
 .equipo{font-weight:700;color:var(--tinta)}
 .acciones{margin-top:24px;display:grid;gap:10px}
 .boton{display:block;padding:14px;border-radius:12px;font-size:15.5px;
        font-weight:700;text-decoration:none;border:0}
 .boton.primario{background:var(--verde);color:#fff}
 .boton.secundario{background:transparent;color:var(--suave);border:1px solid var(--linea)}
 .nota{margin-top:22px;padding:13px 15px;border-radius:12px;background:var(--aviso);
       font-size:13px;line-height:1.5;color:var(--suave);text-align:left}
 [hidden]{display:none !important}
</style>
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
