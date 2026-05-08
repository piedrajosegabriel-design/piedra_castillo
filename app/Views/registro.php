<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Registro</title>
    <script>
        const temaGuardado = localStorage.getItem('tema');
        if (temaGuardado) {
            document.documentElement.setAttribute('data-theme', temaGuardado);
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="pagina">
<div class="contenedor">
    <header class="encabezado">
        <a href="<?= site_url('/') ?>" class="marca">
            <span class="marca-icono">EA</span>
            <span class="marca-texto">
                <strong>EdenAir</strong>
                <small>Crear cuenta</small>
            </span>
        </a>

        <div class="menu">
            <button type="button" class="boton boton-tema" data-boton-tema>Tema oscuro</button>
            <a href="<?= site_url('login') ?>" class="boton boton-secundario">Login</a>
        </div>
    </header>

    <main class="contenido">
        <section class="seccion seccion-principal">
            <div class="bloque">
                <p class="etiqueta">Registro</p>
                <h1 class="titulo">Crea tu cuenta y prepara el ambiente inicial.</h1>
                <p class="texto">
                    El registro prepara la cuenta del usuario, el espacio inicial
                    y la configuracion necesaria para comenzar a usar el sistema.
                </p>

                <ul class="lista-simple">
                    <li>
                        <strong>Formulario directo</strong>
                        <span>Solo los datos necesarios para empezar sin pasos de mas.</span>
                    </li>
                    <li>
                        <strong>Validaciones utiles</strong>
                        <span>Correo valido, confirmacion de contrasena y clave segura.</span>
                    </li>
                    <li>
                        <strong>Configuracion inicial</strong>
                        <span>Puedes usar un preset o dejar preparado un ambiente personalizado.</span>
                    </li>
                </ul>
            </div>

            <div class="bloque">
                <p class="etiqueta">Alta de usuario</p>
                <h2>Completa tus datos</h2>
                <p class="texto">Elige un ambiente inicial y crea una contrasena segura.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mensaje mensaje-error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php $errores = session()->getFlashdata('errors') ?? []; ?>
                <?php if ($errores): ?>
                    <div class="mensaje mensaje-error">
                        <ul class="lista-puntos">
                            <?php foreach ($errores as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('registro') ?>" method="POST" id="formRegistro" class="formulario">
                    <?= csrf_field() ?>

                    <div class="fila">
                        <div class="campo">
                            <label for="nombre">Nombre completo</label>
                            <input type="text" id="nombre" name="nombre" value="<?= esc(old('nombre')) ?>" placeholder="Tu nombre" autocomplete="name" required>
                        </div>

                        <div class="campo">
                            <label for="email">Correo electronico</label>
                            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="correo@ejemplo.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" value="<?= esc(old('usuario')) ?>" placeholder="usuario.personal" autocomplete="username" required>
                        <p class="nota">Usa letras, numeros, puntos, guiones o guion bajo.</p>
                    </div>

                    <div class="campo">
                        <label for="environment_type">Ambiente inicial</label>
                        <select id="environment_type" name="environment_type" required>
                            <option value="">Selecciona un ambiente</option>
                            <?php foreach (($presets ?? []) as $key => $preset): ?>
                                <option value="<?= esc($key) ?>" <?= old('environment_type') === $key ? 'selected' : '' ?>>
                                    <?= esc($preset['label']) ?> - <?= esc($preset['description']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="bloquePersonalizado" class="bloque-suave oculto">
                        <div class="campo">
                            <label for="custom_name">Nombre del ambiente</label>
                            <input type="text" id="custom_name" name="custom_name" value="<?= esc(old('custom_name')) ?>" placeholder="Ejemplo: Laboratorio de pruebas">
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label for="min_temperature">Temperatura minima (C)</label>
                                <input type="number" step="0.1" id="min_temperature" name="min_temperature" value="<?= esc(old('min_temperature')) ?>">
                            </div>

                            <div class="campo">
                                <label for="max_temperature">Temperatura maxima (C)</label>
                                <input type="number" step="0.1" id="max_temperature" name="max_temperature" value="<?= esc(old('max_temperature')) ?>">
                            </div>
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label for="min_humidity">Humedad minima (%)</label>
                                <input type="number" step="0.1" id="min_humidity" name="min_humidity" value="<?= esc(old('min_humidity')) ?>">
                            </div>

                            <div class="campo">
                                <label for="max_humidity">Humedad maxima (%)</label>
                                <input type="number" step="0.1" id="max_humidity" name="max_humidity" value="<?= esc(old('max_humidity')) ?>">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="max_co2">Limite de CO2 (ppm)</label>
                            <input type="number" id="max_co2" name="max_co2" value="<?= esc(old('max_co2')) ?>">
                            <p class="nota">Si dejas campos vacios, el sistema tomara los valores del preset.</p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="registroPassword">Contrasena</label>
                        <div class="campo-password">
                            <input type="password" id="registroPassword" name="password" placeholder="Crea una contrasena" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="boton boton-secundario boton-bloque" id="verPasswordRegistro">Mostrar</button>
                        </div>
                        <p class="nota">Debe tener al menos 8 caracteres, una mayuscula, una minuscula y un numero.</p>
                    </div>

                    <div class="medidor">
                        <div class="medidor-barra">
                            <span id="fuerzaBarra"></span>
                        </div>
                        <p id="fuerzaTexto" class="nota">Seguridad pendiente.</p>
                    </div>

                    <div class="campo">
                        <label for="confirmPassword">Confirmar contrasena</label>
                        <input type="password" id="confirmPassword" name="password_confirm" placeholder="Repite la contrasena" autocomplete="new-password" minlength="8" required>
                    </div>

                    <p id="coincideTexto" class="nota">Esperando confirmacion de contrasena.</p>

                    <button type="submit" class="boton boton-bloque" id="botonRegistro">Crear cuenta</button>
                    <a href="<?= site_url('login') ?>" class="enlace-centro">Ya tengo una cuenta</a>
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/registro.js') ?>"></script>
</body>
</html>
