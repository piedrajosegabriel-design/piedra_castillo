<?php
/**
 * OJO PARA VER LA CONTRASEÑA
 *
 * Botón chico que se mete adentro del campo, contra el borde derecho, y
 * alterna entre puntitos y texto plano. Lo engancha public/JS/acceso.js por
 * el atributo data-ver-password.
 *
 * CÓMO SE USA
 *   <div class="ea-password">
 *       <input type="password" id="miPassword" ...>
 *       <?= view('partials/ojo_password', ['objetivo' => '#miPassword']) ?>
 *   </div>
 *
 * Podés apuntar a varios campos a la vez separándolos con coma:
 *   ['objetivo' => '#password, #password_confirm']
 *
 * POR QUÉ ES UN PARTIAL
 * El ícono son dos SVG de ~10 líneas. Repetirlos en las cuatro pantallas de
 * contraseña era copiar 80 líneas que después hay que mantener sincronizadas.
 */

$objetivo = $objetivo ?? '';
?>
<button type="button"
        class="ea-password-ojo"
        data-ver-password="<?= esc($objetivo, 'attr') ?>"
        data-visible="false"
        aria-label="Mostrar contraseña"
        title="Mostrar contraseña">

    <!-- Ojo abierto: se ve cuando la contraseña está oculta (tocá para mostrar) -->
    <svg class="ea-ojo-ver" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2.2 12S5.8 5.5 12 5.5 21.8 12 21.8 12 18.2 18.5 12 18.5 2.2 12 2.2 12Z"/>
        <circle cx="12" cy="12" r="3.2"/>
    </svg>

    <!-- Ojo tachado: se ve cuando la contraseña está a la vista (tocá para ocultar) -->
    <svg class="ea-ojo-ocultar" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9.9 5.8A9.3 9.3 0 0 1 12 5.5c6.2 0 9.8 6.5 9.8 6.5a17 17 0 0 1-3.2 4"/>
        <path d="M6.4 7.8A17 17 0 0 0 2.2 12S5.8 18.5 12 18.5a9.4 9.4 0 0 0 3.9-.8"/>
        <path d="M10.1 10.2a3.2 3.2 0 0 0 4.3 4.3"/>
        <line x1="3.5" y1="3.5" x2="20.5" y2="20.5"/>
    </svg>
</button>
