<?php
$errores = session()->getFlashdata('errors') ?? [];
$presetSeleccionado = old('environment_type');

if (! is_string($presetSeleccionado) || $presetSeleccionado === '') {
    $presetSeleccionado = 'hogar';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Elegir ambiente</title>
    <script>
        const temaGuardado = localStorage.getItem('tema');
        if (temaGuardado) {
            document.documentElement.setAttribute('data-theme', temaGuardado);
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="pagina pagina-ambiente">
<div class="contenedor">
    <header class="encabezado">
        <a href="<?= site_url('/') ?>" class="marca">
            <span class="marca-icono">EA</span>
            <span class="marca-texto">
                <strong>EdenAir</strong>
                <small>Configuración inicial</small>
            </span>
        </a>

        <div class="menu">
            <label class="switch">
              <input id="input" type="checkbox" aria-label="Cambiar tema" />
              <div class="slider round">
                <div class="sun-moon">
                  <svg id="moon-dot-1" class="moon-dot" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="moon-dot-2" class="moon-dot" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="moon-dot-3" class="moon-dot" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="light-ray-1" class="light-ray" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="light-ray-2" class="light-ray" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="light-ray-3" class="light-ray" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>

                  <svg id="cloud-1" class="cloud-dark" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="cloud-2" class="cloud-dark" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="cloud-3" class="cloud-dark" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="cloud-4" class="cloud-light" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="cloud-5" class="cloud-light" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                  <svg id="cloud-6" class="cloud-light" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"></circle>
                  </svg>
                </div>
                <div class="stars">
                  <svg id="star-1" class="star" viewBox="0 0 20 20">
                    <path
                      d="M 0 10 C 10 10,10 10 ,0 10 C 10 10 , 10 10 , 10 20 C 10 10 , 10 10 , 20 10 C 10 10 , 10 10 , 10 0 C 10 10,10 10 ,0 10 Z"
                    ></path>
                  </svg>
                  <svg id="star-2" class="star" viewBox="0 0 20 20">
                    <path
                      d="M 0 10 C 10 10,10 10 ,0 10 C 10 10 , 10 10 , 10 20 C 10 10 , 10 10 , 20 10 C 10 10 , 10 10 , 10 0 C 10 10,10 10 ,0 10 Z"
                    ></path>
                  </svg>
                  <svg id="star-3" class="star" viewBox="0 0 20 20">
                    <path
                      d="M 0 10 C 10 10,10 10 ,0 10 C 10 10 , 10 10 , 10 20 C 10 10 , 10 10 , 20 10 C 10 10 , 10 10 , 10 0 C 10 10,10 10 ,0 10 Z"
                    ></path>
                  </svg>
                  <svg id="star-4" class="star" viewBox="0 0 20 20">
                    <path
                      d="M 0 10 C 10 10,10 10 ,0 10 C 10 10 , 10 10 , 10 20 C 10 10 , 10 10 , 20 10 C 10 10 , 10 10 , 10 0 C 10 10,10 10 ,0 10 Z"
                    ></path>
                  </svg>
                </div>
              </div>
            </label>
            <a href="<?= site_url('logout') ?>" class="boton boton-secundario">Cerrar sesión</a>
        </div>
    </header>

    <main class="contenido">
        <section class="seccion seccion-ambiente">
            <div class="bloque bloque-ambiente-simple" id="panelAmbiente" data-preset="<?= esc($presetSeleccionado) ?>">
                <div class="cabecera-ambiente-simple">
                    <p class="etiqueta">Configuración inicial</p>
                    <h1 class="titulo">Elige el tipo de ambiente.</h1>
                    <p class="texto">
                        Selecciona una opción y continúa. Si eliges
                        <strong>personalizable</strong>, podrás ajustar tus propios límites.
                    </p>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mensaje mensaje-exito"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mensaje mensaje-error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php if ($errores): ?>
                    <div class="mensaje mensaje-error">
                        <ul class="lista-puntos">
                            <?php foreach ($errores as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('panel/ambiente') ?>" method="POST" id="formAmbiente" class="formulario formulario-ambiente-simple">
                    <?= csrf_field() ?>

                    <div class="mosaico-ambiente">
                        <?php foreach (($presets ?? []) as $key => $preset): ?>
                            <?php $resumen = sprintf(
                                '%.1f-%.1f C | %.0f-%.0f %% | %d ppm',
                                (float) $preset['min_temperature'],
                                (float) $preset['max_temperature'],
                                (float) $preset['min_humidity'],
                                (float) $preset['max_humidity'],
                                (int) $preset['max_co2']
                            ); ?>
                            <label class="tarjeta-ambiente-selector<?= $presetSeleccionado === $key ? ' activa' : '' ?>" data-preset-card>
                                <input
                                    type="radio"
                                    name="environment_type"
                                    value="<?= esc($key) ?>"
                                    <?= $presetSeleccionado === $key ? 'checked' : '' ?>
                                    required
                                >

                                <span class="tarjeta-ambiente-contenido">
                                    <span class="tarjeta-ambiente-superior">
                                        <span class="tarjeta-ambiente-codigo"><?= esc(strtoupper(substr($preset['label'], 0, 2))) ?></span>
                                        <span class="tarjeta-ambiente-check"></span>
                                    </span>
                                    <strong><?= esc($preset['label']) ?></strong>
                                    <small><?= esc($preset['description']) ?></small>
                                    <span class="tarjeta-ambiente-rango"><?= esc($resumen) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="resumen-ambiente" id="resumenAmbiente">
                        <div class="resumen-ambiente-cabecera">
                            <div>
                                <p class="etiqueta">Selección actual</p>
                                <h2 id="previewNombre">Hogar</h2>
                            </div>
                            <span class="estado estado-info" id="previewEstado">Preset listo</span>
                        </div>

                        <p class="texto" id="previewDescripcion">Balance general para convivencia diaria.</p>

                        <div class="resumen-ambiente-datos">
                            <div class="dato-ambiente">
                                <small>Temperatura</small>
                                <strong id="previewTemperatura">20.0 a 26.0 C</strong>
                            </div>

                            <div class="dato-ambiente">
                                <small>Humedad</small>
                                <strong id="previewHumedad">35 a 60 %</strong>
                            </div>

                            <div class="dato-ambiente">
                                <small>CO2 límite</small>
                                <strong id="previewCo2">1000 ppm</strong>
                            </div>
                        </div>
                    </div>

                    <div id="bloquePersonalizado" class="bloque-suave bloque-personalizable<?= $presetSeleccionado === 'personalizable' ? '' : ' oculto' ?>">
                        <div class="campo">
                            <label for="custom_name">Nombre del ambiente</label>
                            <input type="text" id="custom_name" name="custom_name" value="<?= esc(old('custom_name')) ?>" placeholder="Ejemplo: Laboratorio de pruebas">
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label for="min_temperature">Temperatura mínima (C)</label>
                                <input type="number" step="0.1" id="min_temperature" name="min_temperature" value="<?= esc(old('min_temperature')) ?>">
                            </div>

                            <div class="campo">
                                <label for="max_temperature">Temperatura máxima (C)</label>
                                <input type="number" step="0.1" id="max_temperature" name="max_temperature" value="<?= esc(old('max_temperature')) ?>">
                            </div>
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label for="min_humidity">Humedad mínima (%)</label>
                                <input type="number" step="0.1" id="min_humidity" name="min_humidity" value="<?= esc(old('min_humidity')) ?>">
                            </div>

                            <div class="campo">
                                <label for="max_humidity">Humedad máxima (%)</label>
                                <input type="number" step="0.1" id="max_humidity" name="max_humidity" value="<?= esc(old('max_humidity')) ?>">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="max_co2">Límite de CO2 (ppm)</label>
                            <input type="number" id="max_co2" name="max_co2" value="<?= esc(old('max_co2')) ?>">
                            <p class="nota">Si dejas campos vacíos, se usarán los valores base del preset personalizable.</p>
                        </div>
                    </div>

                    <div class="acciones-ambiente">
                        <p class="nota">Al continuar se crea el espacio, se prepara la simulación inicial y se habilita el panel.</p>
                        <button type="submit" class="boton boton-bloque" id="botonAmbiente">Continuar al panel</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<script id="presetData" type="application/json"><?= json_encode($presets ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/ambiente.js') ?>"></script>
</body>
</html>
