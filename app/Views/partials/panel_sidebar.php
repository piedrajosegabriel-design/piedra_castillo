<?php
/**
 * ============================================================================
 * MENÚ LATERAL DEL PANEL — el único lugar donde vive.
 * ============================================================================
 *
 * ---------------------------------------------------------------------------
 * ¿CÓMO AGREGO UNA OPCIÓN AL MENÚ?
 * ---------------------------------------------------------------------------
 * Agregá una línea al array $menu de acá abajo. Nada más: el HTML, las clases
 * y el marcado del ítem activo salen solos.
 *
 *   ['clave' => 'reportes', 'texto' => 'Reportes', 'icono' => 'chevron',
 *    'href' => site_url('panel/reportes')],
 *
 * Y en la vista de esa pantalla poné 'sidebarActivo' => 'reportes' para que
 * quede marcada.
 *
 * Claves de un ítem:
 *   clave    string  Identificador; se compara contra $active para marcarlo.
 *   texto    string  Etiqueta visible.
 *   icono    string  Nombre del set (ver app/Helpers/eden_helper.php).
 *   href     string  Destino.
 *   globo    int     Número chiquito a la derecha (ej: cantidad de equipos).
 *   clase    string  Clase extra, ej: 'ea-sidebar-item--cta' para destacarlo.
 *   attrs    string  Atributos sueltos.
 *   seccion  string  En vez de un ítem, dibuja un título separador.
 *
 * ---------------------------------------------------------------------------
 * PARÁMETROS (los pasa layouts/panel.php)
 * ---------------------------------------------------------------------------
 *   $active        string  Clave del ítem a marcar como activo.
 *   $devicesCount  int     Número del globito de "Mis dispositivos".
 */
$active       = isset($active) && is_string($active) ? $active : '';
$devicesCount = isset($devicesCount) ? (int) $devicesCount : null;

$menu = [
    ['clave' => 'inicio',           'texto' => 'Inicio',            'icono' => 'casa',           'href' => site_url('panel')],
    ['clave' => 'dispositivos',     'texto' => 'Mis dispositivos',  'icono' => 'dispositivo',    'href' => site_url('panel/dispositivos'), 'globo' => $devicesCount],
    ['clave' => 'ambientes',        'texto' => 'Ambientes',         'icono' => 'ambiente',       'href' => site_url('panel/ambientes')],
    ['clave' => 'automatizaciones', 'texto' => 'Automatizaciones',  'icono' => 'automatizacion', 'href' => site_url('panel') . '#automatizaciones'],

    ['seccion' => 'Cuenta'],
    ['clave' => 'perfil', 'texto' => 'Perfil',          'icono' => 'perfil',  'href' => site_url('panel/perfil')],
    ['clave' => 'compra', 'texto' => 'Comprar EdenAir', 'icono' => 'carrito', 'href' => site_url('panel/compra'),
     'clase' => 'ea-sidebar-item--cta', 'attrs' => 'data-ea-buy-cta'],
];
?>
<aside class="ea-sidebar" id="dashboardSidebar" aria-label="Navegación principal del dashboard">

    <!-- Marca -->
    <div class="ea-sidebar-brand">
        <span class="ea-sidebar-mark ea-sidebar-mark--e" aria-hidden="true">
            <?= marca_e('sb-e') ?>
        </span>
        <span class="ea-sidebar-word">
            <b>Eden<em>Air</em></b>
            <span>Panel · v0.5</span>
        </span>
    </div>

    <!-- Opciones (salen del array $menu de arriba) -->
    <nav class="ea-sidebar-nav" aria-label="Secciones">
        <?php foreach ($menu as $item): ?>

            <?php if (isset($item['seccion'])): ?>
                <div class="ea-sidebar-section"><?= esc($item['seccion']) ?></div>
                <?php continue; ?>
            <?php endif; ?>

            <?php $esActivo = $active !== '' && $active === ($item['clave'] ?? null); ?>
            <a href="<?= esc($item['href']) ?>"
               class="ea-sidebar-item<?= $esActivo ? ' is-active' : '' ?><?= isset($item['clase']) ? ' ' . $item['clase'] : '' ?>"
               <?= $esActivo ? 'aria-current="page"' : '' ?>
               <?= $item['attrs'] ?? '' ?>>
                <span class="ea-sidebar-icon" aria-hidden="true"><?= icono($item['icono']) ?></span>
                <span class="ea-sidebar-label"><?= esc($item['texto']) ?></span>
                <?php if (isset($item['globo']) && $item['globo'] !== null): ?>
                    <span class="ea-sidebar-meta"><?= esc((string) $item['globo']) ?></span>
                <?php endif; ?>
            </a>

        <?php endforeach; ?>
    </nav>

    <!-- Pie: estado del sistema + salir -->
    <div class="ea-sidebar-footer">
        <div class="ea-sidebar-status">
            <span class="ea-sidebar-dot tone-success"></span>
            <span class="ea-sidebar-foot-label">Sistema en línea · ESP32 preparada</span>
        </div>
        <a href="<?= site_url('logout') ?>" class="ea-sidebar-logout" title="Cerrar sesión">
            <?= icono('salir', 16) ?>
            <span class="ea-sidebar-label">Cerrar sesión</span>
        </a>
    </div>

</aside>
