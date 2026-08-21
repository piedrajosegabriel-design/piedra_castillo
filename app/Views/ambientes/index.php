<?php
/**
 * AMBIENTES — listado de los espacios físicos del usuario + la referencia de
 * qué rangos usa cada tipo de espacio.
 * Ruta: /panel/ambientes · Controlador: AmbientesController::index
 * Recibe:
 *   $ambientes  array preparado por el controlador (tipo, rangos, equipos)
 *   $catalogo   los tipos de espacio con sus rangos (EnvironmentPresetService)
 *
 * Los rangos que se ven acá son de solo lectura: se cambian en
 * /panel/ambientes/{id}/editar (vista ambientes/editar.php).
 */
$ambientes = $ambientes ?? [];
$catalogo  = $catalogo  ?? [];

// Tipos que el usuario ya está usando: se marcan en la referencia de abajo.
$tiposEnUso = array_column($ambientes, 'tipo_clave');

$this->setData([
    'tituloPagina'  => 'Eden Air · Ambientes',
    'sidebarActivo' => 'ambientes',
    'cabecera'      => [
        'titulo' => 'Ambientes',
        'bajada' => 'Los lugares físicos donde están tus dispositivos Eden Air',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== Encabezado de la sección + acción principal ===== -->
    <section class="ea-dev-toolbar">
        <div>
            <h2 class="ea-dev-toolbar-title">Tus ambientes</h2>
            <p class="ea-dev-toolbar-sub">
                Cada ambiente representa un espacio físico y usa los rangos de su tipo
                (oficina, aula, hogar, dormitorio) o los que definas vos.
                Con <strong>Editar ambiente</strong> cambiás el tipo, el nombre y cada rango.
            </p>
        </div>
        <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-secondary">
            + Agregar dispositivo
        </a>
    </section>

    <?php if ($ambientes === []): ?>

        <!-- ===== Sin ambientes todavía ===== -->
        <section class="ea-dev-empty">
            <span class="ea-dev-empty-orb" aria-hidden="true"></span>
            <h3>Todavía no tenés ambientes</h3>
            <p>Los ambientes se crean al vincular tu primer dispositivo Eden Air. Empezá agregando uno.</p>
            <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-primary ea-button-buy">
                Conectá tu Eden Air
            </a>
        </section>

    <?php else: ?>

        <!-- ===== Una tarjeta por ambiente ===== -->
        <section class="ea-dev-grid">
            <?php foreach ($ambientes as $a): ?>
                <article class="ea-dev-card">
                    <header class="ea-dev-card-head">
                        <span class="ea-dev-icon" aria-hidden="true"><?= icono($a['tipo_icono']) ?></span>
                        <span class="ea-dev-badge tone-info">
                            <span class="ea-dev-badge-dot"></span><?= esc($a['tipo']) ?>
                        </span>
                    </header>

                    <h3 class="ea-dev-name"><?= esc($a['nombre']) ?></h3>

                    <?php /* Aviso corto: ¿son los números del tipo o los movió el usuario? */ ?>
                    <p class="ea-amb-origen">
                        <?php if ($a['sigue_tipo']): ?>
                            Usa los rangos recomendados para <?= esc($a['tipo']) ?>.
                        <?php else: ?>
                            Rangos ajustados a medida, distintos a los de <?= esc($a['tipo']) ?>.
                        <?php endif; ?>
                    </p>

                    <dl class="ea-dev-meta">
                        <div><dt>Rango temp</dt><dd><?= esc($a['rangos']['temp']) ?></dd></div>
                        <div><dt>Rango humedad</dt><dd><?= esc($a['rangos']['hum']) ?></dd></div>
                        <div><dt>CO₂ máx</dt><dd><?= esc($a['rangos']['co2']) ?></dd></div>
                        <div><dt>Dispositivos</dt><dd><?= esc((string) count($a['devices'])) ?></dd></div>
                    </dl>

                    <?php if ($a['devices']): ?>
                        <ul class="ea-amb-devices">
                            <?php foreach ($a['devices'] as $d): ?>
                                <li>
                                    <span class="ea-amb-dot" aria-hidden="true"></span>
                                    <?= esc($d['name']) ?> <small>· <?= esc($d['tipo']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <footer class="ea-dev-card-foot">
                        <a href="<?= site_url('panel/ambientes/' . $a['id'] . '/editar') ?>" class="ea-button ea-button-secondary ea-button-sm">
                            Editar ambiente
                        </a>
                        <span class="ea-dev-code">tipo, nombre y rangos</span>
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>

    <?php endif; ?>

    <!-- ===== Referencia: qué rango usa cada tipo de espacio =====
         Sale del mismo catálogo que ofrece el formulario de edición
         (EnvironmentPresetService), así que los números no se repiten acá.
         Se muestra siempre: también sirve antes de tener el primer equipo. -->
    <section class="ea-amb-ref">
        <header class="ea-amb-ref-head">
            <h2 class="ea-dev-toolbar-title">Rangos por tipo de espacio</h2>
            <p class="ea-dev-toolbar-sub">
                Los valores recomendados de cada tipo. Elegís uno al editar el ambiente
                y, si preferís otros números, están todos disponibles para cambiar.
            </p>
        </header>

        <ul class="ea-amb-ref-grid">
            <?php foreach ($catalogo as $tipo): ?>
                <?php $enUso = in_array($tipo['clave'], $tiposEnUso, true); ?>
                <li class="ea-amb-ref-card<?= $enUso ? ' is-usado' : '' ?>">
                    <header class="ea-amb-ref-card-head">
                        <span class="ea-dev-icon" aria-hidden="true"><?= icono($tipo['icono']) ?></span>
                        <h3 class="ea-amb-ref-name"><?= esc($tipo['label']) ?></h3>
                        <?php if ($enUso): ?>
                            <span class="ea-dev-badge tone-success">
                                <span class="ea-dev-badge-dot"></span>En uso
                            </span>
                        <?php endif; ?>
                    </header>

                    <p class="ea-amb-ref-desc"><?= esc($tipo['descripcion']) ?></p>

                    <?php if ($tipo['libre']): ?>
                        <p class="ea-amb-ref-libre">
                            Sin rangos fijos: arrancás con
                            <?= esc($tipo['rangos']['temp']) ?>, <?= esc($tipo['rangos']['hum']) ?>
                            y <?= esc($tipo['rangos']['co2']) ?>, y los cambiás por los que quieras.
                        </p>
                    <?php else: ?>
                        <dl class="ea-dev-meta">
                            <div><dt><?= icono('temp', 14) ?> Temperatura</dt><dd><?= esc($tipo['rangos']['temp']) ?></dd></div>
                            <div><dt><?= icono('hum', 14) ?> Humedad</dt><dd><?= esc($tipo['rangos']['hum']) ?></dd></div>
                            <div><dt><?= icono('co2', 14) ?> CO₂ máx</dt><dd><?= esc($tipo['rangos']['co2']) ?></dd></div>
                        </dl>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

<?= $this->endSection() ?>
