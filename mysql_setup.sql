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

ALTER TABLE users
ADD reset_token VARCHAR(64) NULL,
ADD reset_expires_at DATETIME NULL;

ALTER TABLE users
ADD COLUMN apellido VARCHAR(120) NOT NULL DEFAULT '' AFTER nombre;

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
    UNIQUE KEY uq_spaces_user_id (user_id),
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
    device_uid VARCHAR(64) NOT NULL,
    api_token VARCHAR(80) NOT NULL,
    is_simulated TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
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
