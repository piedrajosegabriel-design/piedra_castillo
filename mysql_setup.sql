-- =========================================================
-- EDENAIR - BASE DE DATOS COMPLETA
-- =========================================================
-- Este archivo deja la base lista para:
-- 1. Vista principal del sistema
-- 2. Registro de usuarios
-- 3. Login con autenticacion segura
-- 4. Panel con mediciones, alertas y control
-- 5. API del dispositivo o simulador
--
-- Seguridad:
-- La contraseña NO se guarda en texto plano.
-- En la tabla users se guarda password_hash.
-- La aplicacion usa password_hash() y password_verify().

CREATE DATABASE IF NOT EXISTS tesina_esp32
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE tesina_esp32;

-- =========================================================
-- TABLA: users
-- Guarda los usuarios registrados para login y registro.
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    apellido VARCHAR(120) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL,
    usuario VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    reset_token VARCHAR(64) NULL,
    reset_expires_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Compatibilidad con bases creadas antes de perfil/apellido y recuperacion.
ALTER TABLE users
ADD COLUMN IF NOT EXISTS apellido VARCHAR(120) NOT NULL DEFAULT '' AFTER nombre,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL AFTER password_hash,
ADD COLUMN IF NOT EXISTS reset_expires_at DATETIME NULL AFTER reset_token;


-- =========================================================
-- TABLA: spaces
-- Guarda el ambiente del usuario y sus rangos base.
-- =========================================================

