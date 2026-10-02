-- Renombra la base tesina_esp32 a edenair, con todos sus datos.
-- MariaDB no tiene RENAME DATABASE: se crea la nueva y se mudan las tablas
-- en un solo RENAME TABLE (las claves foraneas se mudan solas).
-- En Windows MariaDB guarda los nombres de base en minuscula, por eso "edenair".

CREATE DATABASE IF NOT EXISTS edenair
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

RENAME TABLE
  tesina_esp32.users           TO edenair.users,
  tesina_esp32.spaces          TO edenair.spaces,
  tesina_esp32.devices         TO edenair.devices,
  tesina_esp32.device_pairings TO edenair.device_pairings,
  tesina_esp32.device_states   TO edenair.device_states,
  tesina_esp32.device_commands TO edenair.device_commands,
  tesina_esp32.measurements    TO edenair.measurements,
  tesina_esp32.purchases       TO edenair.purchases,
  tesina_esp32.migrations      TO edenair.migrations;

-- Ya vacia: se puede borrar.
DROP DATABASE tesina_esp32;
