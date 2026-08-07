<?php
/**
 * AMBIENTES — listado de los espacios físicos del usuario.
 * Ruta: /panel/ambientes · Controlador: AmbientesController::index
 * Recibe: $ambientes (array preparado por el controlador)
 */
$ambientes = $ambientes ?? [];

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
                Cada ambiente representa un espacio físico (dormitorio, aula, oficina…).
                Podés ajustar sus rangos de confort y ver los dispositivos que tiene asignados.
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
                        <span class="ea-dev-icon" aria-hidden="true"><?= icono('casa') ?></span>
                        <span class="ea-dev-badge tone-info">
                            <span class="ea-dev-badge-dot"></span><?= esc($a['tipo']) ?>
                        </span>
                    </header>

                    <h3 class="ea-dev-name"><?= esc($a['nombre']) ?></h3>

                    <dl class="ea-dev-meta">
                        <div><dt>Rango temp</dt><dd><?= esc($a['rango_temp']) ?></dd></div>
                        <div><dt>Rango humedad</dt><dd><?= esc($a['rango_hum']) ?></dd></div>
                        <div><dt>CO₂ máx</dt><dd><?= esc((string) $a['max_co2']) ?> ppm</dd></div>
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
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>

    <?php endif; ?>

<?= $this->endSection() ?>
