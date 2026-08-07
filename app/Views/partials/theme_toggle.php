<?php
/**
 * Interruptor claro / oscuro.
 *
 * Estructura: el <label> con un checkbox escondido y la pastilla que se corre.
 * Estilos:    eden-brand.css, sección "Interruptor claro / oscuro".
 * Lógica:     public/JS/tema.js (guarda la elección en localStorage).
 *
 * Parámetros — todo adentro de la clave 'toggle', para que un uso no herede
 * los datos del otro (CodeIgniter comparte los datos entre los view() de una
 * misma página; ver la nota larga en partials/logo.php):
 *
 *   sufijo  string  Se pega al id interno. Necesario cuando la misma página
 *                   tiene dos toggles (barra superior + menú móvil): dos
 *                   elementos no pueden compartir el mismo id.
 *   label   string  Texto accesible. Default "Cambiar tema".
 *
 *   <?= view('partials/theme_toggle', ['toggle' => ['sufijo' => '-movil']]) ?>
 */
$toggle = is_array($toggle ?? null) ? $toggle : [];
$sufijo = (string) ($toggle['sufijo'] ?? '');
$label  = (string) ($toggle['label']  ?? 'Cambiar tema');
?>
<label class="ea-theme-switch" title="<?= esc($label) ?>">
    <input id="input<?= esc($sufijo) ?>" type="checkbox" aria-label="<?= esc($label) ?>">
    <span class="ea-theme-track" aria-hidden="true">
        <span class="ea-theme-thumb">
            <svg class="ea-theme-sun" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="4.5" fill="currentColor"/>
                <g stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                    <line x1="12" y1="2" x2="12" y2="4.5"/>
                    <line x1="12" y1="19.5" x2="12" y2="22"/>
                    <line x1="2" y1="12" x2="4.5" y2="12"/>
                    <line x1="19.5" y1="12" x2="22" y2="12"/>
                    <line x1="4.8" y1="4.8" x2="6.6" y2="6.6"/>
                    <line x1="17.4" y1="17.4" x2="19.2" y2="19.2"/>
                    <line x1="4.8" y1="19.2" x2="6.6" y2="17.4"/>
                    <line x1="17.4" y1="6.6" x2="19.2" y2="4.8"/>
                </g>
            </svg>
            <svg class="ea-theme-moon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 14.2A8 8 0 1 1 9.8 4a7 7 0 0 0 10.2 10.2Z" fill="currentColor"/>
            </svg>
        </span>
    </span>
</label>
