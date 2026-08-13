<?php
/**
 * ============================================================================
 * DASHBOARD — la pantalla principal del panel.
 * ============================================================================
 * Ruta: /panel · Controlador: PanelController::index
 * Recibe: $panel, con $panel['view'] ya cocinado por PanelService::obtenerVistaPanel()
 *
 * ESTA VISTA NO CALCULA NADA. Valores, tonos, textos y porcentajes llegan
 * listos desde el service. Si un número sale mal, se arregla allá, no acá.
 *
 * ORDEN DE LA PANTALLA (cada dato aparece UNA sola vez):
 *   1. HERO      · el diagnóstico: cómo está el ambiente ahora
 *   2. SENSORES  · los 4 valores medidos, con su rango ideal
 *   3. CONTROL   · actuadores + modo de operación + automatizaciones
 *   4. LECTURAS  · historial reciente
 *
 * ANIMACIONES: no viven acá. Los JS las enganchan por atributos data-*:
 *   data-vivo*      → panel-vivo.js refresca los números sin recargar
 *   .ea-reveal      → dashboard.js los muestra al entrar en pantalla
 *   data-readings*  → dashboard.js despliega el resto del historial
 *   scroll suave    → dashboard-gsap.js (se activa con conScrollSuave)
 */
$panel = (isset($panel) && is_array($panel)) ? $panel : [];
$view  = (isset($panel['view']) && is_array($panel['view'])) ? $panel['view'] : [];

$tono        = (string) ($view['tono'] ?? 'success');
$modoManual  = ! empty($view['modoManual']);
$historial   = (array) ($view['historial'] ?? []);
$filasFijas  = 3;   // cuántas lecturas se ven sin apretar "Ver más"

/* CALIDAD DE AIRE APARTE DEL RESTO
   El service manda cuatro "sensores", pero uno no es un sensor: la calidad de
   aire es un ÍNDICE que se calcula a partir de los otros tres. Mostrarlo como
   una tarjeta más hacía que se leyera como una cuarta medición independiente,
   y encima obligaba a recorrer las cuatro para saber si el ambiente está bien.

   Acá se separa: el índice sube al hero (es la respuesta a "¿cómo está mi
   aire?") y abajo quedan las tres mediciones reales que lo explican. Se
   conservan intactos los atributos data-vivo-*, así que panel-vivo.js lo sigue
   refrescando sin cambios: para el JS es la misma tarjeta, solo se mudó. */
$indiceAire = null;
$medidos    = [];

foreach ((array) ($view['sensores'] ?? []) as $sensor) {
    if (($sensor['icono'] ?? '') === 'air') {
        $indiceAire = $sensor;
        continue;
    }
    $medidos[] = $sensor;
}

/** Etiqueta corta de un tono de sensor (el color ya lo pone la clase CSS). */
$tonoLabel = static fn (string $t): string => match ($t) {
    'danger'  => 'Crítico',
    'warning' => 'Atención',
    'neutral' => 'Sin datos',
    default   => 'Normal',
};

