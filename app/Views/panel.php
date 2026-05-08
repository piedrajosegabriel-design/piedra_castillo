<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Panel</title>
    <script>
        const temaGuardado = localStorage.getItem('tema');
        if (temaGuardado) {
            document.documentElement.setAttribute('data-theme', temaGuardado);
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="pagina">
<?php
    $modoManual = ($panel['state']['modo'] ?? 'automatic') === 'manual';
    $ultima = $panel['latest_measurement'] ?? null;
    $errores = session()->getFlashdata('errors') ?? [];
    $graficos = $panel['charts'] ?? [];
?>
<div class="contenedor">
    <header class="encabezado">
        <a href="<?= site_url('/') ?>" class="marca">
            <span class="marca-icono">EA</span>
            <span class="marca-texto">
                <strong>EdenAir</strong>
                <small>Panel del sistema</small>
            </span>
        </a>

        <div class="menu">
            <button type="button" class="boton boton-tema" data-boton-tema>Tema oscuro</button>
            <span class="texto-menu"><?= esc($panel['user']['nombre']) ?></span>
            <a href="<?= site_url('logout') ?>" class="boton boton-secundario">Cerrar sesion</a>
        </div>
    </header>

    <main class="contenido">
        <section id="resumen" class="seccion seccion-principal" data-seccion-panel>
            <div class="bloque">
                <p class="etiqueta">Panel</p>
                <h1 class="titulo">Resumen del ambiente y del dispositivo.</h1>
                <p class="texto">
                    El panel ahora esta ordenado por apartados para que puedas ir
                    directo a resumen, graficos, control, historial o API.
                </p>
            </div>

            <div class="bloque bloque-color">
                <p class="etiqueta">Estado actual</p>
                <div class="tarjetas-resumen">
                    <div class="tarjeta-resumen">
                        <span>Espacio</span>
                        <strong><?= esc($panel['space']['nombre']) ?></strong>
                        <small><?= esc($panel['space']['resumen']) ?></small>
                    </div>

                    <div class="tarjeta-resumen">
                        <span>Dispositivo</span>
                        <strong><?= esc($panel['device']['nombre']) ?></strong>
                        <small><?= esc($panel['device']['uid']) ?></small>
                    </div>

                    <div class="tarjeta-resumen">
                        <span>Modo</span>
                        <strong><?= esc($panel['state']['modo_label']) ?></strong>
                        <small><?= esc($panel['state']['detalle']) ?></small>
                    </div>

                    <div class="tarjeta-resumen">
                        <span>Mediciones</span>
                        <strong><?= esc((string) $panel['resumen']['mediciones']) ?></strong>
                        <small>Ultima lectura: <?= esc($panel['resumen']['ultima_lectura']) ?></small>
                    </div>
                </div>
            </div>
        </section>

        <nav class="menu-panel" data-menu-panel>
            <a href="#resumen" class="item-menu-panel activo">Resumen</a>
            <a href="#metricas" class="item-menu-panel">Metricas</a>
            <a href="#graficos" class="item-menu-panel">Graficos</a>
            <a href="#medicion" class="item-menu-panel">Medicion</a>
            <a href="#control" class="item-menu-panel">Control</a>
            <a href="#alertas" class="item-menu-panel">Alertas</a>
            <a href="#historial" class="item-menu-panel">Historial</a>
            <a href="#api" class="item-menu-panel">API</a>
        </nav>

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

        <section id="metricas" class="panel-seccion" data-seccion-panel>
            <div class="panel-seccion-cabecera">
                <div>
                    <p class="etiqueta">Metricas</p>
                    <h2>Estado actual del ambiente</h2>
                </div>
                <p class="texto">Este bloque resume rapidamente si temperatura, humedad, CO2 y calidad del aire estan dentro de lo esperado.</p>
            </div>

            <div class="rejilla-metricas">
                <?php if ($panel['metrics'] !== []): ?>
                    <?php foreach ($panel['metrics'] as $metrica): ?>
                        <article class="tarjeta-metrica">
                            <div class="metrica-cabecera">
                                <span><?= esc($metrica['titulo']) ?></span>
                                <small class="estado estado-<?= esc($metrica['tono']) ?>"><?= esc($metrica['estado']) ?></small>
                            </div>
                            <strong><?= esc($metrica['valor']) ?></strong>
                            <p class="texto"><?= esc($metrica['detalle']) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <article class="tarjeta sin-datos">
                        <h2>Aun no hay metricas</h2>
                        <p class="texto">Guarda una medicion para empezar a ver el comportamiento del ambiente.</p>
                    </article>
                <?php endif; ?>
            </div>
        </section>

        <section id="graficos" class="tarjeta panel-seccion" data-seccion-panel>
            <div class="panel-seccion-cabecera">
                <div>
                    <p class="etiqueta">Graficos</p>
                    <h2>Lecturas recientes</h2>
                </div>
                <p class="texto">Cada grafico toma las ultimas mediciones para que sea mas facil ver cambios y tendencias.</p>
            </div>

            <?php if ($graficos !== []): ?>
                <div class="rejilla-graficos">
                    <?php foreach ($graficos as $grafico): ?>
                        <article class="grafico">
                            <div class="grafico-cabecera">
                                <div>
                                    <strong><?= esc($grafico['titulo']) ?></strong>
                                    <p class="texto"><?= esc($grafico['detalle']) ?></p>
                                </div>
                                <span class="estado estado-<?= esc($grafico['tono']) ?>"><?= esc($grafico['actual']) ?></span>
                            </div>

                            <div class="grafico-barras">
                                <?php foreach ($grafico['puntos'] as $punto): ?>
                                    <div class="grafico-barra">
                                        <small class="grafico-valor"><?= esc($punto['valor']) ?></small>
                                        <div class="grafico-columna">
                                            <span class="grafico-relleno grafico-<?= esc($punto['tono']) ?>" style="height: <?= esc((string) $punto['porcentaje']) ?>%;"></span>
                                        </div>
                                        <small class="grafico-etiqueta"><?= esc($punto['etiqueta']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <p class="nota"><?= esc($grafico['rango']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sin-datos">
                    <p class="texto">Aun no hay suficientes mediciones para mostrar graficos.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel-columnas">
            <article id="medicion" class="tarjeta" data-seccion-panel>
                <p class="etiqueta">Medicion</p>
                <h2>Registrar nueva lectura</h2>
                <p class="texto">Puedes cargar una medicion manual para actualizar el estado del espacio.</p>

                <form action="<?= site_url('panel/medicion') ?>" method="POST" class="formulario">
                    <?= csrf_field() ?>

                    <div class="fila">
                        <div class="campo">
                            <label for="temperature">Temperatura (C)</label>
                            <input type="number" step="0.1" id="temperature" name="temperature" value="<?= esc(old('temperature')) ?>" placeholder="Ej: 24.5">
                        </div>

                        <div class="campo">
                            <label for="humidity">Humedad (%)</label>
                            <input type="number" step="0.1" id="humidity" name="humidity" value="<?= esc(old('humidity')) ?>" placeholder="Ej: 58">
                        </div>
                    </div>

                    <div class="fila">
                        <div class="campo">
                            <label for="co2_ppm">CO2 (ppm)</label>
                            <input type="number" id="co2_ppm" name="co2_ppm" value="<?= esc(old('co2_ppm')) ?>" placeholder="Ej: 920">
                        </div>

                        <div class="campo">
                            <label for="air_quality_index">Calidad del aire (0-100)</label>
                            <input type="number" id="air_quality_index" name="air_quality_index" value="<?= esc(old('air_quality_index')) ?>" placeholder="Ej: 72">
                        </div>
                    </div>

                    <div class="campo">
                        <label for="notes">Notas</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Detalle breve sobre la medicion"><?= esc(old('notes')) ?></textarea>
                    </div>

                    <button type="submit" class="boton boton-bloque">Guardar medicion</button>
                </form>
            </article>

            <article id="control" class="tarjeta" data-seccion-panel>
                <p class="etiqueta">Control</p>
                <h2>Modo y actuadores</h2>
                <p class="texto">
                    El modo automatico deja que el sistema decida. El modo manual te permite controlar los actuadores.
                </p>

                <div class="grupo-acciones">
                    <form action="<?= site_url('panel/modo') ?>" method="POST" class="formulario-linea">
                        <?= csrf_field() ?>
                        <input type="hidden" name="mode" value="automatic">
                        <button type="submit" class="boton <?= $modoManual ? 'boton-secundario' : '' ?>">Modo automatico</button>
                    </form>

                    <form action="<?= site_url('panel/modo') ?>" method="POST" class="formulario-linea">
                        <?= csrf_field() ?>
                        <input type="hidden" name="mode" value="manual">
                        <button type="submit" class="boton <?= $modoManual ? '' : 'boton-secundario' ?>">Modo manual</button>
                    </form>
                </div>

                <div class="lista-actuadores">
                    <?php foreach ($panel['actuators'] as $actuador): ?>
                        <div class="bloque-suave">
                            <div class="actuador-cabecera">
                                <strong><?= esc($actuador['titulo']) ?></strong>
                                <span class="estado estado-<?= esc($actuador['tono']) ?>"><?= esc($actuador['estado']) ?></span>
                            </div>
                            <p class="texto"><?= esc($actuador['detalle']) ?></p>

                            <div class="grupo-acciones">
                                <form action="<?= site_url('panel/actuador') ?>" method="POST" class="formulario-linea">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="actuator" value="<?= esc($actuador['clave']) ?>">
                                    <input type="hidden" name="value" value="on">
                                    <button type="submit" class="boton" <?= $modoManual ? '' : 'disabled' ?>>Encender</button>
                                </form>

                                <form action="<?= site_url('panel/actuador') ?>" method="POST" class="formulario-linea">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="actuator" value="<?= esc($actuador['clave']) ?>">
                                    <input type="hidden" name="value" value="off">
                                    <button type="submit" class="boton boton-secundario" <?= $modoManual ? '' : 'disabled' ?>>Apagar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel-columnas">
            <article id="alertas" class="tarjeta" data-seccion-panel>
                <p class="etiqueta">Alertas</p>
                <h2>Resumen del ambiente</h2>
                <div class="lista-alertas">
                    <?php foreach ($panel['alerts'] as $alerta): ?>
                        <div class="alerta alerta-<?= esc($alerta['tono']) ?>">
                            <strong><?= esc($alerta['titulo']) ?></strong>
                            <p><?= esc($alerta['texto']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="tarjeta">
                <p class="etiqueta">Ultima medicion</p>
                <h2>Lectura mas reciente</h2>
                <?php if ($ultima): ?>
                    <div class="bloque-suave">
                        <div class="lista-simple">
                            <div>
                                <strong>Fecha</strong>
                                <span><?= esc($ultima['fecha']) ?></span>
                            </div>
                            <div>
                                <strong>Temperatura</strong>
                                <span><?= esc($ultima['temperatura']) ?></span>
                            </div>
                            <div>
                                <strong>Humedad</strong>
                                <span><?= esc($ultima['humedad']) ?></span>
                            </div>
                            <div>
                                <strong>CO2</strong>
                                <span><?= esc($ultima['co2']) ?></span>
                            </div>
                            <div>
                                <strong>Calidad</strong>
                                <span><?= esc($ultima['aire']) ?></span>
                            </div>
                            <div>
                                <strong>Origen</strong>
                                <span><?= esc($ultima['origen']) ?></span>
                            </div>
                        </div>
                        <?php if ($ultima['notas'] !== ''): ?>
                            <p class="texto nota-bloque"><?= esc($ultima['notas']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="sin-datos">
                        <p class="texto">Aun no hay una medicion registrada.</p>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <section id="historial" class="tarjeta" data-seccion-panel>
            <p class="etiqueta">Historial</p>
            <h2>Mediciones recientes</h2>

            <?php if ($panel['history'] !== []): ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Temperatura</th>
                                <th>Humedad</th>
                                <th>CO2</th>
                                <th>Calidad</th>
                                <th>Origen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($panel['history'] as $fila): ?>
                                <tr>
                                    <td><?= esc($fila['fecha']) ?></td>
                                    <td><?= esc($fila['temperatura']) ?></td>
                                    <td><?= esc($fila['humedad']) ?></td>
                                    <td><?= esc($fila['co2']) ?></td>
                                    <td><?= esc($fila['aire']) ?></td>
                                    <td><?= esc($fila['origen']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="sin-datos">
                    <p class="texto">Todavia no hay historial para mostrar.</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="api" class="tarjeta" data-seccion-panel>
            <p class="etiqueta">API del dispositivo</p>
            <h2>Para que sirve y donde esta</h2>
            <p class="texto">
                Esta API permite que un dispositivo o simulador externo envie mediciones,
                consulte comandos pendientes y marque acciones como ejecutadas.
            </p>

            <div class="rejilla rejilla-dos">
                <div class="bloque-suave">
                    <strong>Archivos principales</strong>
                    <p class="texto ruta-archivo"><?= esc($panel['api']['routes_file']) ?></p>
                    <p class="texto ruta-archivo"><?= esc($panel['api']['controller_file']) ?></p>
                </div>

                <div class="bloque-suave">
                    <strong>Datos necesarios</strong>
                    <p class="texto">UID: <?= esc($panel['device']['uid']) ?></p>
                    <p class="texto">Token: <?= esc($panel['device']['token']) ?></p>
                    <p class="texto">Ultimo envio: <?= esc($panel['device']['ultimo_envio']) ?></p>
                    <p class="texto">Ultima consulta: <?= esc($panel['device']['ultima_consulta']) ?></p>
                </div>
            </div>

            <div class="lista-api">
                <div class="api-item">
                    <strong>POST de mediciones</strong>
                    <code><?= esc($panel['api']['measurements_url']) ?></code>
                    <p>Recibe temperatura, humedad, CO2 y calidad del aire desde el dispositivo o simulador.</p>
                </div>

                <div class="api-item">
                    <strong>GET de comandos pendientes</strong>
                    <code><?= esc($panel['api']['commands_url']) ?></code>
                    <p>Entrega las acciones que el dispositivo deberia revisar o ejecutar.</p>
                </div>

                <div class="api-item">
                    <strong>POST para marcar ejecutado</strong>
                    <code><?= esc($panel['api']['executed_url']) ?></code>
                    <p>Se usa cuando el dispositivo ya aplico un comando y quiere informar ese cambio.</p>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/panel.js') ?>"></script>
</body>
</html>
