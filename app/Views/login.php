<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EdenAir | Login</title>
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
                <small>Ingreso al sistema</small>
            </span>
        </a>

        <div class="menu">
            <label class="switch">
              <input id="input" type="checkbox" checked="darkTheme" />
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
            <a href="<?= site_url('registro') ?>" class="boton boton-secundario">Registro</a>
        </div>
    </header>

    <main class="contenido">
        <section class="seccion seccion-principal">
            <div class="bloque">
                <p class="etiqueta">Login</p>
                <h1 class="titulo">Accede a tu panel de monitoreo.</h1>
                <p class="texto">
                    El login permite entrar con usuario o correo y abrir el panel
                    principal del sistema con una autenticacion segura. Si es tu
                    primer ingreso, luego veras una pantalla interactiva para
                    elegir el ambiente.
                </p>

                <ul class="lista-simple">
                    <li>
                        <strong>Autenticacion segura</strong>
                        <span>La contrasena se compara con hash y la sesion se regenera al ingresar.</span>
                    </li>
                    <li>
                        <strong>Entrada simple</strong>
                        <span>El formulario es corto y muestra mensajes claros.</span>
                    </li>
                    <li>
                        <strong>Primer acceso guiado</strong>
                        <span>Si aun no tienes ambiente, el sistema te lleva a una seleccion visual y animada.</span>
                    </li>
                </ul>
            </div>

            <div class="bloque">
                <p class="etiqueta">Ingreso</p>
                <h2>Completa tus datos</h2>
                <p class="texto">Puedes usar tu usuario o tu correo electronico.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mensaje mensaje-error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mensaje mensaje-exito"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="POST" id="formLogin" class="formulario">
                    <?= csrf_field() ?>

                    <div class="campo">
                        <label for="usuario">Usuario o correo</label>
                        <input type="text" name="usuario" id="usuario" placeholder="usuario o correo@dominio.com" value="<?= esc(old('usuario')) ?>" autocomplete="username" required>
                    </div>

                    <div class="campo">
                        <label for="loginPassword">Contrasena</label>
                        <div class="campo-password">
                            <input type="password" name="password" id="loginPassword" placeholder="Ingresa tu contrasena" autocomplete="current-password" required>
                            <button type="button" class="boton boton-secundario boton-bloque" id="verPasswordLogin">Mostrar</button>
                        </div>
                    </div>

                    <p class="nota">Las credenciales se validan en servidor y, si es tu primera vez, el siguiente paso sera elegir el ambiente.</p>

                    <button type="submit" class="boton boton-bloque" id="botonLogin">Entrar</button>
                    <a href="<?= site_url('registro') ?>" class="enlace-centro">No tengo cuenta todavia</a>
                </form>
            </div>
        </section>
    </main>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/login.js') ?>"></script>
</body>
</html>
