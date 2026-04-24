-- =========================================================
-- EDENAIR - SETUP MYSQL (COPIAR Y PEGAR)
-- =========================================================

CREATE DATABASE IF NOT EXISTS tesina_esp32
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- A partir de aqui trabajamos dentro de la base del proyecto.
USE tesina_esp32;

-- Tabla de usuarios para login/register
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

-- Tabla opcional para futuro: ambientes del proyecto
CREATE TABLE IF NOT EXISTS ambientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nombre_ambiente ENUM('aula', 'oficina', 'hogar', 'dormitorio') NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_ambientes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Tabla opcional para futuro: datos leidos por ESP32
CREATE TABLE IF NOT EXISTS lecturas_ambientales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ambiente_id INT UNSIGNED NOT NULL,
    temperatura DECIMAL(5,2) NULL,
    humedad DECIMAL(5,2) NULL,
    co2_ppm DECIMAL(8,2) NULL,
    ruido_db DECIMAL(6,2) NULL,
    estado_general VARCHAR(30) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lecturas_ambiente
        FOREIGN KEY (ambiente_id) REFERENCES ambientes(id)
        ON DELETE CASCADE
);

-- Usuario de prueba
-- password = 123456
-- Se inserta o actualiza para tener una cuenta demo lista al levantar la base.
INSERT INTO users (nombre, email, usuario, password_hash, created_at, updated_at)
VALUES (
    'Usuario Demo',
    'demo@edenair.com',
    'demo',
    '$2y$10$gBE5BRMBQIJV/.elcxWoiOKP2Qo2VArJC979i98c/ymVUtYnBiKgO',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    -- Si el usuario demo ya existe, refrescamos sus datos base.
    nombre = VALUES(nombre),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    updated_at = NOW();
