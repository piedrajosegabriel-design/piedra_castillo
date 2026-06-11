<?php
/**
 * EdenAir — Theme toggle (sol / luna) compatible con tema.js.
 * El input mantiene id="input" para no romper la lógica existente.
 *
 * Variables opcionales:
 *   $unique  string  Sufijo de IDs internos para evitar colisiones cuando
 *                    se usan dos toggles en la misma página. Default vacío.
 *   $label   string  Etiqueta accesible (default "Cambiar tema").
 */
$unique = isset($unique) && is_string($unique) ? $unique : '';
$label  = isset($label)  && is_string($label)  ? $label  : 'Cambiar tema';
?>
<label class="ea-theme-switch switch" title="<?= esc($label) ?>">
    <input id="input<?= esc($unique) ?>" type="checkbox" aria-label="<?= esc($label) ?>" />
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

<!-- ===== ESTILOS DEL TOGGLE (embebidos a propósito) =====
     Este <style> viaja CON el partial: así el toggle se ve bien en cualquier
     página que lo incluya sin depender de un CSS externo. Como va en el
     <body> (después de los CSS del <head>), gana los empates de cascada y
     dashboard.css lo ajusta con selectores más específicos donde hace falta.
     Si la página incluye dos toggles (navbar + menú móvil) el bloque se
     repite, pero es CSS idéntico: no tiene efecto visual. -->
<style>
    .ea-theme-switch {
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }
    .ea-theme-switch input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .ea-theme-track {
        position: relative;
        width: 56px;
        height: 30px;
        background: var(--eden-100);
        border: 1px solid var(--ea-rule);
        border-radius: 999px;
        transition: background-color 0.25s ease, border-color 0.25s ease;
        display: inline-flex;
        align-items: center;
    }
    .ea-theme-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 24px;
        height: 24px;
        background: var(--ea-card);
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(20, 32, 26, 0.18);
        transition: transform 0.25s ease, background-color 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--eden-700);
    }
    .ea-theme-sun,
    .ea-theme-moon {
        position: absolute;
        width: 14px;
        height: 14px;
        transition: opacity 0.25s ease, transform 0.25s ease;
    }
    .ea-theme-moon { opacity: 0; transform: scale(0.4) rotate(-25deg); }
    .ea-theme-sun { opacity: 1; transform: scale(1); }

    .ea-theme-switch input:checked + .ea-theme-track {
        background: var(--eden-800);
        border-color: var(--eden-700);
    }
    .ea-theme-switch input:checked + .ea-theme-track .ea-theme-thumb {
        transform: translateX(26px);
        background: var(--eden-700);
        color: var(--eden-100);
    }
    .ea-theme-switch input:checked + .ea-theme-track .ea-theme-sun { opacity: 0; transform: scale(0.4) rotate(25deg); }
    .ea-theme-switch input:checked + .ea-theme-track .ea-theme-moon { opacity: 1; transform: scale(1) rotate(0); }
</style>
