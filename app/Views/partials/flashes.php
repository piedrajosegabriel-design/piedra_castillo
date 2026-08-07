<?php
/**
 * Mensajes de una sola vez (flash): el "Guardado con éxito" o el "Revisá los
 * campos" que aparece arriba del contenido después de un POST.
 *
 * No recibe nada: lee la sesión. El layout ya lo incluye, así que una vista
 * normal no tiene que hacer nada para que sus mensajes se vean.
 *
 * Quién los escribe: los controladores, con
 *   return redirect()->to('...')->with('success', 'Listo');
 *   return redirect()->back()->with('errors', $validacion->getErrors());
 */
$exito   = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errores = session()->getFlashdata('errors') ?? [];
?>
<?php if ($exito): ?>
    <div class="ea-flash ea-flash-success"><?= esc($exito) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="ea-flash ea-flash-danger"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="ea-flash ea-flash-danger">
        <ul>
            <?php foreach ($errores as $mensaje): ?>
                <li><?= esc($mensaje) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
