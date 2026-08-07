<?php
/**
 * CONECTAR UN EDEN AIR — la pantalla del QR.
 * Ruta: /panel/dispositivos/conectar · Controlador: DispositivosController::conectar
 * Recibe: $ssid (WiFi de configuración del equipo), $minutos (dura la ventana)
 *
 * No hay pasos ni código de activación: el usuario aprieta "Conectar", el
 * servidor abre una ventana de vinculación y devuelve el QR dibujado en el
 * momento. La página lo muestra y sondea hasta que el equipo aparece.
 *
 * ESTRUCTURA: cuatro paneles, uno visible por vez. Los tres últimos arrancan
 * con `hidden` y los va destapando public/JS/conectar.js según el estado:
 *   reposo → vivo → ok | fin
 *
 * Endpoints que usa conectar.js (leídos de los data-url-* de la sección):
 *   POST panel/dispositivos/conectar          → abre la ventana, devuelve el QR
 *   GET  panel/dispositivos/conectar/estado   → sondeo
 *   POST panel/dispositivos/conectar/cancelar → cierra la ventana
 */
$ssid    = $ssid    ?? 'EdenAir-Setup';
$minutos = $minutos ?? 10;

$this->setData([
    'tituloPagina'  => 'Eden Air · Conectá tu dispositivo',
    'sidebarActivo' => 'dispositivos',
    'scripts'       => ['JS/conectar.js'],
    'cabecera'      => [
        'titulo' => 'Conectá tu Eden Air',
        'bajada' => 'Escaneá un QR y listo: sin códigos ni configuración manual',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <a href="<?= site_url('panel/dispositivos') ?>" class="ea-back-link">← Volver a Mis dispositivos</a>

    <section class="ea-pair" data-pair
             data-url-iniciar="<?= site_url('panel/dispositivos/conectar') ?>"
             data-url-estado="<?= site_url('panel/dispositivos/conectar/estado') ?>"
             data-url-cancelar="<?= site_url('panel/dispositivos/conectar/cancelar') ?>"
             data-url-listado="<?= site_url('panel/dispositivos') ?>"
             data-csrf-name="<?= esc(csrf_token(), 'attr') ?>"
             data-csrf-hash="<?= esc(csrf_hash(), 'attr') ?>">

        <div class="ea-pair-card">

            <!-- ===== PANEL 1 · Reposo: explicación + el botón ===== -->
            <div class="ea-pair-panel" data-pair-panel="reposo">
                <span class="ea-step-eyebrow">Conexión directa</span>
                <h2 class="ea-step-title">Enchufá tu Eden Air y apretá Conectar</h2>
                <p class="ea-step-lede">
                    Te vamos a mostrar un código QR generado en el momento. No tenés que buscar
                    nada en la caja ni escribir ningún código.
                </p>

                <ol class="ea-pair-steps">
                    <li><b>1</b><span>Enchufá el equipo y esperá unos segundos.<small>Si todavía no tiene WiFi configurado, crea su propia red para que puedas configurarlo.</small></span></li>
                    <li><b>2</b><span>Escaneá el QR con la cámara del celular.<small>El celular se conecta solo a la red del equipo, sin escribir contraseñas.</small></span></li>
                    <li><b>3</b><span>Elegí tu WiFi de casa en la pantalla que se abre sola.<small>Ahí escribís la clave de <b>tu</b> WiFi. El Eden Air se conecta y aparece acá en unos segundos.</small></span></li>
                </ol>

                <p class="ea-step-lede ea-pair-nota-final">
                    <b>No necesitás instalar nada.</b> Todo se hace desde el celular:
                    ni cables ni programas.
                </p>

                <button type="button" class="ea-button ea-button-primary ea-button-buy ea-pair-cta" data-pair-start>
                    Conectar
                </button>
            </div>

            <!-- ===== PANEL 2 · En vivo: el QR y la espera ===== -->
            <div class="ea-pair-panel" data-pair-panel="vivo" hidden>
                <div class="ea-pair-live">
                    <div>
                        <?php /* El <svg> del QR lo inyecta conectar.js con lo que responde el servidor. */ ?>
                        <div class="ea-pair-qr" data-pair-qr></div>
                        <p class="ea-pair-qr-note">Apuntá la cámara del celular al código</p>
                    </div>

                    <div>
                        <span class="ea-step-eyebrow">Paso final</span>
                        <h2 class="ea-step-title">Escaneá el código</h2>
                        <p class="ea-step-lede">
                            Tu celular te va a ofrecer conectarse a la red del equipo. Aceptá, y en la
                            pantalla que se abre sola elegí el WiFi de tu casa y poné su contraseña.
                        </p>

                        <ul class="ea-pair-hints">
                            <li>Si tu celular avisa que <b>“esta red no tiene internet”</b>, es normal:
                                esa red es el propio Eden Air. Elegí mantener la conexión.</li>
                            <li>Si la pantalla no se abre sola, entrá desde el navegador del celular a
                                <b>http://192.168.4.1</b></li>
                            <li>Si te equivocás en la clave, el equipo te lo avisa y podés reintentar
                                sin volver a empezar.</li>
                            <li>Al terminar, el equipo te ofrece un botón <b>«Ver mi Eden Air»</b>:
                                podés seguir todo desde el celular sin volver a esta pantalla.</li>
                        </ul>

                        <dl class="ea-pair-net">
                            <div><dt>Red del equipo</dt><dd data-pair-ssid><?= esc($ssid) ?></dd></div>
                            <div><dt>Contraseña</dt><dd data-pair-pass>—</dd></div>
                        </dl>

                        <p class="ea-pair-timer">Este código vale por <b data-pair-timer><?= esc((string) $minutos) ?>:00</b> minutos.</p>

                        <p class="ea-pair-status" data-pair-status role="status" aria-live="polite">
                            <span class="ea-pair-spinner" aria-hidden="true"></span>
                            <span data-pair-status-text>Esperando a que tu Eden Air se conecte…</span>
                        </p>

                        <div class="ea-wizard-nav ea-wizard-nav--izq">
                            <button type="button" class="ea-button ea-button-ghost" data-pair-cancel>Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== PANEL 3 · Resultado: conectado ===== -->
            <div class="ea-pair-panel" data-pair-panel="ok" hidden>
                <div class="ea-pair-result">
                    <span class="ea-pair-result-icon" aria-hidden="true"><?= icono('check-grande') ?></span>
                    <h2 data-pair-ok-title>¡Tu Eden Air quedó conectado!</h2>
                    <p data-pair-ok-text>Ya está midiendo el aire de tu ambiente. En unos minutos vas a ver las primeras lecturas en el panel.</p>
                    <div class="ea-pair-actions">
                        <a href="<?= site_url('panel') ?>" class="ea-button ea-button-primary ea-button-buy">Ir al panel</a>
                        <a href="<?= site_url('panel/dispositivos') ?>" class="ea-button ea-button-secondary">Ver mis dispositivos</a>
                    </div>
                </div>
            </div>

            <!-- ===== PANEL 4 · Resultado: se venció o falló ===== -->
            <div class="ea-pair-panel" data-pair-panel="fin" hidden>
                <div class="ea-pair-result ea-pair-result--warn">
                    <span class="ea-pair-result-icon" aria-hidden="true"><?= icono('alerta') ?></span>
                    <h2 data-pair-fin-title>Se agotó el tiempo</h2>
                    <p data-pair-fin-text>Nadie conectó un equipo durante la espera. Podés volver a intentarlo cuando quieras.</p>
                    <div class="ea-pair-actions">
                        <button type="button" class="ea-button ea-button-primary ea-button-buy" data-pair-retry>Intentar de nuevo</button>
                        <a href="<?= site_url('panel/dispositivos') ?>" class="ea-button ea-button-secondary">Volver</a>
                    </div>
                </div>
            </div>

        </div>
    </section>

<?= $this->endSection() ?>
