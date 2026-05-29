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
    $routes->get('perfil', 'PanelController::perfil');
    $routes->post('perfil', 'PanelController::actualizarPerfil');
    $routes->post('password', 'PanelController::actualizarPassword');
    $routes->get('compra', 'PanelController::compra');
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
// Datos simulados — preparado para cablear medición real más adelante.
$routes->get('api/sensores', static function () {
    $rand = static fn (float $min, float $max): float => $min + mt_rand() / mt_getrandmax() * ($max - $min);

    $temperatura = round($rand(21.5, 23.5), 0);
    $humedad     = (int) round($rand(45, 52));
    $co2ppm      = (int) round($rand(520, 720));
    $calidad     = (int) round($rand(82, 95));

    $co2Estado = $co2ppm < 800 ? 'OK' : 'Alto';
    $calidadEt = $calidad >= 90 ? 'Excelente' : ($calidad >= 75 ? 'Buena' : 'Regular');

    return service('response')->setJSON([
        'status'    => 'success',
        'timestamp' => date('c'),
        'sensores'  => [
            'temperatura'    => ['valor' => $temperatura, 'unidad' => '°C', 'texto' => $temperatura . ' °C'],
            'humedad'        => ['valor' => $humedad,     'unidad' => '%',  'texto' => $humedad . ' %'],
            'co2'            => ['valor' => $co2ppm,      'unidad' => 'ppm','texto' => $co2Estado],
            'calidad_aire'   => ['valor' => $calidad,     'unidad' => '/100','texto' => $calidadEt],
            'ventilador'     => ['valor' => 'activo',     'texto' => 'Activo'],
            'humidificacion' => ['valor' => 'optima',     'texto' => 'Óptima'],
        ],
    ]);
});
