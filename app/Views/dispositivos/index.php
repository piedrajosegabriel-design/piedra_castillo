<?php
/**
 * MIS DISPOSITIVOS — todos los equipos vinculados a la cuenta.
 * Ruta: /panel/dispositivos · Controlador: DispositivosController::index
 * Recibe: $dispositivos (array preparado por DevicePairingService::listarDeUsuario)
 */
$dispositivos = $dispositivos ?? [];
$total        = count($dispositivos);

$this->setData([
    'tituloPagina'    => 'EdenAir · Mis dispositivos',
    'descripcion'     => 'Administrá todos los dispositivos Eden Air vinculados a tu cuenta: estado, ambiente y alta de nuevos equipos.',
    'sidebarActivo'   => 'dispositivos',
    'cantidadEquipos' => $total,
    'cabecera'        => [
        'titulo' => 'Mis dispositivos',
        'bajada' => $total . ($total === 1 ? ' dispositivo vinculado' : ' dispositivos vinculados'),
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== Encabezado de la sección + acción principal ===== -->
    <section class="ea-dev-toolbar" aria-label="Acciones de dispositivos">
        <div>
            <h2 class="ea-dev-toolbar-title">Equipos vinculados</h2>
            <p class="ea-dev-toolbar-sub">
                Administrá el estado y el ambiente de cada Eden Air.
                Una misma cuenta puede tener varios dispositivos.
            </p>
        </div>
        <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-primary ea-button-buy">
            <?= icono('mas', 16) ?>
            Conectar dispositivo
        </a>
    </section>

    <?php if ($total === 0): ?>

        <!-- ===== Sin dispositivos todavía ===== -->
        <section class="ea-dev-empty">
            <span class="ea-dev-empty-orb" aria-hidden="true"></span>
            <h3>Todavía no conectaste ningún dispositivo</h3>
            <p>Enchufá tu Eden Air, apretá Conectar y escaneá el QR con el celular. Listo: empezás a monitorear tu ambiente.</p>
            <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-primary ea-button-buy">
                Conectá tu Eden Air
            </a>
            <p class="ea-dev-empty-hint">No hace falta ningún código: la vinculación es automática.</p>
        </section>

    <?php else: ?>

        <!-- ===== Una tarjeta por dispositivo + la de "agregar otro" ===== -->
        <section class="ea-dev-grid" aria-label="Listado de dispositivos">
            <?php foreach ($dispositivos as $d): ?>
                <article class="ea-dev-card">
                    <header class="ea-dev-card-head">
                        <span class="ea-dev-icon" aria-hidden="true"><?= icono('dispositivo') ?></span>
                        <span class="ea-dev-badge tone-<?= esc($d['estado_tono']) ?>">
                            <span class="ea-dev-badge-dot"></span><?= esc($d['estado_label']) ?>
                        </span>
                    </header>

                    <h3 class="ea-dev-name"><?= esc($d['nombre']) ?></h3>

                    <dl class="ea-dev-meta">
                        <div><dt>Tipo</dt><dd><?= esc($d['tipo']) ?></dd></div>
                        <div><dt>Ambiente</dt><dd><?= esc($d['espacio']) ?></dd></div>
                        <div><dt>ID técnico</dt><dd class="ea-mono"><?= esc($d['uid']) ?></dd></div>
                        <?php if (! empty($d['mac'])): ?>
                            <div><dt>MAC <span class="ea-dev-tag">técnica</span></dt><dd class="ea-mono"><?= esc($d['mac']) ?></dd></div>
                        <?php endif; ?>
                    </dl>

                    <?php if (! empty($d['notas'])): ?>
                        <p class="ea-dev-notes"><?= esc($d['notas']) ?></p>
                    <?php endif; ?>

                    <footer class="ea-dev-card-foot">
                        <a href="<?= site_url('panel') ?>" class="ea-button ea-button-secondary ea-button-sm">Ver panel</a>
                        <?php if (! empty($d['visto'])): ?>
                            <span class="ea-dev-code" title="Última vez que el equipo se comunicó">
                                · visto <?= esc(date('d/m H:i', strtotime((string) $d['visto']))) ?>
                            </span>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>

            <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-dev-card ea-dev-card-add">
                <span class="ea-dev-add-plus" aria-hidden="true">+</span>
                <span class="ea-dev-add-label">Conectar otro dispositivo</span>
                <span class="ea-dev-add-hint">Escaneá el QR y se vincula solo</span>
            </a>
        </section>

    <?php endif; ?>

    <!-- ===== Ayuda: cómo se conecta un equipo ===== -->
    <section class="ea-dev-info" aria-label="Cómo funciona la vinculación">
        <h3>¿Cómo se conecta un dispositivo?</h3>
        <ol class="ea-dev-steps">
            <li><span>1</span> Enchufá tu dispositivo Eden Air.</li>
            <li><span>2</span> Apretá “Conectar dispositivo”: aparece un QR generado en el momento.</li>
            <li><span>3</span> Escanealo con la cámara del celular.</li>
            <li><span>4</span> Elegí el WiFi de tu casa en la pantalla que se abre sola.</li>
            <li><span>5</span> El equipo se conecta y aparece acá solo, sin códigos.</li>
        </ol>
        <p class="ea-dev-info-note">
            Podés administrar varios dispositivos desde un mismo dashboard.
            Luego vas a poder configurar automatizaciones ambientales por espacio.
        </p>
    </section>

<?= $this->endSection() ?>
