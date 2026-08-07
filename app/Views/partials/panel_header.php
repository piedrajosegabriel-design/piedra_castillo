<?php
/**
 * ============================================================================
 * BARRA SUPERIOR DEL PANEL — el único lugar donde vive.
 * ============================================================================
 * Antes esta barra estaba copiada en las 8 vistas internas: agregarle un botón
 * significaba pegarlo 8 veces y que igual quedaran distintas. Ahora se toca acá
 * y cambia en todo el panel.
 *
 * ---------------------------------------------------------------------------
 * ¿CÓMO AGREGO UN BOTÓN?
 * ---------------------------------------------------------------------------
 * No se toca este archivo. En la vista que lo necesite, agregá una línea a
 * 'botones' dentro de su bloque de configuración:
 *
 *   'botones' => [
 *       ['texto' => 'Conectar', 'href' => site_url('panel/dispositivos/conectar'), 'icono' => 'mas'],
 *   ],
 *
 * Claves de un botón (todas opcionales menos una de texto/icono):
 *   texto   string  Etiqueta. Si no la ponés, sale un botón redondo de ícono solo.
 *   icono   string  Nombre del set (ver app/Helpers/eden_helper.php).
 *   href    string  A dónde va. Sin href sale un <button type="button">.
 *   estilo  string  primary | secondary | ghost   (default: secondary)
 *   attrs   string  Atributos sueltos: 'data-algo id="x"'. Para engancharle JS.
 *   titulo  string  Tooltip. Obligatorio en los botones de ícono solo (accesibilidad).
 *
 * Para que quede igual en todas las pantallas no hay que copiar clases: el
 * estilo lo pone este archivo.
 *
 * ---------------------------------------------------------------------------
 * PARÁMETROS — todo adentro de la clave 'cabecera', que el layout arma con la
 * config de cada vista. Va agrupado para que no herede datos de otro partial
 * (ver la nota larga en partials/logo.php).
 * ---------------------------------------------------------------------------
 *   titulo   string  Título grande (h1).
 *   bajada   string  Línea chica debajo.
 *   botones  array   Ver arriba.
 *   extra    string  HTML libre entre el título y los botones (chips, selects).
 *   usuario  bool    Mostrar el avatar + nombre de la derecha (default true).
 */
$cabecera = is_array($cabecera ?? null) ? $cabecera : [];

$titulo  = (string) ($cabecera['titulo'] ?? '');
$bajada  = (string) ($cabecera['bajada'] ?? '');
$botones = is_array($cabecera['botones'] ?? null) ? $cabecera['botones'] : [];
$extra   = (string) ($cabecera['extra']  ?? '');
$usuario = $cabecera['usuario'] ?? true;

$nombre  = (string) (session()->get('user_name') ?? 'Usuario');
$inicial = strtoupper(mb_substr(trim($nombre), 0, 1) ?: 'U');
?>
<header class="dashboard-header ea-header">

    <!-- Hamburguesa: abre y cierra el menú lateral (lo maneja dashboard.js) -->
    <button type="button" class="ea-burger" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Mostrar u ocultar menú">
        <span></span><span></span><span></span>
    </button>

    <!-- Título de la pantalla -->
    <div class="ea-header-titles">
        <h1><?= esc($titulo) ?></h1>
        <?php if ($bajada !== ''): ?><p><?= esc($bajada) ?></p><?php endif; ?>
    </div>

    <!-- HTML libre de la vista (chips de estado, selector de dispositivo...) -->
    <?= $extra ?>

    <!-- Botones de la vista -->
    <?php if ($botones !== []): ?>
        <div class="ea-header-actions">
            <?php foreach ($botones as $boton):
                $texto     = (string) ($boton['texto'] ?? '');
                $nombreIco = (string) ($boton['icono'] ?? '');
                $href      = $boton['href']   ?? null;
                $tooltip   = (string) ($boton['titulo'] ?? $texto);
                $soloIcono = $texto === '' && $nombreIco !== '';

                $clase = $soloIcono
                    ? 'ea-header-icon-btn'
                    : 'ea-button ea-button-sm ea-button-' . ($boton['estilo'] ?? 'secondary');

                $etiqueta = $href !== null ? 'a' : 'button';
                $atributos = $href !== null
                    ? ' href="' . esc((string) $href) . '"'
                    : ' type="button"';
                if ($tooltip !== '') {
                    $atributos .= ' title="' . esc($tooltip) . '"';
                }
                if ($soloIcono) {
                    $atributos .= ' aria-label="' . esc($tooltip) . '"';
                }
                if (! empty($boton['attrs'])) {
                    $atributos .= ' ' . $boton['attrs'];
                }
            ?>
                <<?= $etiqueta ?> class="<?= $clase ?>"<?= $atributos ?>>
                    <?php if ($nombreIco !== ''): ?><?= icono($nombreIco, 16) ?><?php endif; ?>
                    <?php if ($texto !== ''): ?><?= esc($texto) ?><?php endif; ?>
                </<?= $etiqueta ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Interruptor claro / oscuro -->
    <div class="ea-header-tools">
        <?= view('partials/theme_toggle', ['toggle' => []]) ?>
    </div>

    <!-- Quién está logueado -->
    <?php if ($usuario): ?>
        <div class="ea-header-user" title="<?= esc($nombre) ?>">
            <span class="ea-header-avatar"><?= esc($inicial) ?></span>
            <span class="ea-header-name">
                <?= esc($nombre) ?>
                <small>Cuenta Eden Air</small>
            </span>
        </div>
    <?php endif; ?>

</header>
