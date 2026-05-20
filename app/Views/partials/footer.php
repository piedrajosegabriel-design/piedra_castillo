<?php
/**
 * EdenAir — pie compartido para páginas públicas.
 */
?>
<footer class="ea-footer">
    <div class="ea-page">
        <div class="ea-footer-inner">
            <div>
                <?= view('partials/logo', ['tone' => 'cream', 'size' => 38]) ?>
                <p>
                    Plataforma de monitoreo y control ambiental conectada a un módulo ESP32.
                    Hecha para que cada habitación sea su propio pequeño edén.
                </p>
            </div>
            <div>
                <h4>Plataforma</h4>
                <ul class="ea-stack">
                    <li><a href="<?= site_url('/') ?>">Inicio</a></li>
                    <li><a href="<?= site_url('login') ?>">Login</a></li>
                    <li><a href="<?= site_url('registro') ?>">Crear cuenta</a></li>
                </ul>
            </div>
            <div>
                <h4>Proyecto</h4>
                <ul class="ea-stack">
                    <li>Tesina · 2026</li>
                    <li>CodeIgniter 4 · ESP32</li>
                    <li><span style="color: var(--ea-citrus); font-family: var(--ea-font-mono);">● Aire limpio</span></li>
                </ul>
            </div>
        </div>
        <div class="ea-footer-bottom">
            <span>© <?= date('Y') ?> EdenAir</span>
            <span>Respirá mejor, viví más cómodo.</span>
        </div>
    </div>
</footer>