CREATE TABLE IF NOT EXISTS spaces (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    environment_type VARCHAR(20) NOT NULL,
    custom_name VARCHAR(120) NULL,
    min_temperature DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    max_temperature DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    min_humidity DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    max_humidity DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    max_co2 INT UNSIGNED NOT NULL DEFAULT 1000,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    -- Multi-dispositivo: una cuenta puede tener varios ambientes (uno por
    -- dispositivo). Antes era UNIQUE; ahora es índice normal.
    KEY idx_spaces_user_id (user_id),
    CONSTRAINT fk_spaces_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: devices
-- Guarda el dispositivo real o simulado del usuario.
-- device_uid y api_token sirven para la API.
-- =========================================================

CREATE TABLE IF NOT EXISTS devices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    space_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    device_type VARCHAR(60) NULL DEFAULT 'Eden Air Core',
    device_uid VARCHAR(64) NOT NULL,
    api_token VARCHAR(80) NOT NULL,
    is_simulated TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    -- Estado mostrado en "Mis dispositivos": active | offline | pending | simulated
    status VARCHAR(20) NULL DEFAULT 'simulated',
    -- MAC de fábrica: SOLO identificador técnico, nunca credencial.
    -- La graba el propio equipo la primera vez que se presenta a la API.
    mac_address VARCHAR(32) NULL,
    notes VARCHAR(255) NULL,
    last_seen_at DATETIME NULL,
    last_command_sync_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_devices_uid (device_uid),
    UNIQUE KEY uq_devices_token (api_token),
    KEY idx_devices_user_id (user_id),
    KEY idx_devices_space_id (space_id),
    CONSTRAINT fk_devices_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_devices_space
        FOREIGN KEY (space_id) REFERENCES spaces(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: measurements
-- Guarda las mediciones del panel, simulador o API.
-- source puede ser: web, api, automation, seed.
-- =========================================================

CREATE TABLE IF NOT EXISTS measurements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    space_id INT UNSIGNED NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'web',
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    co2_ppm INT UNSIGNED NOT NULL,
    air_quality_index INT UNSIGNED NOT NULL,
    air_quality_label VARCHAR(30) NOT NULL,
    notes TEXT NULL,
    captured_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_measurements_device_date (device_id, captured_at),
    KEY idx_measurements_user_id (user_id),
    KEY idx_measurements_space_id (space_id),
    CONSTRAINT fk_measurements_device
        FOREIGN KEY (device_id) REFERENCES devices(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_measurements_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_measurements_space
        FOREIGN KEY (space_id) REFERENCES spaces(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: device_states
-- Guarda el estado actual del dispositivo:
-- modo automatico/manual y actuadores.
-- =========================================================

CREATE TABLE IF NOT EXISTS device_states (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id INT UNSIGNED NOT NULL,
    operating_mode VARCHAR(20) NOT NULL DEFAULT 'automatic',
    fan_state VARCHAR(10) NOT NULL DEFAULT 'off',
    aromatizer_state VARCHAR(10) NOT NULL DEFAULT 'off',
    alert_led_state VARCHAR(10) NOT NULL DEFAULT 'off',
    last_reason TEXT NULL,
    updated_by VARCHAR(40) NOT NULL DEFAULT 'system',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_device_states_device_id (device_id),
    CONSTRAINT fk_device_states_device
        FOREIGN KEY (device_id) REFERENCES devices(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: device_commands
-- Guarda los comandos pendientes o ejecutados para la API.
-- =========================================================

CREATE TABLE IF NOT EXISTS device_commands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id INT UNSIGNED NOT NULL,
    issued_by_user_id INT UNSIGNED NULL,
    source VARCHAR(30) NOT NULL DEFAULT 'web',
    command_type VARCHAR(30) NOT NULL,
    target_value VARCHAR(60) NOT NULL,
    payload TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    executed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_device_commands_device_status (device_id, status),
    KEY idx_device_commands_user_id (issued_by_user_id),
    CONSTRAINT fk_device_commands_device
        FOREIGN KEY (device_id) REFERENCES devices(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_device_commands_user
        FOREIGN KEY (issued_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: device_pairings  (ventanas de vinculacion)
-- El usuario aprieta "Conectar" y se abre una ventana de unos
-- minutos. La web muestra un QR con las credenciales del WiFi de
-- configuracion del equipo; el equipo que se presente a la API
-- durante esa ventana queda asociado a esa cuenta.
-- No hay codigos que tipear ni nada que buscar en la caja.
-- =========================================================

CREATE TABLE IF NOT EXISTS device_pairings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    -- Con este token el navegador pregunta "ya aparecio mi equipo?".
    token VARCHAR(64) NOT NULL,
    -- Credenciales que viajan adentro del QR (el WiFi que publica el equipo).
    ap_ssid VARCHAR(64) NOT NULL,
    ap_password VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'esperando',  -- esperando | vinculado | expirado | cancelado
    device_id INT UNSIGNED NULL,
    -- IP del navegador que abrio la ventana: desempata si hay varias abiertas.
    client_ip VARCHAR(45) NULL,
    expires_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pairing_token (token),
    KEY idx_pairing_estado (status, expires_at),
    KEY idx_pairing_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- TABLA: migrations  (control interno de CodeIgniter)
-- CodeIgniter anota aca que migraciones ya corrio en esta base.
-- Se crea y se completa a mano para que una base armada con este
-- archivo quede en el MISMO estado que una armada con
-- `php spark migrate`. Sin esto, CodeIgniter creeria que no corrio
-- ninguna e intentaria aplicarlas todas sobre un esquema ya completo.
--
-- Si agregas una migracion nueva, sumale una fila aca tambien.
-- =========================================================

CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version VARCHAR(255) NOT NULL,
    class VARCHAR(255) NOT NULL,
    `group` VARCHAR(255) NOT NULL,
    namespace VARCHAR(255) NOT NULL,
    time INT(11) NOT NULL,
    batch INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO migrations (version, class, `group`, namespace, time, batch)
SELECT * FROM (
    SELECT '2026-05-06-000001' v, 'App\\Database\\Migrations\\CreateTesinaSimulationSchema' c, 'default' g, 'App' n, UNIX_TIMESTAMP() t, 1 b
    UNION ALL SELECT '2026-05-29-000001', 'App\\Database\\Migrations\\AddApellidoToUsers',           'default', 'App', UNIX_TIMESTAMP(), 1
    UNION ALL SELECT '2026-05-31-000001', 'App\\Database\\Migrations\\EnsureUserAccountColumns',     'default', 'App', UNIX_TIMESTAMP(), 1
    UNION ALL SELECT '2026-05-31-000002', 'App\\Database\\Migrations\\CreateDeviceClaimSchema',      'default', 'App', UNIX_TIMESTAMP(), 1
    UNION ALL SELECT '2026-05-31-000003', 'App\\Database\\Migrations\\AllowMultipleSpacesPerUser',   'default', 'App', UNIX_TIMESTAMP(), 1
    UNION ALL SELECT '2026-08-02-000001', 'App\\Database\\Migrations\\ReplaceClaimCodesWithPairing', 'default', 'App', UNIX_TIMESTAMP(), 1
) AS nuevas
WHERE NOT EXISTS (SELECT 1 FROM migrations);

-- =========================================================
-- COMPATIBILIDAD CON BASES VIEJAS
-- Solo hace falta si venis de un esquema anterior, el del alta por
-- codigo de activacion. En una base nueva no hace nada.
-- =========================================================

DROP TABLE IF EXISTS device_activation_codes;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'devices' AND COLUMN_NAME = 'activation_code') > 0,
    'ALTER TABLE devices DROP COLUMN activation_code',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- RESUMEN RAPIDO DE USO
-- =========================================================
-- users:
--   login, registro, hash de contraseña.
--
-- spaces:
--   ambiente del usuario y limites configurados.
--
-- devices:
--   dispositivo vinculado al usuario, UID y token API.
--
-- measurements:
--   historial de temperatura, humedad, CO2 y calidad del aire.
--
-- device_states:
--   modo manual/automatico y estado de actuadores.
--
-- device_commands:
--   comandos para el dispositivo desde panel o automatizacion.
--
-- device_pairings:
--   ventanas de vinculacion abiertas al apretar "Conectar" (QR + espera).
--
-- migrations:
--   control interno de CodeIgniter (que migraciones ya se aplicaron).
--
-- =========================================================
-- COMO USAR ESTE ARCHIVO
-- =========================================================
-- Maquina nueva, base desde cero:
--   mysql -h 127.0.0.1 -P 3306 -u root < mysql_setup.sql
--   (ajusta el puerto al de tu MySQL; en XAMPP a veces es 3307)
--
-- Despues verifica que quedo al dia:
--   php spark migrate:status
--   Tiene que decir que no hay migraciones pendientes.
--
-- Maquina que ya tiene la base:
--   NO uses este archivo. Usa `php spark migrate`, que aplica solo
--   los cambios que falten y respeta los datos que ya tenes.
