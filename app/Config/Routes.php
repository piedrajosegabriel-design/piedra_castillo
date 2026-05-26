<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'AccesoController::inicio');

$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'AccesoController::login');
    $routes->post('login', 'AccesoController::validarLogin');
    $routes->get('registro', 'AccesoController::registro');
    $routes->post('registro', 'AccesoController::guardarRegistro');
    $routes->get('register', 'AccesoController::registro');
    $routes->post('register', 'AccesoController::guardarRegistro');
    $routes->get('recuperar', 'AccesoController::recuperar');
    $routes->post('recuperar', 'AccesoController::procesarRecuperacion');
    $routes->get('restablecer/(:any)', 'AccesoController::restablecer/$1');
    $routes->post('restablecer/(:any)', 'AccesoController::guardarNuevaPassword/$1');
});

$routes->get('logout', 'AccesoController::logout', ['filter' => 'auth']);

$routes->group('panel', ['filter' => 'auth'], static function ($routes) {
    $routes->get('ambiente', 'AccesoController::seleccionAmbiente');
    $routes->post('ambiente', 'AccesoController::guardarAmbiente');
    $routes->get('', 'PanelController::index');
    $routes->post('medicion', 'PanelController::guardarMedicion');
    $routes->post('modo', 'PanelController::cambiarModo');
    $routes->post('actuador', 'PanelController::cambiarActuador');
});

$routes->get('dashboard', 'PanelController::index', ['filter' => 'auth']);

$routes->group('api/devices', static function ($routes) {
    $routes->post('(:segment)/measurements', 'Api\DeviceApiController::storeMeasurement/$1');
    $routes->get('(:segment)/commands/pending', 'Api\DeviceApiController::pendingCommands/$1');
    $routes->post('(:segment)/commands/(:num)/executed', 'Api\DeviceApiController::markCommandExecuted/$1/$2');
});