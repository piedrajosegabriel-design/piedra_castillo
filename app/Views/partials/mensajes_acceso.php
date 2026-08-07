<?php
/**
 * Avisos de las pantallas de acceso (login, registro, recuperación).
 *
 * Es el equivalente de partials/flashes.php pero con el estilo de la tarjeta
 * de acceso (.ea-message) en vez del estilo del panel (.ea-flash).
 *
 * No recibe nada: lee la sesión. El layout ya lo incluye.
 */
$error   = session()->getFlashdata('error');
$exito   = session()->getFlashdata('success');
$errores = session()->getFlashdata('errors') ?? [];
?>
<?php if ($error): ?>
    <div class="ea-message ea-message--error"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($exito): ?>
    <div class="ea-message ea-message--success"><?= esc($exito) ?></div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="ea-message ea-message--error">
        <div>
            <strong>Revisá los siguientes campos:</strong>
            <ul>
                <?php foreach ($errores as $mensaje): ?>
                    <li><?= esc($mensaje) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