$this->setData([
    'tituloPagina'    => 'EdenAir · Panel del ambiente',
    'descripcion'     => 'Panel EdenAir: monitoreo en tiempo real de temperatura, humedad, CO₂ y calidad del aire, con control de actuadores y automatizaciones del ambiente.',
    'sidebarActivo'   => 'inicio',
    'cantidadEquipos' => count($panel['devices_list'] ?? []),
    'conLoader'       => true,
    'conScrollSuave'  => true,
    // data-epoch-lectura: antigüedad real de la medición que se está dibujando,
    // para que el sello de "actualizado hace…" ya sea correcto al abrir la
    // página y no solo después del primer refresco.
    'attrsApp'        => 'data-url-datos="' . site_url('panel/datos') . '"'
                       . ' data-epoch-lectura="' . esc((string) ($view['ultimaLecturaEpoch'] ?? ''), 'attr') . '"',
    'scripts'         => ['JS/panel-vivo.js'],
    'cabecera'        => [
        // Solo identidad: qué ambiente estoy viendo y con qué equipo. El estado
        // del aire NO va acá, es el titular del hero: repetirlo cansa la lectura.
        'titulo' => (string) ($view['spaceName']  ?? 'Mi ambiente'),
        'bajada' => (string) ($view['spaceLabel'] ?? ''),
        'extra'  => view('partials/selector_dispositivo', ['selector' => [
            'equipos' => (array) ($panel['devices_list'] ?? []),
            'activo'  => (string) ($view['deviceName'] ?? ''),
        ]]),
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- =====================================================================
         1. HERO · el diagnóstico
         Una sola frase que responde "¿cómo está mi ambiente?", más la
         tendencia de temperatura. Los valores medidos NO se repiten acá:
         viven en las tarjetas de sensores, justo abajo.
         ===================================================================== -->
    <section class="ea-hero ea-reveal tone-<?= esc($tono) ?>" id="dashboard" data-vivo-tono>
        <div class="ea-hero-glow" aria-hidden="true"></div>

        <div class="ea-hero-main">
            <div class="ea-hero-top">
                <span class="ea-badge tone-<?= esc($tono) ?> ea-hero-status" data-vivo-tono>
                    <span class="ea-dot"></span>
                    <span data-vivo="estadoLabel"><?= esc((string) ($view['estadoLabel'] ?? '')) ?></span>
                </span>
                <span class="ea-hero-mode ea-mode-tag <?= $modoManual ? 'is-manual' : 'is-auto' ?>">
                    <span class="ea-mode-tag-dot" aria-hidden="true"></span>
                    Modo <?= $modoManual ? 'manual' : 'automático' ?>
                </span>
            </div>

            <p class="ea-hero-eyebrow">Hola, <?= esc((string) ($view['userName'] ?? 'bienvenido')) ?></p>
            <h2 class="ea-serif ea-hero-title" data-vivo="estadoTitulo"><?= esc((string) ($view['estadoTitulo'] ?? '')) ?></h2>
            <p class="ea-hero-diag" data-vivo="estadoDetalle"><?= esc((string) ($view['estadoDetalle'] ?? '')) ?></p>

            <div class="ea-hero-foot">
                <span class="ea-hero-foot-item">
                    <span class="ea-hero-foot-label">Última lectura</span>
                    <span class="ea-mono ea-hero-foot-val" data-vivo="ultimaLectura"><?= esc((string) ($view['ultimaLectura'] ?? '—')) ?></span>
                </span>
                <span class="ea-hero-foot-item">
                    <span class="ea-hero-foot-label">Dispositivo</span>
                    <span class="ea-hero-foot-val"><?= esc((string) ($view['deviceName'] ?? '—')) ?></span>
                </span>
                <span class="ea-hero-foot-item ea-hero-foot-conn">
                    <span class="ea-conn-dot"></span>
                    <span class="ea-hero-foot-label">Identificador</span>
                    <span class="ea-mono ea-hero-foot-val"><?= esc((string) ($view['deviceUid'] ?? '')) ?></span>
                </span>
            </div>
        </div>

        <div class="ea-hero-side">

            <?php /* ÍNDICE DE CALIDAD DE AIRE — el titular numérico.
                     Mantiene data-vivo-sensor="air" y los data-vivo de adentro,
                     así panel-vivo.js lo refresca igual que cuando vivía en la
                     grilla de sensores. */ ?>
            <?php if ($indiceAire !== null): $aTono = (string) ($indiceAire['tono'] ?? 'success'); ?>
                <div class="ea-hero-indice" data-vivo-sensor="air">
                    <span class="ea-hero-indice-label">Calidad de aire</span>

                    <span class="ea-hero-indice-dato">
                        <span class="ea-hero-indice-num" data-vivo="valor"><?= esc((string) ($indiceAire['valor'] ?? '--')) ?></span>
                        <span class="ea-hero-indice-unidad">/100</span>
                        <span class="ea-badge tone-<?= esc($aTono) ?> ea-hero-indice-badge" data-vivo-tono>
                            <span class="ea-dot"></span>
                            <span data-vivo="badge"><?= esc($tonoLabel($aTono)) ?></span>
                        </span>
                    </span>

                    <?php /* El medidor: banda = zona buena, pin = el valor de ahora. */ ?>
                    <span class="ea-gauge ea-hero-indice-gauge" role="img"
                          aria-label="Calidad de aire: <?= esc((string) ($indiceAire['valor'] ?? '--'), 'attr') ?> de 100. <?= esc($tonoLabel($aTono), 'attr') ?>.">
                        <span class="ea-gauge-band" data-vivo="band"
                              style="left: <?= round((float) ($indiceAire['bandLow'] ?? 0), 1) ?>%; width: <?= round(max(0.0, (float) ($indiceAire['bandHigh'] ?? 100) - (float) ($indiceAire['bandLow'] ?? 0)), 1) ?>%;"></span>
                        <span class="ea-gauge-pin tone-<?= esc($aTono) ?>" data-vivo="pin" data-vivo-tono
                              style="left: <?= round(max(0.0, min(100.0, (float) ($indiceAire['pct'] ?? 0))), 1) ?>%;"></span>
                    </span>

                    <?php /* Este texto lo reescribe panel-vivo.js con view.sensores[].rango,
                             así que arranca con el MISMO valor que va a poner el JS. */ ?>
                    <span class="ea-hero-indice-hint" data-vivo="rango"><?= esc((string) ($indiceAire['rango'] ?? '')) ?></span>
                </div>
            <?php endif; ?>

            <div class="ea-hero-trend">
                <?php /* El trazo del mini-gráfico (sparkPath) lo calcula PanelService.
                         panel-vivo.js lo reemplaza cuando llegan lecturas nuevas.
                         Los topes numéricos NO son decoración: sin ellos la curva
                         no dice nada, porque no se sabe entre qué valores se mueve. */ ?>
                <span class="ea-hero-trend-label">
                    Temperatura · últimas <?= count($historial) ?> lecturas
                </span>

                <?php
                $hayEscala = isset($view['trendMin'], $view['trendMax']);
                $altSpark  = 'Tendencia de temperatura de las últimas ' . count($historial) . ' lecturas'
                           . ($hayEscala ? ', entre ' . $view['trendMin'] . ' y ' . $view['trendMax'] . ' grados' : '')
                           . '.';
                ?>
                <svg viewBox="0 0 220 60" class="ea-hero-spark" preserveAspectRatio="none" role="img"
                     aria-label="<?= esc($altSpark, 'attr') ?>">
                    <defs>
                        <linearGradient id="ea-spark-grad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="var(--eden-500)" stop-opacity=".30"/>
                            <stop offset="1" stop-color="var(--eden-500)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?> L 220 60 L 0 60 Z" fill="url(#ea-spark-grad)" data-vivo-spark="relleno"/>
                    <path d="<?= esc((string) ($view['sparkPath'] ?? '')) ?>" fill="none" stroke="var(--eden-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-vivo-spark="linea"/>
                </svg>

                <?php if ($hayEscala): ?>
                    <span class="ea-hero-trend-escala" aria-hidden="true">
                        <span class="ea-mono"><span data-vivo="trendMin"><?= esc((string) $view['trendMin']) ?></span>°</span>
                        <span class="ea-mono"><span data-vivo="trendMax"><?= esc((string) $view['trendMax']) ?></span>°</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         2. SENSORES
         El único lugar donde aparecen los valores medidos. La banda verde del
         medidor es el rango ideal del ambiente; el pin, la lectura actual.
         ===================================================================== -->
    <div class="ea-sec" id="sensores">
        <h2>Lo que mide el equipo</h2>
        <span class="ea-sec-right">Comparado con el rango de <?= esc((string) ($view['spaceName'] ?? 'tu ambiente')) ?></span>
    </div>

    <div class="ea-sensor-grid">
        <?php foreach ($medidos as $sensor):
            $sTono    = (string) ($sensor['tono'] ?? 'success');
            $bandLow  = (float) ($sensor['bandLow'] ?? 0);
            $bandHigh = (float) ($sensor['bandHigh'] ?? 100);
            $bandAnch = max(0.0, $bandHigh - $bandLow);
            $pin      = max(0.0, min(100.0, (float) ($sensor['pct'] ?? 0)));
        ?>
            <article class="ea-sensor-card accent-<?= esc((string) ($sensor['accent'] ?? 'eden')) ?>" data-vivo-sensor="<?= esc((string) ($sensor['icono'] ?? ''), 'attr') ?>">
                <div class="ea-sensor-head">
                    <span class="ea-sensor-icon" aria-hidden="true"><?= icono((string) ($sensor['icono'] ?? 'temp')) ?></span>
                    <span class="ea-sensor-title"><?= esc((string) ($sensor['titulo'] ?? '')) ?></span>
                    <span class="ea-badge tone-<?= esc($sTono) ?> ea-sensor-badge" data-vivo-tono>
                        <span class="ea-dot"></span>
                        <span data-vivo="badge"><?= esc($tonoLabel($sTono)) ?></span>
                    </span>
                </div>

                <div class="ea-sensor-value">
                    <span class="ea-sensor-num" data-vivo="valor"><?= esc((string) ($sensor['valor'] ?? '--')) ?></span>
                    <span class="ea-mono ea-sensor-unit"><?= esc((string) ($sensor['unidad'] ?? '')) ?></span>
                </div>

                <div class="ea-sensor-foot">
                    <?php /* La etiqueta dice el dato, no "hay un gráfico acá": quien
                             usa lector de pantalla necesita el valor y el rango. */ ?>
                    <div class="ea-gauge" role="img"
                         aria-label="<?= esc((string) ($sensor['titulo'] ?? '') . ': ' . ($sensor['valor'] ?? '--') . ' ' . ($sensor['unidad'] ?? '') . '. ' . ($sensor['rango'] ?? '') . '. ' . $tonoLabel($sTono) . '.', 'attr') ?>">
                        <span class="ea-gauge-band" data-vivo="band" style="left: <?= round($bandLow, 1) ?>%; width: <?= round($bandAnch, 1) ?>%;"></span>
                        <span class="ea-gauge-pin tone-<?= esc($sTono) ?>" data-vivo="pin" data-vivo-tono style="left: <?= round($pin, 1) ?>%;"></span>
                    </div>
                    <div class="ea-sensor-hint">
                        <span data-vivo="rango"><?= esc((string) ($sensor['rango'] ?? '')) ?></span>
                        <span class="ea-gauge-legend"><i></i>zona ideal</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- =====================================================================
         3. CONTROL
         Actuadores (con el selector de modo en su cabecera, porque es lo que
         decide si podés tocarlos) + las reglas que los mueven solos.
         ===================================================================== -->
    <div class="ea-sec" id="configuracion">
        <h2>Control</h2>
        <span class="ea-sec-right">Qué está encendido y con qué criterio</span>
    </div>

    <div class="ea-ops-grid">

        <article class="ea-card ea-actuators-card">
            <div class="ea-card-head">
                <h3>Actuadores</h3>
                <span class="ea-mono ea-card-meta">
                    <?= (int) ($view['actuadoresActivos'] ?? 0) ?> de <?= count($view['actuadores'] ?? []) ?> encendidos
                </span>
            </div>

            <!-- FORMULARIO: modo de operación.
                 POST /panel/modo → PanelController::cambiarModo
                 En automático manda el equipo; en manual mandás vos (y recién
                 ahí aparecen los interruptores de cada actuador). -->
            <form action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll
                  class="ea-mode-switch <?= $modoManual ? 'is-manual' : '' ?>" role="group" aria-label="Modo de operación">
                <?= csrf_field() ?>
                <button type="submit" name="mode" value="automatic" class="ea-mode-opt <?= $modoManual ? '' : 'is-active' ?>" <?= $modoManual ? '' : 'aria-current="true"' ?>>
                    <?= icono('modo-auto', 14) ?>
                    Automático
                </button>
                <button type="submit" name="mode" value="manual" class="ea-mode-opt <?= $modoManual ? 'is-active' : '' ?>" <?= $modoManual ? 'aria-current="true"' : '' ?>>
                    <?= icono('modo-manual', 14) ?>
                    Manual
                </button>
            </form>

            <ul class="ea-actuators-list">
                <?php foreach (($view['actuadores'] ?? []) as $act):
                    $encendido = ! empty($act['encendido']);
                    $clave     = (string) ($act['clave'] ?? 'fan');
                    // La clave que manda el service se traduce al ícono del set.
                    $iconoAct  = match ($clave) {
                        'fan'        => 'ventilador',
                        'aromatizer' => 'aromatizador',
                        default      => 'led',
                    };
                ?>
                    <li class="ea-actuator-row">
                        <span class="ea-actuator-icon <?= $encendido ? 'is-on' : '' ?>" aria-hidden="true"><?= icono($iconoAct) ?></span>

                        <div class="ea-actuator-body">
                            <strong class="ea-actuator-name"><?= esc((string) ($act['titulo'] ?? 'Actuador')) ?></strong>
                            <span class="ea-actuator-reason"><?= esc((string) ($act['detalle'] ?? '')) ?></span>
                        </div>

                        <span class="ea-actuator-state">
                            <?php if ($modoManual): ?>
                                <!-- FORMULARIO: encender / apagar un actuador.
                                     POST /panel/actuador → PanelController::cambiarActuador
                                     Deja la orden PENDIENTE: el panel no la da por hecha
                                     hasta que el equipo la ejecuta y la confirma. -->
                                <form action="<?= site_url('panel/actuador') ?>" method="POST" data-preserve-scroll class="ea-actuator-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="actuator" value="<?= esc($clave) ?>">
                                    <input type="hidden" name="value" value="<?= $encendido ? 'off' : 'on' ?>">
                                    <button type="submit" class="ea-actuator-toggle <?= $encendido ? 'is-on' : '' ?>"
                                            aria-label="<?= $encendido ? 'Apagar' : 'Encender' ?> <?= esc((string) ($act['titulo'] ?? '')) ?>">
                                        <span class="ea-actuator-toggle-thumb"></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="ea-badge <?= $encendido ? 'tone-success' : 'tone-neutral' ?> ea-actuator-badge">
                                    <span class="ea-dot"></span><?= $encendido ? 'ON' : 'OFF' ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>

        <article class="ea-card ea-rules-card" id="automatizaciones">
            <div class="ea-card-head">
                <h3>Automatizaciones</h3>
                <span class="ea-mono ea-card-meta">
                    <?= (int) ($view['reglasActivas'] ?? 0) ?> de <?= count($view['reglas'] ?? []) ?> aplicándose
                </span>
            </div>

            <ul class="ea-rules-list">
                <?php foreach (($view['reglas'] ?? []) as $regla): $activa = ! empty($regla['activa']); ?>
                    <li class="ea-rule">
                        <span class="ea-rule-state tone-<?= $activa ? 'success' : 'neutral' ?>" aria-hidden="true"></span>
                        <div class="ea-rule-body">
                            <p class="ea-rule-text">
                                Cuando <strong><?= esc((string) ($regla['cuando'] ?? '')) ?></strong>,
                                <span><?= esc(mb_strtolower((string) ($regla['accion'] ?? ''))) ?>.</span>
                            </p>
                        </div>
                        <span class="ea-badge tone-<?= $activa ? 'success' : 'neutral' ?> ea-rule-badge">
                            <span class="ea-dot"></span><?= $activa ? 'Aplicándose' : 'En espera' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="ea-actuators-note">
                <?= $modoManual
                    ? 'En modo manual las reglas quedan en pausa: los actuadores hacen lo que vos indiques.'
                    : 'En modo automático estas reglas encienden y apagan los actuadores solas.' ?>
            </p>
        </article>

    </div>

    <!-- =====================================================================
         4. LECTURAS
         Historial reciente. Se ven 3 filas; el resto lo despliega dashboard.js
         con el botón "Ver más" (las filas extra llevan la clase is-extra).
         ===================================================================== -->
    <div class="ea-sec" id="historial">
        <h2>Lecturas</h2>
        <span class="ea-sec-right"><span class="ea-mono"><?= count($historial) ?> registros recientes</span></span>
    </div>

    <article class="ea-card ea-readings-card" data-readings>
        <div class="ea-readings-wrap">
            <table class="ea-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Origen</th>
                        <th class="ea-num">Temp.</th>
                        <th class="ea-num">Humedad</th>
                        <th>Calidad</th>
                        <th class="ea-num">CO₂</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <?php /* data-vivo-historial: panel-vivo.js reconstruye estas filas
                         cuando llega una lectura nueva, sin recargar la página.
                         data-filas-fijas le dice cuántas se ven sin apretar "Ver
                         más", para que no tenga el número duplicado. */ ?>
                <tbody data-vivo-historial data-filas-fijas="<?= (int) $filasFijas ?>">
                    <?php if ($historial === []): ?>
                        <tr>
                            <td colspan="7" class="ea-table-empty">
                                <div class="ea-empty">
                                    <strong>Sin lecturas registradas todavía.</strong>
                                    <p>Cuando el dispositivo envíe datos, las lecturas aparecerán acá.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $i => $fila): ?>
                            <tr class="<?= $i >= $filasFijas ? 'is-extra' : '' ?>">
                                <td class="ea-mono ea-table-time"><?= esc((string) ($fila['fecha'] ?? '--')) ?></td>
                                <td>
                                    <span class="ea-table-dev">
                                        <span class="ea-table-dev-dot"></span>
                                        <span class="ea-mono"><?= esc((string) ($fila['origen'] ?? '--')) ?></span>
                                    </span>
                                </td>
                                <td class="ea-num ea-mono"><?= esc((string) ($fila['temperatura'] ?? '--')) ?></td>
                                <td class="ea-num ea-mono"><?= esc((string) ($fila['humedad'] ?? '--')) ?></td>
                                <td><?= esc((string) ($fila['aire'] ?? '--')) ?></td>
                                <td class="ea-num ea-mono"><?= esc((string) ($fila['co2'] ?? '--')) ?></td>
                                <td>
                                    <span class="ea-badge tone-<?= esc((string) ($fila['tono'] ?? 'success')) ?>">
                                        <span class="ea-dot"></span><?= esc($tonoLabel((string) ($fila['tono'] ?? 'success'))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($historial) > $filasFijas): $ocultas = count($historial) - $filasFijas; ?>
            <div class="ea-readings-foot">
                <button type="button" class="ea-kbtn ea-kbtn-primary ea-readings-more" data-readings-toggle
                        data-less="Ver menos" data-more="Ver <?= $ocultas ?> más"
                        aria-expanded="false" aria-controls="historial">
                    <?= icono('chevron', 14, 'ea-readings-more-chevron') ?>
                    <span data-readings-label>Ver <?= $ocultas ?> más</span>
                </button>
            </div>
        <?php endif; ?>
    </article>

<?= $this->endSection() ?>
