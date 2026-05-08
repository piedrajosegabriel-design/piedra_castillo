<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Inicio</title>
    <script>
        const temaGuardado = localStorage.getItem('tema');
        if (temaGuardado) {
            document.documentElement.setAttribute('data-theme', temaGuardado);
        }
    </script>
    <link rel="stylesheet" href="<?= base_url('CSS/todo.css') ?>">
</head>
<body class="pagina">
<?php $conSesion = (bool) session()->get('user_id'); ?>
<div class="contenedor">
    <header class="encabezado">
        <a href="<?= site_url('/') ?>" class="marca">
            <span class="marca-icono">EA</span>
            <span class="marca-texto">
                <strong>EdenAir</strong>
                <small>Monitoreo ambiental</small>
            </span>
        </a>

        <div class="menu">
            <button type="button" class="boton boton-tema" data-boton-tema>Tema oscuro</button>

            <?php if ($conSesion): ?>
                <a href="<?= site_url('panel') ?>" class="boton boton-secundario">Ir al panel</a>
                <a href="<?= site_url('logout') ?>" class="boton">Cerrar sesion</a>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="boton boton-secundario">Login</a>
                <a href="<?= site_url('registro') ?>" class="boton">Registro</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="contenido">
        <section class="seccion seccion-principal">
            <div class="bloque">
                <p class="etiqueta">Vista principal</p>
                <h1 class="titulo">Monitorea cada espacio con una vista clara y ordenada.</h1>
                <p class="texto">
                    EdenAir presenta el servicio, centraliza el acceso de usuarios
                    y ofrece un panel comodo para seguir el estado del ambiente.
                </p>

                <div class="acciones">
                    <?php if ($conSesion): ?>
                        <a href="<?= site_url('panel') ?>" class="boton">Abrir panel</a>
                    <?php else: ?>
                        <a href="<?= site_url('registro') ?>" class="boton">Crear cuenta</a>
                        <a href="<?= site_url('login') ?>" class="boton boton-secundario">Ya tengo cuenta</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bloque bloque-color">
                <p class="etiqueta">Lo principal</p>
                <ul class="lista-simple">
                    <li>
                        <strong>Informacion del servicio</strong>
                        <span>Describe el monitoreo ambiental desde el primer ingreso.</span>
                    </li>
                    <li>
                        <strong>Acceso directo</strong>
                        <span>El usuario puede ir a login o registro sin pasos de mas.</span>
                    </li>
                    <li>
                        <strong>Panel organizado</strong>
                        <span>La informacion queda distribuida de forma comoda y facil de recorrer.</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="rejilla">
            <article class="tarjeta">
                <p class="etiqueta">Producto</p>
                <h2>Monitoreo del espacio</h2>
                <p class="texto">
                    El sistema organiza temperatura, humedad, CO2 y calidad del aire
                    en un panel pensado para una lectura rapida.
                </p>
            </article>

            <article class="tarjeta">
                <p class="etiqueta">Acceso</p>
                <h2>Usuarios registrados</h2>
                <p class="texto">
                    Cada usuario crea su cuenta, inicia sesion y administra su propio
                    ambiente desde el panel principal.
                </p>
            </article>

            <article class="tarjeta">
                <p class="etiqueta">Interfaz</p>
                <h2>Simple y comoda</h2>
                <p class="texto">
                    La interfaz combina color, orden y contraste para verse completa
                    sin cargar demasiado la pagina.
                </p>
            </article>
        </section>

        <section class="rejilla rejilla-dos">
            <article class="tarjeta">
                <p class="etiqueta">Incluye</p>
                <ul class="lista-puntos">
                    <li>Vista principal con informacion del sistema.</li>
                    <li>Login para usuarios registrados.</li>
                    <li>Registro con validaciones basicas y contrasena segura.</li>
                    <li>Panel con resumen, alertas, historial y explicacion de API.</li>
                </ul>
            </article>

            <article class="tarjeta">
                <p class="etiqueta">Pensado para seguir</p>
                <p class="texto">
                    El sistema queda listo para seguir creciendo sin perder el orden
                    general de la pagina.
                </p>
            </article>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
</body>
</html>
