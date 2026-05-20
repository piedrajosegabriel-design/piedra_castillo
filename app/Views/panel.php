<!DOCTYPE html>
<html lang="es">
<head>
    <?= view('partials/head', [
        'title'    => 'EdenAir | Panel',
        'extraCss' => ['CSS/dashboard.css'],
    ]) ?>
</head>
<body class="dashboard-body ea-body dashboard-loading">
<?php
    $panel = isset($panel) && is_array($panel) ? $panel : [];

    $user = isset($panel['user']) && is_array($panel['user']) ? $panel['user'] : [];
    $space = isset($panel['space']) && is_array($panel['space']) ? $panel['space'] : [];
    $device = isset($panel['device']) && is_array($panel['device']) ? $panel['device'] : [];
    $state = isset($panel['state']) && is_array($panel['state']) ? $panel['state'] : [];
    $metricsData = isset($panel['metrics']) && is_array($panel['metrics']) ? $panel['metrics'] : [];
    $chartsData = isset($panel['charts']) && is_array($panel['charts']) ? $panel['charts'] : [];
    $actuatorsData = isset($panel['actuators']) && is_array($panel['actuators']) ? $panel['actuators'] : [];
    $alertsData = isset($panel['alerts']) && is_array($panel['alerts']) ? $panel['alerts'] : [];
    $historyData = isset($panel['history']) && is_array($panel['history']) ? $panel['history'] : [];
    $latestData = isset($panel['latest_measurement']) && is_array($panel['latest_measurement']) ? $panel['latest_measurement'] : null;
    $api = isset($panel['api']) && is_array($panel['api']) ? $panel['api'] : [];
    $errors = session()->getFlashdata('errors') ?? [];

    $extractNumber = static function ($value, float $default = 0.0): float {
        if (is_int($value) || is_float($value)) {
            return (float) $value;  
        }

        if (is_string($value) && preg_match('/-?\d+(?:[.,]\d+)?/', $value, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return $default;
    };

    $metricIndex = [];
    foreach ($metricsData as $metric) {
        $titulo = strtolower((string) ($metric['titulo'] ?? ''));
        if ($titulo !== '') {
            $metricIndex[$titulo] = $metric;
        }
    }

    $userName = (string) ($user['nombre'] ?? 'Usuario');
    $spaceName = (string) ($space['nombre'] ?? 'Ambiente principal');
    $spaceLabel = (string) ($space['tipo_label'] ?? 'Monitoreo ambiental');
    $spaceSummary = (string) ($space['resumen'] ?? 'Resumen ambiental disponible para seguimiento general.');
    $deviceName = (string) ($device['nombre'] ?? 'Módulo EdenAir');
    $deviceUid = (string) ($device['uid'] ?? 'EA-ENV-01');
    $deviceToken = (string) ($device['token'] ?? 'Token disponible al enlazar el dispositivo.');
    $deviceTokenPreview = strlen($deviceToken) > 8
        ? substr($deviceToken, 0, 4) . str_repeat('*', 8) . substr($deviceToken, -4)
        : $deviceToken;
    $deviceLastSeen = (string) ($device['ultimo_envio'] ?? 'Sin envíos todavía');
    $deviceLastSync = (string) ($device['ultima_consulta'] ?? 'Sin consultas todavía');

    $modeKey = (string) ($state['modo'] ?? 'automatic');
    $modoManual = $modeKey === 'manual';
    $modeLabel = $modoManual ? 'Manual' : 'Automático';
    $modeDetail = (string) ($state['detalle'] ?? 'El sistema está listo para operar con supervisión ambiental.');

    $defaultTempDetail = isset($space['perfil']['min_temperature'], $space['perfil']['max_temperature'])
        ? sprintf('Ideal entre %.1f y %.1f C', (float) $space['perfil']['min_temperature'], (float) $space['perfil']['max_temperature'])
        : 'Rango sugerido entre 22.0 y 26.0 C';
    $defaultHumidityDetail = isset($space['perfil']['min_humidity'], $space['perfil']['max_humidity'])
        ? sprintf('Ideal entre %.0f y %.0f %%', (float) $space['perfil']['min_humidity'], (float) $space['perfil']['max_humidity'])
        : 'Rango sugerido entre 45 y 60 %';
    $defaultCo2Detail = isset($space['perfil']['max_co2'])
        ? 'Límite recomendado: ' . (int) $space['perfil']['max_co2'] . ' ppm'
        : 'Límite recomendado: 900 ppm';

    $defaultMetrics = [
        [
            'titulo' => 'Temperatura',
            'valor' => '24.6 C',
            'estado' => 'En rango',
            'tono' => 'success',
            'detalle' => $defaultTempDetail,
        ],
        [
            'titulo' => 'Humedad',
            'valor' => '58 %',
            'estado' => 'Estable',
            'tono' => 'success',
            'detalle' => $defaultHumidityDetail,
        ],
        [
            'titulo' => 'CO2',
            'valor' => '640 ppm',
            'estado' => 'Controlado',
            'tono' => 'info',
            'detalle' => $defaultCo2Detail,
        ],
        [
            'titulo' => 'Calidad del aire',
            'valor' => '78/100',
            'estado' => 'Bueno',
            'tono' => 'success',
            'detalle' => 'Aire dentro de una franja cómoda para el ambiente.',
        ],
    ];

    if ($metricsData === []) {
        $metricsData = $defaultMetrics;
        foreach ($metricsData as $metric) {
            $metricIndex[strtolower((string) $metric['titulo'])] = $metric;
        }
    }

    $tempMetric = $metricIndex['temperatura'] ?? $defaultMetrics[0];
    $humidityMetric = $metricIndex['humedad'] ?? $defaultMetrics[1];
    $co2Metric = $metricIndex['co2'] ?? $defaultMetrics[2];
    $airMetric = $metricIndex['calidad del aire'] ?? $defaultMetrics[3];

    $currentTemp = $extractNumber($tempMetric['valor'] ?? null, 24.6);
    $currentHumidity = max(0, min(100, (int) round($extractNumber($humidityMetric['valor'] ?? null, 58))));
    $currentCo2 = max(0, (int) round($extractNumber($co2Metric['valor'] ?? null, 640)));
    $currentAir = max(0, min(100, (int) round($extractNumber($airMetric['valor'] ?? null, 78))));

    $airStateLabel = $currentAir >= 70 ? 'Bueno' : ($currentAir >= 55 ? 'Regular' : 'Malo');
    $airTone = $currentAir >= 70 ? 'success' : ($currentAir >= 55 ? 'warning' : 'danger');
    $airSummary = $airStateLabel === 'Bueno'
        ? 'Calidad del aire cómoda para el uso diario.'
        : ($airStateLabel === 'Regular'
            ? 'Conviene observar tendencia y refrigeración.'
            : 'Se recomienda revisar el ambiente.');

    $metricTones = [
        (string) ($tempMetric['tono'] ?? 'success'),
        (string) ($humidityMetric['tono'] ?? 'success'),
        (string) ($co2Metric['tono'] ?? 'info'),
        $airTone,
    ];
    $metricDangerCount = count(array_filter($metricTones, static function (string $tone): bool {
        return $tone === 'danger';
    }));
    $metricWarningCount = count(array_filter($metricTones, static function (string $tone): bool {
        return in_array($tone, ['warning', 'danger'], true);
    }));
    $baseGeneralStatus = $metricWarningCount > 0 ? 'Requiere revisión' : 'Ambiente estable';
    $baseGeneralTone = $metricDangerCount > 0 ? 'danger' : ($metricWarningCount > 0 ? 'warning' : 'success');

    $defaultAlerts = [
        [
            'tono' => $baseGeneralTone,
            'titulo' => 'Resumen del ambiente seleccionado',
            'texto' => 'La vista queda preparada para informar el estado del ambiente seleccionado sin mezclar otros espacios.',
        ],
        [
            'tono' => (string) ($tempMetric['tono'] ?? 'success'),
            'titulo' => 'Temperatura del ambiente',
            'texto' => (string) ($tempMetric['detalle'] ?? $defaultTempDetail),
        ],
        [
            'tono' => (string) ($humidityMetric['tono'] ?? 'success'),
            'titulo' => 'Humedad del ambiente',
            'texto' => (string) ($humidityMetric['detalle'] ?? $defaultHumidityDetail),
        ],
    ];
    $alerts = $alertsData !== [] ? $alertsData : $defaultAlerts;
    $criticalCount = count(array_filter($alerts, static function (array $alerta): bool {
        return in_array((string) ($alerta['tono'] ?? 'neutral'), ['warning', 'danger'], true);
    }));

    $generalStatus = $criticalCount > 0 ? 'Requiere revisión' : $baseGeneralStatus;
    $generalTone = $criticalCount > 1 ? 'danger' : ($criticalCount === 1 ? 'warning' : $baseGeneralTone);
    $generalDetail = $criticalCount > 0
        ? 'Hay ' . $criticalCount . ' alerta' . ($criticalCount === 1 ? '' : 's') . ' para revisar en el resumen ambiental.'
        : 'Las variables principales se mantienen dentro de una lectura tranquila.';

    $defaultActuators = [
        [
            'clave' => 'fan',
            'titulo' => 'Aire acondicionado',
            'estado' => 'Encendido',
            'tono' => 'info',
            'detalle' => 'Refresca el ambiente cuando sube la temperatura o el CO2.',
        ],
        [
            'clave' => 'aromatizer',
            'titulo' => 'Aromatizador',
            'estado' => 'Apagado',
            'tono' => 'neutral',
            'detalle' => 'Acompaña la sensación general del ambiente cuando es necesario.',
        ],
        [
            'clave' => 'alert_led',
            'titulo' => 'LED de alerta',
            'estado' => 'Apagado',
            'tono' => 'neutral',
            'detalle' => 'Da una referencia visual cuando una condición sale del rango.',
        ],
    ];
    $actuators = $actuatorsData !== [] ? $actuatorsData : $defaultActuators;
    $activeActuators = count(array_filter($actuators, static function (array $actuador): bool {
        return strtolower((string) ($actuador['estado'] ?? 'apagado')) !== 'apagado';
    }));

    $temperatureBars = isset($chartsData[0]['puntos']) && is_array($chartsData[0]['puntos']) && $chartsData[0]['puntos'] !== []
        ? array_slice($chartsData[0]['puntos'], -6)
        : [
            ['valor' => '22.8', 'porcentaje' => 48, 'tono' => 'success', 'etiqueta' => '08:00'],
            ['valor' => '23.4', 'porcentaje' => 54, 'tono' => 'success', 'etiqueta' => '10:00'],
            ['valor' => '24.1', 'porcentaje' => 60, 'tono' => 'success', 'etiqueta' => '12:00'],
            ['valor' => '24.9', 'porcentaje' => 68, 'tono' => 'warning', 'etiqueta' => '14:00'],
            ['valor' => '25.2', 'porcentaje' => 72, 'tono' => 'warning', 'etiqueta' => '16:00'],
            ['valor' => '24.6', 'porcentaje' => 64, 'tono' => 'success', 'etiqueta' => '18:00'],
        ];
    $temperatureRange = (string) ($chartsData[0]['rango'] ?? $defaultTempDetail);

    $latest = $latestData ?? [
        'fecha' => 'Hoy 18:00',
        'temperatura' => number_format($currentTemp, 1) . ' C',
        'humedad' => $currentHumidity . ' %',
        'co2' => $currentCo2 . ' ppm',
        'aire' => $airStateLabel . ' (' . $currentAir . '/100)',
        'origen' => 'Panel web',
        'notas' => 'Dato de ejemplo visual para el resumen del panel.',
    ];
    $latestIsSample = $latestData === null;

    $historyRows = $historyData !== [] ? $historyData : [
        [
            'fecha' => '14/05/2026 08:00',
            'temperatura' => '23.8 C',
            'humedad' => '55 %',
            'co2' => '610 ppm',
            'aire' => 'Bueno (80/100)',
            'origen' => 'Web',
        ],
        [
            'fecha' => '14/05/2026 10:00',
            'temperatura' => '24.1 C',
            'humedad' => '57 %',
            'co2' => '640 ppm',
            'aire' => 'Bueno (78/100)',
            'origen' => 'API',
        ],
        [
            'fecha' => '14/05/2026 14:00',
            'temperatura' => '24.9 C',
            'humedad' => '60 %',
            'co2' => '710 ppm',
            'aire' => 'Regular (68/100)',
            'origen' => 'API',
        ],
        [
            'fecha' => '14/05/2026 18:00',
            'temperatura' => number_format($currentTemp, 1) . ' C',
            'humedad' => $currentHumidity . ' %',
            'co2' => $currentCo2 . ' ppm',
            'aire' => $airStateLabel . ' (' . $currentAir . '/100)',
            'origen' => 'Web',
        ],
    ];
    $historyIsSample = $historyData === [];

    $temperatureRecommended = isset($space['perfil']['min_temperature'], $space['perfil']['max_temperature'])
        ? sprintf('%.1f a %.1f C', (float) $space['perfil']['min_temperature'], (float) $space['perfil']['max_temperature'])
        : '22.0 a 26.0 C';
    $humidityRecommended = isset($space['perfil']['min_humidity'], $space['perfil']['max_humidity'])
        ? sprintf('%.0f a %.0f %%', (float) $space['perfil']['min_humidity'], (float) $space['perfil']['max_humidity'])
        : '45 a 60 %';
    $co2Recommended = isset($space['perfil']['max_co2'])
        ? (int) $space['perfil']['max_co2'] . ' ppm'
        : '900 ppm';

    $selectedEnvironmentStatus = $criticalCount > 1
        ? 'Con observaciones'
        : ($criticalCount === 1 ? 'En seguimiento' : 'Estable');
    $selectedEnvironmentBadge = $criticalCount > 1
        ? 'Alerta'
        : ($criticalCount === 1 ? 'Regular' : 'Bueno');

    $summaryCards = [
        [
            'sigla' => 'TMP',
            'titulo' => 'Temperatura',
            'valor' => number_format($currentTemp, 1) . ' C',
            'detalle' => (string) ($tempMetric['detalle'] ?? $defaultTempDetail),
            'estado' => (string) ($tempMetric['estado'] ?? 'En rango'),
            'tono' => (string) ($tempMetric['tono'] ?? 'success'),
        ],
        [
            'sigla' => 'HUM',
            'titulo' => 'Humedad',
            'valor' => $currentHumidity . ' %',
            'detalle' => (string) ($humidityMetric['detalle'] ?? $defaultHumidityDetail),
            'estado' => (string) ($humidityMetric['estado'] ?? 'Estable'),
            'tono' => (string) ($humidityMetric['tono'] ?? 'success'),
        ],
        [
            'sigla' => 'AIR',
            'titulo' => 'Aire / CO2',
            'valor' => $currentAir . '/100',
            'detalle' => 'CO2 actual: ' . $currentCo2 . ' ppm. ' . $airSummary,
            'estado' => $airStateLabel,
            'tono' => $airTone,
        ],
        [
            'sigla' => 'ALT',
            'titulo' => 'Alertas',
            'valor' => $criticalCount > 0 ? $criticalCount . ' activas' : 'Sin alertas',
            'detalle' => $criticalCount > 0
                ? 'Revisa el panel de alertas para ver el detalle y la prioridad.'
                : 'No hay avisos críticos en el estado actual del sistema.',
            'estado' => $criticalCount > 0 ? 'Atención' : 'Estable',
            'tono' => $generalTone,
        ],
    ];
?>

<noscript>
    <style>
        .dashboard-loading .dashboard-loader {
            display: none;
        }

        .dashboard-loading .dashboard-app {
            opacity: 1;
            transform: none;
        }
    </style>
</noscript>

<div class="dashboard-loader" data-dashboard-loader>
    <div class="dashboard-loader-glow dashboard-loader-glow-a"></div>
    <div class="dashboard-loader-glow dashboard-loader-glow-b"></div>
    <div class="dashboard-loader-grid"></div>
    <div class="dashboard-loader-shell">
        <div class="dashboard-loader-brand">
            <svg viewBox="0 0 100 100" width="44" height="44" aria-hidden="true">
                <circle cx="50" cy="50" r="44" fill="none" stroke="#ecf2e8" stroke-width="2"/>
                <path d="M 18 70 C 30 35, 60 25, 82 30" fill="none" stroke="#ecf2e8" stroke-width="2" stroke-linecap="round"/>
                <path d="M 18 70 C 40 60, 65 55, 82 30" fill="none" stroke="#bcd2bd" stroke-width="2" stroke-linecap="round"/>
                <circle cx="50" cy="50" r="2.4" fill="#ecf2e8"/>
            </svg>
            <strong class="dashboard-loader-name">Eden<em style="font-style:italic;color:#c9d870;">Air</em></strong>
        </div>

        <div class="dashboard-loader-scene" aria-hidden="true">
            <div class="dashboard-loader-halo dashboard-loader-halo-a"></div>
            <div class="dashboard-loader-halo dashboard-loader-halo-b"></div>
            <span class="dashboard-loader-ribbon dashboard-loader-ribbon-a"></span>
            <span class="dashboard-loader-ribbon dashboard-loader-ribbon-b"></span>
            <span class="dashboard-loader-ribbon dashboard-loader-ribbon-c"></span>

            <div class="dashboard-loader-core">
                <div class="dashboard-loader-orbit dashboard-loader-orbit-a"></div>
                <div class="dashboard-loader-orbit dashboard-loader-orbit-b"></div>
                <div class="dashboard-loader-orbit dashboard-loader-orbit-c"></div>
                <div class="dashboard-loader-pulse">
                    <div class="dashboard-loader-emblem">
                        <span class="dashboard-loader-emblem-leaf"></span>
                        <span class="dashboard-loader-emblem-wave dashboard-loader-emblem-wave-a"></span>
                        <span class="dashboard-loader-emblem-wave dashboard-loader-emblem-wave-b"></span>
                    </div>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <span class="dashboard-loader-spark dashboard-loader-spark-a"></span>
            <span class="dashboard-loader-spark dashboard-loader-spark-b"></span>
            <span class="dashboard-loader-spark dashboard-loader-spark-c"></span>
            <span class="dashboard-loader-spark dashboard-loader-spark-d"></span>
        </div>

        <div class="dashboard-loader-content">
            <h1>Clima bajo control</h1>
            <div class="dashboard-loader-progress" aria-hidden="true">
                <span></span>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-app" data-dashboard-app>
    <header class="dashboard-header">
        <div class="header-start">
            <button type="button" class="nav-toggle" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Abrir o cerrar menú lateral">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a href="<?= site_url('/') ?>" class="brand-link">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 100 100" width="28" height="28">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="3"/>
                        <path d="M 18 70 C 30 35, 60 25, 82 30" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        <path d="M 18 70 C 40 60, 65 55, 82 30" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.65"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <strong>Eden<em style="font-style:italic;color:var(--eden-500);">Air</em></strong>
                    <small>Sistema de monitoreo ambiental</small>
                </span>
            </a>
        </div>

        <div class="header-actions">
            <div class="user-box">
                <span class="eyebrow">Hola, <?= esc($userName) ?></span>
                <strong>Panel de monitoreo</strong>
                <small><?= esc($spaceName) ?> - <?= esc($modeLabel) ?></small>
            </div>

            <div class="header-tools">
                <?= view('partials/theme_toggle') ?>
                <a href="<?= site_url('logout') ?>" class="logout-link">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <div class="sidebar-panel">
            <div class="sidebar-top">
                <p class="eyebrow">Navegación</p>
                <h2>Resumen rápido</h2>
                <p class="sidebar-note">Acceso directo al estado del ambiente, alertas y configuración operativa.</p>
            </div>

            <nav class="sidebar-nav" aria-label="Secciones principales">
                <a href="#dashboard" class="sidebar-link">
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 13.2L12 5l8 8.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1v-6.8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <span class="sidebar-label">Dashboard</span>
                </a>

                <a href="#ambiente" class="sidebar-link">
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5c3.6 0 6.5 2.8 6.5 6.3 0 5-6.5 10.7-6.5 10.7S5.5 14.8 5.5 9.8C5.5 6.3 8.4 3.5 12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                            <path d="M12 7.8a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z" stroke="currentColor" stroke-width="1.8"></path>
                        </svg>
                    </span>
                    <span class="sidebar-label">Ambiente actual</span>
                </a>

                <a href="#alertas" class="sidebar-link">
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 5v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                            <path d="M12 16.2h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path>
                            <path d="M10.2 3.8 3.5 16a2 2 0 0 0 1.8 3h13.4a2 2 0 0 0 1.8-3L13.8 3.8a2 2 0 0 0-3.6 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <span class="sidebar-label">Alertas</span>
                </a>

                <a href="#historial" class="sidebar-link">
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M3 12a9 9 0 1 0 3-6.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                            <path d="M3 4v4.5h4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <span class="sidebar-label">Historial</span>
                </a>

                <a href="#configuracion" class="sidebar-link">
                    <span class="sidebar-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m12 3 1.5 2.7 3.1.4-2.1 2.1.5 3-3-1.3-3 1.3.5-3-2.1-2.1 3.1-.4L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                            <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.8"></circle>
                            <path d="M4.5 13.5 3 16l2.4 1 1.2 2.6 2.7-.6 2 1.8 2-1.8 2.7.6 1.2-2.6L21 16l-1.5-2.5" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <span class="sidebar-label">Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <span class="eyebrow">Modo actual</span>
                <strong><?= esc($modeLabel) ?></strong>
                <small><?= esc($spaceLabel) ?></small>
            </div>
        </div>
    </aside>

    <main class="dashboard-main">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash-message flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash-message flash-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="flash-message flash-danger">
                <ul class="flash-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section id="dashboard" class="section-panel">
            <div class="overview-grid">
                <article class="card hero-panel">
                    <p class="eyebrow">Panel de monitoreo</p>
                    <h1>Lectura clara del estado ambiental.</h1>
                    <p class="section-text">
                        Este resumen concentra temperatura, humedad, aire, alertas y estado operativo
                        para que puedas explicar rápidamente cómo responde EdenAir.
                    </p>

                    <div class="hero-tags">
                        <span class="tag-pill"><?= esc($spaceName) ?></span>
                        <span class="tag-pill"><?= esc($deviceName) ?></span>
                        <span class="tag-pill"><?= esc($modeLabel) ?></span>
                    </div>

                    <div class="overview-meta">
                        <div>
                            <span>Ambiente</span>
                            <strong><?= esc($spaceLabel) ?></strong>
                            <small><?= esc($spaceSummary) ?></small>
                        </div>
                        <div>
                            <span>Última lectura</span>
                            <strong><?= esc($latest['fecha']) ?></strong>
                            <small><?= $latestIsSample ? 'Vista de ejemplo lista para reemplazar con datos reales.' : 'Dato reciente recibido por el sistema.' ?></small>
                        </div>
                    </div>
                </article>

                <article class="card highlight-card tone-<?= esc($generalTone) ?>">
                    <div class="highlight-header">
                        <div>
                            <p class="eyebrow">Estado general</p>
                            <h2><?= esc($generalStatus) ?></h2>
                        </div>
                        <span class="status-pill status-<?= esc($generalTone) ?>"><?= esc($generalTone === 'success' ? 'Normal' : 'Atención') ?></span>
                    </div>

                    <p class="section-text"><?= esc($generalDetail) ?></p>

                    <div class="highlight-grid">
                        <div class="mini-stat">
                            <span>Temperatura</span>
                            <strong><?= esc(number_format($currentTemp, 1) . ' C') ?></strong>
                        </div>
                        <div class="mini-stat">
                            <span>Humedad</span>
                            <strong><?= esc($currentHumidity . ' %') ?></strong>
                        </div>
                        <div class="mini-stat">
                            <span>Actuadores activos</span>
                            <strong><?= esc((string) $activeActuators) ?>/<?= esc((string) count($actuators)) ?></strong>
                        </div>
                        <div class="mini-stat">
                            <span>Modo</span>
                            <strong><?= esc($modeLabel) ?></strong>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Métricas principales</p>
                    <h2>Variables clave del ambiente</h2>
                </div>
            </div>

            <div class="metric-grid">
                <?php foreach ($summaryCards as $card): ?>
                    <article class="card metric-card tone-<?= esc($card['tono']) ?>">
                        <div class="metric-head">
                            <span class="metric-mark tone-<?= esc($card['tono']) ?>"><?= esc($card['sigla']) ?></span>
                            <span class="status-pill status-<?= esc($card['tono']) ?>"><?= esc($card['estado']) ?></span>
                        </div>
                        <span class="metric-title"><?= esc($card['titulo']) ?></span>
                        <strong class="metric-value"><?= esc($card['valor']) ?></strong>
                        <p class="metric-text"><?= esc($card['detalle']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Gráficos visuales</p>
                    <h2>Tendencias simples y fáciles de leer</h2>
                </div>
            </div>

            <div class="charts-grid">
                <article class="card chart-card">
                    <div class="card-top">
                        <div>
                            <h3>Temperatura de las últimas horas</h3>
                            <p class="section-text"><?= esc($temperatureRange) ?></p>
                        </div>
                        <span class="status-pill status-<?= esc($tempMetric['tono'] ?? 'success') ?>"><?= esc($tempMetric['estado'] ?? 'En rango') ?></span>
                    </div>

                    <div class="bar-chart" aria-label="Gráfico de barras de temperatura">
                        <?php foreach ($temperatureBars as $bar): ?>
                            <div class="bar-item">
                                <span class="bar-value"><?= esc((string) ($bar['valor'] ?? '0')) ?></span>
                                <div class="bar-track">
                                    <span class="bar-fill tone-<?= esc((string) ($bar['tono'] ?? 'success')) ?>" style="height: <?= esc((string) ($bar['porcentaje'] ?? 40)) ?>%;"></span>
                                </div>
                                <span class="bar-label"><?= esc((string) ($bar['etiqueta'] ?? '--:--')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="card chart-card">
                    <div class="card-top">
                        <div>
                            <h3>Humedad actual</h3>
                            <p class="section-text"><?= esc($humidityMetric['detalle'] ?? $defaultHumidityDetail) ?></p>
                        </div>
                        <span class="status-pill status-<?= esc($humidityMetric['tono'] ?? 'success') ?>"><?= esc($humidityMetric['estado'] ?? 'Estable') ?></span>
                    </div>

                    <div class="progress-layout">
                        <div class="progress-ring" style="--progress: <?= esc((string) $currentHumidity) ?>;">
                            <div class="progress-ring-inner">
                                <strong><?= esc((string) $currentHumidity) ?>%</strong>
                                <span>Humedad</span>
                            </div>
                        </div>

                        <div class="progress-copy">
                            <strong>Lectura actual establecida</strong>
                            <p class="section-text">
                                La humedad puede mantenerse visualmente en una zona confortable y
                                seguir lista para enlazarse con datos reales del controlador.
                            </p>
                        </div>
                    </div>
                </article>

                <article class="card chart-card">
                    <div class="card-top">
                        <div>
                            <h3>Calidad del aire</h3>
                            <p class="section-text">Referencia compacta para mostrar el estado del aire y su relación con el CO2.</p>
                        </div>
                        <span class="status-pill status-<?= esc($airTone) ?>"><?= esc($airStateLabel) ?></span>
                    </div>

                    <div class="air-meter">
                        <div class="air-track">
                            <span class="air-fill tone-<?= esc($airTone) ?>" style="width: <?= esc((string) $currentAir) ?>%;"></span>
                        </div>

                        <div class="air-scale">
                            <span class="<?= $currentAir >= 70 ? 'is-active' : '' ?>">Bueno</span>
                            <span class="<?= $currentAir >= 55 && $currentAir < 70 ? 'is-active' : '' ?>">Regular</span>
                            <span class="<?= $currentAir < 55 ? 'is-active' : '' ?>">Malo</span>
                        </div>

                        <div class="air-summary">
                            <strong><?= esc($currentAir . '/100') ?></strong>
                            <p class="section-text"><?= esc($airSummary) ?> CO2 actual: <?= esc((string) $currentCo2) ?> ppm.</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section id="ambiente" class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Ambiente seleccionado</p>
                    <h2>Lectura del espacio elegido por el usuario</h2>
                </div>
            </div>

            <div class="environment-grid environment-grid-single">
                <article class="card environment-card environment-card-primary tone-<?= esc($generalTone) ?>">
                    <div class="environment-top">
                        <div>
                            <h3><?= esc($spaceName) ?></h3>
                            <p class="section-text"><?= esc($spaceSummary) ?></p>
                        </div>
                        <span class="environment-badge badge-<?= esc($generalTone) ?>"><?= esc($selectedEnvironmentBadge) ?></span>
                    </div>

                    <div class="environment-values environment-values-extended">
                        <div>
                            <span>Tipo de ambiente</span>
                            <strong><?= esc($spaceLabel) ?></strong>
                        </div>
                        <div>
                            <span>Estado actual</span>
                            <strong><?= esc($selectedEnvironmentStatus) ?></strong>
                        </div>
                        <div>
                            <span>Temperatura ideal</span>
                            <strong><?= esc($temperatureRecommended) ?></strong>
                        </div>
                        <div>
                            <span>Humedad ideal</span>
                            <strong><?= esc($humidityRecommended) ?></strong>
                        </div>
                        <div>
                            <span>CO2 recomendado</span>
                            <strong><?= esc($co2Recommended) ?></strong>
                        </div>
                        <div>
                            <span>Modo actual</span>
                            <strong><?= esc($modeLabel) ?></strong>
                        </div>
                    </div>

                    <p class="environment-note">
                        <?= $latestIsSample
                            ? 'Todavía no hay una lectura automática desde ESP32. La vista queda preparada para trabajar con carga manual o datos reales cuando el hardware esté disponible.'
                            : 'Las lecturas visibles pertenecen al ambiente que el usuario eligió al ingresar y se comparan contra su perfil recomendado.' ?>
                    </p>
                </article>
            </div>
        </section>

        <section id="alertas" class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Alertas importantes</p>
                    <h2>Eventos recientes del panel</h2>
                </div>
            </div>

            <?php if ($alerts === []): ?>
                <div class="empty-state">
                    <strong>Sin alertas registradas</strong>
                    <p>El ambiente se mantiene dentro de los rangos esperados. Las nuevas alertas aparecerán acá apenas se generen.</p>
                </div>
            <?php else: ?>
                <div class="alerts-list">
                    <?php foreach ($alerts as $alerta): ?>
                        <article class="card alert-item alert-<?= esc((string) ($alerta['tono'] ?? 'neutral')) ?> tone-<?= esc((string) ($alerta['tono'] ?? 'neutral')) ?>">
                            <span class="alert-marker tone-<?= esc((string) ($alerta['tono'] ?? 'neutral')) ?>"></span>
                            <div>
                                <h3><?= esc((string) ($alerta['titulo'] ?? 'Alerta del sistema')) ?></h3>
                                <p class="section-text"><?= esc((string) ($alerta['texto'] ?? 'Sin detalle disponible.')) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="configuracion" class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Configuración operativa</p>
                    <h2>Actuadores y modo</h2>
                </div>
                <p class="section-text">Desde esta sección se cambia el modo del sistema y, si está en manual, se habilitan los actuadores.</p>
            </div>

            <div class="config-grid">
                <article class="card actuator-card">
                    <div class="card-top">
                        <div>
                            <h3>Actuadores básicos</h3>
                            <p class="section-text">Aire acondicionado, aromatizador y LED de alerta con controles claros de encendido y apagado.</p>
                        </div>
                        <span class="status-pill status-<?= esc($modoManual ? 'info' : 'neutral') ?>"><?= esc($modoManual ? 'Control manual' : 'Bloqueados por modo automático') ?></span>
                    </div>

                    <?php if ($actuators === []): ?>
                        <div class="empty-state empty-state--inline">
                            <strong>Sin actuadores configurados</strong>
                            <p>No hay dispositivos cargados todavía. Cuando se enlacen aparecerán acá con sus controles.</p>
                        </div>
                    <?php else: ?>
                    <div class="actuator-list">
                        <?php foreach ($actuators as $actuador): ?>
                            <div class="actuator-item">
                                <div class="actuator-head">
                                    <div>
                                        <strong><?= esc((string) ($actuador['titulo'] ?? 'Actuador')) ?></strong>
                                        <p class="section-text"><?= esc((string) ($actuador['detalle'] ?? 'Sin detalle adicional.')) ?></p>
                                    </div>
                                    <span class="status-pill status-<?= esc((string) ($actuador['tono'] ?? 'neutral')) ?>"><?= esc((string) ($actuador['estado'] ?? 'Apagado')) ?></span>
                                </div>

                                <div class="actuator-actions">
                                    <form action="<?= site_url('panel/actuador') ?>" method="POST" data-preserve-scroll>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="actuator" value="<?= esc((string) ($actuador['clave'] ?? 'fan')) ?>">
                                        <input type="hidden" name="value" value="on">
                                        <button type="submit" class="control-button" <?= $modoManual ? '' : 'disabled' ?>>Encender</button>
                                    </form>

                                    <form action="<?= site_url('panel/actuador') ?>" method="POST" data-preserve-scroll>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="actuator" value="<?= esc((string) ($actuador['clave'] ?? 'fan')) ?>">
                                        <input type="hidden" name="value" value="off">
                                        <button type="submit" class="ghost-button" <?= $modoManual ? '' : 'disabled' ?>>Apagar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </article>

                <article class="card mode-card">
                    <div class="card-top">
                        <div>
                            <h3>Modo del sistema</h3>
                            <p class="section-text">Visual preparado para destacar si EdenAir está trabajando en automático o en manual.</p>
                        </div>
                        <span class="status-pill status-<?= esc($modoManual ? 'info' : 'success') ?>"><?= esc($modeLabel) ?></span>
                    </div>

                    <div class="mode-current">
                        <strong><?= esc($modeLabel) ?></strong>
                        <p class="section-text"><?= esc($modeDetail) ?></p>
                    </div>

                    <div class="mode-actions">
                        <form action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll>
                            <?= csrf_field() ?>
                            <input type="hidden" name="mode" value="automatic">
                            <button type="submit" class="<?= $modoManual ? 'ghost-button' : 'control-button' ?>">Modo automático</button>
                        </form>

                        <form action="<?= site_url('panel/modo') ?>" method="POST" data-preserve-scroll>
                            <?= csrf_field() ?>
                            <input type="hidden" name="mode" value="manual">
                            <button type="submit" class="<?= $modoManual ? 'control-button' : 'ghost-button' ?>">Modo manual</button>
                        </form>
                    </div>

                    <div class="device-facts">
                        <div>
                            <span>Dispositivo</span>
                            <strong><?= esc($deviceName) ?></strong>
                        </div>
                        <div>
                            <span>UID</span>
                            <strong><?= esc($deviceUid) ?></strong>
                        </div>
                        <div>
                            <span>Último envío</span>
                            <strong><?= esc($deviceLastSeen) ?></strong>
                        </div>
                        <div>
                            <span>Última consulta</span>
                            <strong><?= esc($deviceLastSync) ?></strong>
                        </div>
                    </div>

                    <details class="technical-details">
                        <summary>Información técnica y API</summary>
                        <div class="details-grid">
                            <div>
                                <span>Routes</span>
                                <code><?= esc((string) ($api['routes_file'] ?? 'app/Config/Routes.php')) ?></code>
                            </div>
                            <div>
                                <span>Controller</span>
                                <code><?= esc((string) ($api['controller_file'] ?? 'app/Controllers/Api/DeviceApiController.php')) ?></code>
                            </div>
                            <div>
                                <span>Mediciones</span>
                                <code><?= esc((string) ($api['measurements_url'] ?? site_url('api/devices/' . $deviceUid . '/measurements'))) ?></code>
                            </div>
                            <div>
                                <span>Comandos</span>
                                <code><?= esc((string) ($api['commands_url'] ?? site_url('api/devices/' . $deviceUid . '/commands/pending'))) ?></code>
                            </div>
                            <div>
                                <span>Ejecutado</span>
                                <code><?= esc((string) ($api['executed_url'] ?? site_url('api/devices/' . $deviceUid . '/commands/{id}/executed'))) ?></code>
                            </div>
                            <div>
                                <span>Token</span>
                                <code><?= esc($deviceTokenPreview) ?></code>
                            </div>
                        </div>
                    </details>
                </article>

            </div>
        </section>

        <section id="historial" class="section-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Historial</p>
                    <h2>Lecturas recientes y última medición</h2>
                </div>
                <p class="section-text">Se mantiene el historial del panel, con una presentación más clara para la lectura académica y funcional.</p>
            </div>

            <div class="history-layout">
                <article class="card latest-card">
                    <div class="card-top">
                        <div>
                            <h3>Última medición</h3>
                            <p class="section-text"><?= $latestIsSample ? 'Dato de ejemplo visual preparado para el dashboard.' : 'Última lectura registrada por el sistema.' ?></p>
                        </div>
                        <span class="status-pill status-<?= esc($airTone) ?>"><?= esc($latestIsSample ? 'Ejemplo' : 'Actual') ?></span>
                    </div>

                    <div class="latest-grid">
                        <div class="latest-item">
                            <span>Fecha</span>
                            <strong><?= esc($latest['fecha']) ?></strong>
                        </div>
                        <div class="latest-item">
                            <span>Temperatura</span>
                            <strong><?= esc($latest['temperatura']) ?></strong>
                        </div>
                        <div class="latest-item">
                            <span>Humedad</span>
                            <strong><?= esc($latest['humedad']) ?></strong>
                        </div>
                        <div class="latest-item">
                            <span>CO2</span>
                            <strong><?= esc($latest['co2']) ?></strong>
                        </div>
                        <div class="latest-item">
                            <span>Calidad</span>
                            <strong><?= esc($latest['aire']) ?></strong>
                        </div>
                        <div class="latest-item">
                            <span>Origen</span>
                            <strong><?= esc($latest['origen']) ?></strong>
                        </div>
                    </div>

                    <?php if (($latest['notas'] ?? '') !== ''): ?>
                        <p class="latest-note"><?= esc((string) $latest['notas']) ?></p>
                    <?php endif; ?>
                </article>

                <article class="card table-card">
                    <div class="card-top">
                        <div>
                            <h3>Mediciones recientes</h3>
                            <p class="section-text"><?= $historyIsSample ? 'La tabla muestra datos de ejemplo hasta que existan registros reales.' : 'Últimos registros listos para seguimiento.' ?></p>
                        </div>
                        <span class="status-pill status-info"><?= esc((string) count($historyRows)) ?> filas</span>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Temperatura</th>
                                    <th>Humedad</th>
                                    <th>CO2</th>
                                    <th>Calidad</th>
                                    <th>Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($historyRows === []): ?>
                                    <tr class="empty-row"><td colspan="6">Sin lecturas registradas todavía.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($historyRows as $row): ?>
                                        <tr>
                                            <td><?= esc((string) ($row['fecha'] ?? '--')) ?></td>
                                            <td><?= esc((string) ($row['temperatura'] ?? '--')) ?></td>
                                            <td><?= esc((string) ($row['humedad'] ?? '--')) ?></td>
                                            <td><?= esc((string) ($row['co2'] ?? '--')) ?></td>
                                            <td><?= esc((string) ($row['aire'] ?? '--')) ?></td>
                                            <td><?= esc((string) ($row['origen'] ?? '--')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
</div>

<script src="<?= base_url('JS/tema.js') ?>"></script>
<script src="<?= base_url('JS/dashboard.js') ?>"></script>
</body>
</html>
