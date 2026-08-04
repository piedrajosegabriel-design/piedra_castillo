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

    // Mis dispositivos + conexión de un equipo nuevo por QR.
    // No hay formulario de alta: el equipo se da de alta solo contra la API
    // mientras la ventana de vinculación está abierta.
    $routes->get('dispositivos', 'DispositivosController::index');
    $routes->get('dispositivos/conectar', 'DispositivosController::conectar');
    $routes->post('dispositivos/conectar', 'DispositivosController::iniciar');
    $routes->get('dispositivos/conectar/estado', 'DispositivosController::estadoVinculacion');
    $routes->post('dispositivos/conectar/cancelar', 'DispositivosController::cancelar');
    $routes->post('dispositivo-activo', 'PanelController::seleccionarDispositivo');

    // Ambientes (Hito 2): listado y edición de los espacios del usuario.
    $routes->get('ambientes', 'AmbientesController::index');
    $routes->get('ambientes/(:num)/editar', 'AmbientesController::editar/$1');
    $routes->post('ambientes/(:num)', 'AmbientesController::actualizar/$1');

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
    // Vinculación: el equipo se presenta con su MAC y recibe sus credenciales
    // si hay una ventana abierta. Es el único sin token (viene a buscarlo).
    $routes->post('pair', 'Api\DeviceApiController::pair');

    // Configuración: con qué umbrales tiene que decidir el equipo.
    $routes->get('(:segment)/config', 'Api\DeviceApiController::config/$1');

    $routes->post('(:segment)/measurements', 'Api\DeviceApiController::storeMeasurement/$1');
    $routes->get('(:segment)/commands/pending', 'Api\DeviceApiController::pendingCommands/$1');
    $routes->post('(:segment)/commands/(:num)/executed', 'Api\DeviceApiController::markCommandExecuted/$1/$2');
});

// Endpoint público de lectura ambiental usado por el core 3D del hero.
// Devuelve la ÚLTIMA medición real cargada por cualquier dispositivo Eden Air:
// el visitante de la landing ve aire medido de verdad, no números de relleno.
// Si todavía no hay ninguna medición, responde 'sin_datos' y el hero muestra
// sus valores de reposo.
$routes->get('api/sensores', static function () {
    $medicion = (new \App\Models\MeasurementModel())
        ->orderBy('captured_at', 'DESC')
        ->first();

    if (! $medicion) {
        return service('response')->setJSON([
            'status'    => 'sin_datos',
            'timestamp' => date('c'),
            'sensores'  => null,
        ]);
    }

    $temperatura = (float) $medicion['temperature'];
    $humedad     = (int) round((float) $medicion['humidity']);
    $co2ppm      = (int) $medicion['co2_ppm'];
    $calidad     = (int) $medicion['air_quality_index'];

    return service('response')->setJSON([
        'status'    => 'success',
        'timestamp' => date('c', strtotime((string) $medicion['captured_at'])),
        'sensores'  => [
            'temperatura'  => ['valor' => $temperatura, 'unidad' => '°C',   'texto' => number_format($temperatura, 1) . ' °C'],
            'humedad'      => ['valor' => $humedad,     'unidad' => '%',    'texto' => $humedad . ' %'],
            'co2'          => ['valor' => $co2ppm,      'unidad' => 'ppm',  'texto' => $co2ppm < 800 ? 'OK' : 'Alto'],
            'calidad_aire' => ['valor' => $calidad,     'unidad' => '/100', 'texto' => (string) $medicion['air_quality_label']],
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
