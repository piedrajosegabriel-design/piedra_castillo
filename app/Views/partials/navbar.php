<?php
/**
 * EdenAir — navbar superior para páginas públicas (inicio / login / registro / portfolio).
 *
 * Variables opcionales:
 *   $subtitle        string  Línea pequeña bajo "EdenAir".
 *   $actions         string  HTML a inyectar en la zona derecha (botones, links).
 *   $conSesion       bool    Si es true muestra accesos a panel/logout, si false a login/registro.
 *   $navLinks        array   Lista [['href' => '...', 'label' => '...']] de accesos rápidos.
 *   $portfolioMenu   bool    Mostrar el mega menú de Portfolio (default true).
 *   $activePortfolio bool    Marca el botón Portfolio como activo (default detect por URI).
 */
$subtitle  = $subtitle  ?? 'Monitoreo ambiental';
$actions   = $actions   ?? null;
$conSesion = $conSesion ?? false;
$navLinks  = isset($navLinks) && is_array($navLinks) ? $navLinks : [];

$portfolioMenu   = $portfolioMenu   ?? true;
$activePortfolio = $activePortfolio ?? (function_exists('uri_string') && str_starts_with((string) uri_string(), 'portfolio'));

$portfolioUrl = site_url('portfolio');

$portfolioSections = [
    ['anchor' => 'pagina-principal',     'label' => 'Página principal',          'hint' => 'Introducción del portfolio'],
    ['anchor' => 'imagen-corporativa',   'label' => 'Imagen corporativa',        'hint' => 'Identidad visual'],
    ['anchor' => 'quienes-somos',        'label' => 'Quiénes somos',             'hint' => 'El equipo detrás'],
    ['anchor' => 'acerca-de-eden-air',   'label' => 'Acerca de Eden Air',        'hint' => 'Producto y propuesta'],
    ['anchor' => 'analisis-mercado',     'label' => 'Análisis de mercado',       'hint' => 'TP Nº 2 · Emprendimientos'],
    ['anchor' => 'analisis-competencia', 'label' => 'Análisis de la competencia','hint' => 'Comparativa con otros'],
    ['anchor' => 'plan-operativo',       'label' => 'Plan operativo',            'hint' => 'Etapas y recursos'],
];

$portfolioHref = static function (string $anchor) use ($activePortfolio, $portfolioUrl): string {
    return $activePortfolio ? '#' . $anchor : $portfolioUrl . '#' . $anchor;
};

$hasNavList = $navLinks !== [] || $portfolioMenu;
?>
<header class="ea-navbar">
    <div class="ea-page ea-navbar-inner">
        <?= view('partials/logo', [
            'href'     => site_url('/'),
            'size'     => 36,
            'subtitle' => $subtitle,
            'variant'  => 'horizontal',
        ]) ?>

        <?php if ($hasNavList): ?>
            <ul class="ea-nav-links" aria-label="Secciones">
                <?php foreach ($navLinks as $link): ?>
                    <li><a href="<?= esc($link['href']) ?>"><?= esc($link['label']) ?></a></li>
                <?php endforeach; ?>

                <?php if ($portfolioMenu): ?>
                    <li class="ea-mega" data-ea-mega>
                        <a class="ea-mega-trigger<?= $activePortfolio ? ' is-active' : '' ?>"
                           href="<?= esc($portfolioUrl) ?>"
                           aria-haspopup="true"
                           aria-expanded="false"
                           data-ea-mega-trigger>
                            <span>Portfolio</span>
                            <svg class="ea-mega-caret" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true">
                                <path d="M2 4.5 6 8.5 10 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
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

        <div class="ea-nav-actions">
            <?= view('partials/theme_toggle') ?>

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

<?php if ($portfolioMenu && ! defined('EA_MEGA_MENU_JS_LOADED')): define('EA_MEGA_MENU_JS_LOADED', true); ?>
<script>
(function () {
    if (window.__eaMegaInit) return;
    window.__eaMegaInit = true;

    function ready(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    ready(function () {
        var megas = document.querySelectorAll("[data-ea-mega]");
        if (!megas.length) return;
        var hoverMq = window.matchMedia("(hover: hover) and (pointer: fine)");

        megas.forEach(function (mega) {
            var trigger = mega.querySelector("[data-ea-mega-trigger]");
            var panel   = mega.querySelector("[data-ea-mega-panel]");
            if (!trigger || !panel) return;

            var openTimer = 0;
            var closeTimer = 0;

            function open() {
                window.clearTimeout(closeTimer);
                mega.classList.add("is-open");
                trigger.setAttribute("aria-expanded", "true");
            }
            function close() {
                window.clearTimeout(openTimer);
                mega.classList.remove("is-open");
                trigger.setAttribute("aria-expanded", "false");
            }
            function scheduleOpen()  { window.clearTimeout(closeTimer); openTimer  = window.setTimeout(open,  60); }
            function scheduleClose() { window.clearTimeout(openTimer);  closeTimer = window.setTimeout(close, 180); }

            mega.addEventListener("mouseenter", function () {
                if (hoverMq.matches) scheduleOpen();
            });
            mega.addEventListener("mouseleave", function () {
                if (hoverMq.matches) scheduleClose();
            });

            trigger.addEventListener("click", function (event) {
                if (hoverMq.matches) return; // En desktop el click navega al portfolio
                event.preventDefault();
                if (mega.classList.contains("is-open")) close(); else open();
            });

            trigger.addEventListener("focus", open);
            trigger.addEventListener("keydown", function (event) {
                if (event.key === "Escape") { close(); trigger.blur(); }
                if (event.key === "ArrowDown") { event.preventDefault(); open(); var first = panel.querySelector("a"); if (first) first.focus(); }
            });

            panel.addEventListener("keydown", function (event) {
                if (event.key === "Escape") { close(); trigger.focus(); }
            });

            panel.addEventListener("click", function (event) {
                var link = event.target.closest("a[data-ea-mega-link]");
                if (link) close();
            });

            document.addEventListener("click", function (event) {
                if (!mega.contains(event.target)) close();
            });
        });

        // Resaltado de Portfolio si el usuario entró por anchor
        if (location.hash) {
            var hashTrigger = document.querySelector("[data-ea-mega-trigger]");
            if (hashTrigger && hashTrigger.getAttribute("href") &&
                hashTrigger.getAttribute("href").indexOf("portfolio") !== -1 &&
                document.querySelector("[data-ea-portfolio]")) {
                hashTrigger.classList.add("is-active");
            }
        }
    });
})();
</script>
<?php endif; ?>
