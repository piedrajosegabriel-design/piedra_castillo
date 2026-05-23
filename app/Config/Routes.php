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

// Endpoint público de lectura ambiental usado por el core 3D del hero.
$routes->get('api/sensores', static function () {
    $rand = static fn (float $min, float $max): float => $min + mt_rand() / mt_getrandmax() * ($max - $min);

    $temperatura  = round($rand(21.5, 24.5), 1);
    $humedad      = (int) round($rand(42, 58));
    $co2          = (int) round($rand(520, 780));
    $calidadAire  = (int) round($rand(72, 92));

    $estado = ($co2 < 800 && $calidadAire >= 70 && $temperatura <= 26 && $humedad >= 40 && $humedad <= 60)
        ? 'optimo'
        : 'atencion';

    return service('response')->setJSON([
        'status'    => 'success',
        'timestamp' => date('c'),
        'estado'    => $estado,
        'sensores'  => [
            'temperatura'    => ['valor' => $temperatura, 'unidad' => '°C'],
            'humedad'        => ['valor' => $humedad,     'unidad' => '%'],
            'co2'            => ['valor' => $co2,         'unidad' => 'ppm'],
            'calidad_aire'   => ['valor' => $calidadAire, 'unidad' => '/100'],
        ],
    ]);
});