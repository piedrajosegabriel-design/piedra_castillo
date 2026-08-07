<?php
/**
 * ============================================================================
 * BARRA SUPERIOR PÚBLICA — landing, portfolio, login, registro, recuperación.
 * ============================================================================
 * (La del panel es otra: app/Views/partials/panel_header.php)
 *
 * ---------------------------------------------------------------------------
 * PARÁMETROS (todos opcionales)
 * ---------------------------------------------------------------------------
 *   $subtitle        string  Línea chica bajo "EdenAir".
 *   $actions         string  HTML de la zona derecha. Si no se pasa, se usan
 *                            los botones por defecto según $conSesion.
 *   $conSesion       bool    true → "Ir al panel" y "Cerrar sesión".
 *                            false → "Login" y "Crear cuenta".
 *   $navLinks        array   Accesos rápidos: [['href' => '#x', 'label' => 'X']]
 *   $portfolioMenu   bool    Mostrar el mega menú de Portfolio (default true).
 *   $activePortfolio bool    Marcarlo como activo (por defecto lo detecta solo).
 *
 * ---------------------------------------------------------------------------
 * ¿CÓMO AGREGO UNA SECCIÓN AL MENÚ DE PORTFOLIO?
 * ---------------------------------------------------------------------------
 * Una línea al array $portfolioSections de abajo. El ancla tiene que existir
 * como id="..." en app/Views/portfolio.php.
 *
 * ANIMACIÓN: la apertura del mega menú la maneja public/JS/navbar.js.
 */
$subtitle  = $subtitle  ?? 'Monitoreo ambiental';
$actions   = $actions   ?? null;
$conSesion = $conSesion ?? false;
$navLinks  = isset($navLinks) && is_array($navLinks) ? $navLinks : [];

$portfolioMenu   = $portfolioMenu   ?? true;
$activePortfolio = $activePortfolio ?? (function_exists('uri_string') && str_starts_with((string) uri_string(), 'portfolio'));

$portfolioUrl = site_url('portfolio');

$portfolioSections = [
    ['anchor' => 'pagina-principal',     'label' => 'Página principal',           'hint' => 'Introducción del portfolio'],
    ['anchor' => 'imagen-corporativa',   'label' => 'Imagen corporativa',         'hint' => 'Identidad visual'],
    ['anchor' => 'quienes-somos',        'label' => 'Quiénes somos',              'hint' => 'El equipo detrás'],
    ['anchor' => 'acerca-de-eden-air',   'label' => 'Acerca de Eden Air',         'hint' => 'Producto y propuesta'],
    ['anchor' => 'analisis-mercado',     'label' => 'Análisis de mercado',        'hint' => 'TP Nº 2 · Emprendimientos'],
    ['anchor' => 'analisis-competencia', 'label' => 'Análisis de la competencia', 'hint' => 'Comparativa con otros'],
    ['anchor' => 'plan-operativo',       'label' => 'Plan operativo',             'hint' => 'Etapas y recursos'],
];

// Dentro del propio portfolio los enlaces son anclas sueltas; desde afuera
// tienen que llevar la URL completa.
$portfolioHref = static fn (string $anchor): string
    => ($activePortfolio ? '' : $portfolioUrl) . '#' . $anchor;

$hasNavList = $navLinks !== [] || $portfolioMenu;
?>
<header class="ea-navbar">
    <div class="ea-page ea-navbar-inner">

        <?= view('partials/logo', ['logo' => [
            'href'     => site_url('/'),
            'size'     => 36,
            'subtitle' => $subtitle,
            'variant'  => 'horizontal',
        ]]) ?>

        <?php if ($hasNavList): ?>
            <ul class="ea-nav-links" aria-label="Secciones">

                <!-- Accesos rápidos que pasó la página -->
                <?php foreach ($navLinks as $link): ?>
                    <li><a href="<?= esc($link['href']) ?>"><?= esc($link['label']) ?></a></li>
                <?php endforeach; ?>

                <!-- Mega menú de Portfolio -->
                <?php if ($portfolioMenu): ?>
                    <li class="ea-mega" data-ea-mega>
                        <a class="ea-mega-trigger<?= $activePortfolio ? ' is-active' : '' ?>"
                           href="<?= esc($portfolioUrl) ?>"
                           aria-haspopup="true" aria-expanded="false" data-ea-mega-trigger>
                            <span>Portfolio</span>
                            <?= icono('caret', 10, 'ea-mega-caret') ?>
                        </a>

                        <div class="ea-mega-panel" data-ea-mega-panel role="menu" aria-label="Secciones del portfolio">
                            <div class="ea-mega-inner">
                                <header class="ea-mega-head">
                                    <span class="ea-mega-eyebrow">Portfolio · Eden Air</span>
                                    <h3 class="ea-mega-title">Recorrido del <em>proyecto</em>.</h3>
                                    <p class="ea-mega-lede">Análisis de mercado, identidad y plan operativo en un solo lugar.</p>
                                </header>

                                <ul class="ea-mega-list">
                                    <?php foreach ($portfolioSections as $section): ?>
                                        <li>
                                            <a href="<?= esc($portfolioHref($section['anchor'])) ?>" role="menuitem" data-ea-mega-link>
                                                <span class="ea-mega-link-label"><?= esc($section['label']) ?></span>
                                                <span class="ea-mega-link-hint"><?= esc($section['hint']) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="ea-mega-foot">
                                    <span class="ea-mega-foot-tag">
                                        <span class="ea-mega-foot-dot" aria-hidden="true"></span>
                                        Apartado actual · <strong>Análisis de mercado</strong>
                                    </span>
                                    <a href="<?= esc($portfolioUrl) ?>" class="ea-mega-foot-link">Abrir portfolio →</a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

            </ul>
        <?php endif; ?>

        <!-- Zona derecha: tema + botones -->
        <div class="ea-nav-actions">
            <?= view('partials/theme_toggle', ['toggle' => []]) ?>

            <?php if ($actions !== null): ?>
                <?= $actions ?>
            <?php elseif ($conSesion): ?>
                <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary">Ir al panel</a>
                <a href="<?= site_url('logout') ?>" class="ea-button ea-button-primary">Cerrar sesión</a>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="ea-button ea-button-secondary">Login</a>
                <a href="<?= site_url('registro') ?>" class="ea-button ea-button-primary">Crear cuenta</a>
            <?php endif; ?>
        </div>

    </div>
</header>

<?php if ($portfolioMenu): ?>
    <script src="<?= asset('JS/navbar.js') ?>" defer></script>
<?php endif; ?>
