<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Flujo principal de la app:
// 1. El usuario entra a la landing.
// 2. Desde ahi puede ir a login o registro.
// 3. Si las credenciales son correctas, llega al dashboard privado.

// Landing principal.
$routes->get('/', 'TesinaController::home');

// Formulario de login y envio de credenciales.
$routes->get('/login', 'TesinaController::inicio');
$routes->post('/login', 'TesinaController::login');

// Formulario de registro y alta del usuario.
$routes->get('/register', 'TesinaController::register');
$routes->post('/register', 'TesinaController::registerStore');

// Panel privado y cierre de sesion.
$routes->get('/dashboard', 'TesinaController::dashboard');
$routes->get('/logout', 'TesinaController::logout');
