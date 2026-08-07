<?php
/**
 * Selector de dispositivo de la barra superior del panel.
 *
 * Si la cuenta tiene varios equipos muestra un <select> que cambia cuál se está
 * viendo; si tiene uno solo, muestra su nombre como etiqueta. Con ninguno no
 * dibuja nada.
 *
 * Parámetros — adentro de la clave 'selector', para que no herede datos de
 * otro partial (ver la nota larga en partials/logo.php):
 *   equipos  array   Lista con id, name, space e is_active.
 *   activo   string  Nombre del dispositivo que se está viendo.
 */
$selector     = is_array($selector ?? null) ? $selector : [];
$dispositivos = is_array($selector['equipos'] ?? null) ? $selector['equipos'] : [];
$activo       = (string) ($selector['activo'] ?? '');
?>
<?php if (count($dispositivos) > 1): ?>

    <?php /* FORMULARIO: cambiar de dispositivo.
             POST /panel/dispositivo-activo → PanelController::seleccionarDispositivo
             Se envía solo al elegir una opción; el botón es el respaldo sin JS. */ ?>
    <form method="post" action="<?= site_url('panel/dispositivo-activo') ?>" class="ea-device-switcher" data-preserve-scroll>
        <?= csrf_field() ?>
        <label for="ea-device-select" class="ea-device-switcher-label">Dispositivo</label>
        <div class="ea-device-switcher-control">
            <select id="ea-device-select" name="device_id" onchange="this.form.submit()" aria-label="Cambiar de dispositivo">
                <?php foreach ($dispositivos as $d): ?>
                    <option value="<?= esc((string) $d['id']) ?>" <?= ! empty($d['is_active']) ? 'selected' : '' ?>>
                        <?= esc($d['name']) ?> · <?= esc($d['space']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= icono('caret', 10, 'ea-device-switcher-caret') ?>
        </div>
        <noscript><button type="submit" class="ea-button ea-button-sm ea-button-secondary">Cambiar</button></noscript>
    </form>

<?php elseif ($activo !== ''): ?>

    <span class="ea-chip ea-chip-device" title="Dispositivo activo">
        <?= icono('dispositivo', 12) ?>
        <span><?= esc($activo) ?></span>
    </span>

<?php endif; ?>
