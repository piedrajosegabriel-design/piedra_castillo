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
            <a href="<?= site_url('login') ?>" class="boton boton-secundario">Login</a>
        </div>
    </header>

    <main class="contenido">
        <section class="seccion seccion-principal">
            <div class="bloque">
                <p class="etiqueta">Registro</p>
                <h1 class="titulo">Crea tu cuenta y activa el acceso en segundos.</h1>
                <p class="texto">
                    El registro prepara tu cuenta, valida tus datos y deja listo
                    el acceso. La eleccion del ambiente se realiza despues del
                    primer ingreso en una experiencia visual mas guiada.
                </p>

                <ul class="lista-simple">
                    <li>
                        <strong>Formulario directo</strong>
                        <span>Solo los datos necesarios para empezar sin pasos de más.</span>
                    </li>
                    <li>
                        <strong>Validaciones utiles</strong>
                        <span>Correo valido, confirmacion de contrasena y clave segura.</span>
                    </li>
                    <li>
                        <strong>Eleccion guiada del ambiente</strong>
                        <span>Despues de iniciar sesion eliges el espacio con una interfaz interactiva y animada.</span>
                    </li>
                </ul>
            </div>

            <div class="bloque">
                <p class="etiqueta">Alta de usuario</p>
                <h2>Completa tus datos</h2>
                <p class="texto">Primero crea tu acceso. El ambiente lo eliges despues de entrar.</p>

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
                            <label for="email">Correo electrónico</label>
                            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="correo@ejemplo.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" value="<?= esc(old('usuario')) ?>" placeholder="usuario.personal" autocomplete="username" required>
                        <p class="nota">Usa letras, numeros, puntos, guiones o guion bajo.</p>
                    </div>

                    <div class="bloque-suave">
                        <strong>El ambiente se configura despues del login</strong>
                        <p class="texto">
                            Al ingresar por primera vez veras una pantalla interactiva
                            para elegir entre oficina, aula, hogar, dormitorio o un
                            perfil personalizable.
                        </p>
                    </div>

                    <div class="campo">
                        <label for="registroPassword">Contraseña</label>
                        <div class="campo-password">
                            <input type="password" id="registroPassword" name="password" placeholder="Crea una contrasena" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="boton boton-secundario boton-bloque" id="verPasswordRegistro">Mostrar</button>
                        </div>
                        <p class="nota">Debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.</p>
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

                    <p id="coincideTexto" class="nota">Esperando confirmación de contraseña.</p>

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
