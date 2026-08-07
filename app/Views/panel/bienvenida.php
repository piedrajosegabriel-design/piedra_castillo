<?php
/**
 * BIENVENIDA — lo primero que ve una cuenta que todavía no tiene dispositivos.
 * Ruta: /panel (cuando no hay equipos) · Controlador: PanelController::index
 * Recibe: $usuario
 *
 * Dos caminos: conectar el equipo que ya tiene, o comprar uno.
 */
$usuario = isset($usuario) && is_array($usuario) ? $usuario : [];
$nombre  = trim((string) ($usuario['nombre'] ?? '')) ?: 'usuario';

$this->setData([
    'tituloPagina'    => 'Eden Air · Bienvenido',
    'descripcion'     => 'Bienvenido a Eden Air. Conectá tus dispositivos, organizalos por ambiente y visualizá el estado de tus espacios en tiempo real.',
    'sidebarActivo'   => 'inicio',
    'cantidadEquipos' => 0,
    'claseContenido'  => 'ea-welcome',
    'cabecera'        => [
        'titulo' => 'Inicio',
        'bajada' => 'Configurá tu primer dispositivo Eden Air',
    ],
]);
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('contenido') ?>

    <!-- ===== Saludo ===== -->
    <section class="ea-welcome-hero" aria-labelledby="ea-welcome-title">
        <h2 id="ea-welcome-title" class="ea-welcome-title">
            Bienvenido a <em>Eden&nbsp;Air</em>, <?= esc($nombre) ?>.
        </h2>
        <p class="ea-welcome-lede">
            Tu cuenta está lista. Falta un solo paso: vincular tu dispositivo
            para empezar a ver el aire de tu espacio en tiempo real.
        </p>
    </section>

    <!-- ===== Los dos caminos posibles ===== -->
    <section class="ea-welcome-actions" aria-label="Cómo empezar">
        <div class="ea-welcome-grid">

            <article class="ea-welcome-card ea-welcome-card--primary">
                <header class="ea-welcome-card-head">
                    <span class="ea-welcome-card-tag">Recomendado</span>
                    <span class="ea-welcome-card-icon" aria-hidden="true"><?= icono('dispositivo-ok', 22) ?></span>
                </header>
                <h4>Conectar mi primer dispositivo</h4>
                <p>Enchufá tu Eden Air, apretá Conectar y escaneá el QR con el celular. Se vincula solo.</p>
                <a href="<?= site_url('panel/dispositivos/conectar') ?>" class="ea-button ea-button-primary ea-button-buy ea-button-block">
                    Conectá tu Eden Air
                </a>
            </article>

            <article class="ea-welcome-card">
                <header class="ea-welcome-card-head">
                    <span class="ea-welcome-card-tag ea-welcome-card-tag--neutral">Plan único</span>
                    <span class="ea-welcome-card-icon" aria-hidden="true"><?= icono('carrito', 22) ?></span>
                </header>
                <h4>Comprar Eden Air</h4>
                <p>Dispositivo, dashboard y configuración por ambiente. Todo incluido.</p>
                <a href="<?= site_url('panel/compra') ?>" class="ea-button ea-button-ghost ea-button-block">
                    Ver plan / Comprar
                </a>
            </article>

        </div>

        <p class="ea-welcome-help">
            <?= icono('ayuda', 14) ?>
            No hace falta ningún código ni buscar nada en la caja: el QR se genera
            en el momento y el equipo se da de alta solo.
        </p>
    </section>

<?= $this->endSection() ?>
