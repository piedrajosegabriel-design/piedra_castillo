<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Mapa de rutas de EdenAir, agrupado por QUIÉN puede entrar:
 *   1) Públicas            → cualquiera (landing, portfolio, api/sensores)
 *   2) Grupo 'guest'       → solo SIN sesión (login, registro, recuperación)
 *   3) Grupo 'panel'       → solo CON sesión (filtro 'auth'): el área privada
 *   4) Grupo 'api/devices' → el ESP32 (autentica por token, no por sesión)
 */

// =============================================================================
// 1) RUTAS PÚBLICAS — accesibles sin login
// =============================================================================
$routes->get('/', 'AccesoController::inicio');

// Portfolio público
$routes->get('portfolio',     'PortfolioController::index');
$routes->get('portfolio.php', 'PortfolioController::index');

// =============================================================================
// 2) GRUPO GUEST — solo para usuarios SIN sesión.
// El filtro 'guest' (app/Filters/GuestFilter.php) redirige a /panel si ya
// hay sesión iniciada: un usuario logueado no debería ver el login.
// =============================================================================
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

// =============================================================================
// 3) GRUPO PANEL — el área privada. El filtro 'auth'
// (app/Filters/AuthFilter.php) corta el paso si no hay sesión.
// Todas estas URLs quedan prefijadas con /panel.
// =============================================================================
$routes->group('panel', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'PanelController::index');
    $routes->get('perfil', 'PanelController::perfil');
    $routes->post('perfil', 'PanelController::actualizarPerfil');
    $routes->post('password', 'PanelController::actualizarPassword');
    $routes->get('compra', 'PanelController::compra');

    // Hito 2 — Mis dispositivos + alta por código de activación.
    $routes->get('dispositivos', 'DispositivosController::index');
    $routes->get('dispositivos/agregar', 'DispositivosController::agregar');
    $routes->get('dispositivos/validar', 'DispositivosController::validar');
    $routes->post('dispositivos', 'DispositivosController::guardar');
    $routes->post('dispositivo-activo', 'PanelController::seleccionarDispositivo');
    $routes->post('demo', 'PanelController::iniciarDemo');

    // Ambientes (Hito 2): listado y edición de los espacios del usuario.
    $routes->get('ambientes', 'AmbientesController::index');
    $routes->get('ambientes/(:num)/editar', 'AmbientesController::editar/$1');
    $routes->post('ambientes/(:num)', 'AmbientesController::actualizar/$1');

    $routes->post('medicion', 'PanelController::guardarMedicion');
    $routes->post('modo', 'PanelController::cambiarModo');
    $routes->post('actuador', 'PanelController::cambiarActuador');
});

// Alias: /dashboard muestra lo mismo que /panel.
$routes->get('dashboard', 'PanelController::index', ['filter' => 'auth']);

// =============================================================================
// 4) API REST PARA EL ESP32 — sin filtro 'auth' (el dispositivo no tiene
// sesión): autentica con su token en DeviceApiController. Exenta de CSRF.
// (:segment) captura el device_uid de la URL; (:num) el id del comando.
// =============================================================================
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

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO

   Métodos de RouteCollection (CI4):
   - $routes->get('ruta', 'Controller::metodo')  → registra una ruta GET
   - $routes->post(...)                          → ídem para POST
   - $routes->group('prefijo', $opciones, $fn)   → agrupa rutas bajo un prefijo
                                                   de URL y/o un filtro común
   - ['filter' => 'auth'|'guest']                → filtro que corre ANTES del
                                                   controller (app/Filters/)

   Comodines en las rutas:
   - (:num)     → solo números        → llega como parámetro ($1, $2...)
   - (:any)     → cualquier cosa (incluye /) — usado para el token de reset
   - (:segment) → un segmento de URL (sin /) — usado para el device_uid

   Otros:
   - service('response')->setJSON()  → respuesta JSON sin pasar por un controller
   - static fn / static function     → closures sin acceso a $this (más livianas)
   - mt_rand()/mt_getrandmax()       → aleatorio para los datos simulados del hero
   ============================================================================ */
