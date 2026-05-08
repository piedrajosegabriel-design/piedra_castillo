# EdenAir - Base simulada para tesina con CodeIgniter 4

Sistema inteligente de monitoreo y ambientacion automatica de espacios interiores.

Esta primera etapa funciona sin hardware real y deja preparada la arquitectura final:

`ESP32 -> API en CodeIgniter 4 -> MySQL`

`Usuario -> Web en CodeIgniter 4 -> MySQL`

## Tecnologias

- PHP 8.2
- CodeIgniter 4
- MySQL
- Programacion orientada a objetos
- HTML
- CSS
- JavaScript simple
- Fetch/AJAX

## Lo que ya incluye esta etapa

- Registro e inicio de sesion.
- Sesiones de usuario.
- Seleccion de ambiente: oficina, aula, hogar, dormitorio o personalizable.
- Creacion automatica de un dispositivo ESP32 simulado por usuario.
- Dashboard con temperatura, humedad, CO2 y calidad del aire.
- Estados de actuadores: ventilador, aromatizador y LED de alerta.
- Modo automatico y modo manual.
- Control manual desde la web.
- Guardado de comandos en MySQL.
- Logica de automatizacion basica.
- API lista para recibir mediciones futuras desde una ESP32 real.
- API lista para devolver comandos pendientes y marcar comandos ejecutados.
- Seeders con usuario demo y mediciones iniciales.

## Puesta en marcha

1. Crear la base de datos:

```sql
CREATE DATABASE IF NOT EXISTS tesina_esp32
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
```

2. Revisar `.env` y confirmar estos datos:

```dotenv
database.default.hostname = localhost
database.default.database = tesina_esp32
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

3. Ejecutar migraciones y seeder:

```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

Si en PowerShell `php` no esta en el `PATH`, puedes usar directamente:

```bash
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark db:seed DatabaseSeeder
```

4. Abrir en navegador:

`http://localhost/piedra_castillo/public/`

## Usuario demo

- Usuario: `demo`
- Contrasena: `123456`

## Flujo web

1. El usuario se registra.
2. El sistema crea automaticamente:
   - su ambiente
   - su ESP32 simulada
   - el estado inicial de actuadores
   - mediciones precargadas
3. Desde el dashboard puede:
   - cargar mediciones simuladas
   - cambiar entre modo automatico y manual
   - encender o apagar actuadores en modo manual
   - ejecutar comandos pendientes para simular el trabajo de la ESP32
4. El dashboard se actualiza con `fetch` sin recargar toda la pagina.

## Endpoints API para Postman

Cada usuario tiene un `device_uid` y un `X-Device-Token` visibles en el dashboard.

### 1. Enviar medicion simulando una ESP32

`POST /public/api/devices/{device_uid}/measurements`

Header:

```http
X-Device-Token: TU_TOKEN
Content-Type: application/json
```

Body:

```json
{
  "temperature": 28.4,
  "humidity": 67,
  "co2_ppm": 1280,
  "air_quality_index": 42,
  "notes": "Prueba desde Postman"
}
```

### 2. Consultar comandos pendientes

`GET /public/api/devices/{device_uid}/commands/pending`

Header:

```http
X-Device-Token: TU_TOKEN
```

### 3. Marcar comando como ejecutado

`POST /public/api/devices/{device_uid}/commands/{id}/executed`

Header:

```http
X-Device-Token: TU_TOKEN
```

## Como funciona la simulacion actual

- Las mediciones pueden nacer desde seeders, desde formularios web o desde Postman.
- Si el modo esta en automatico, el backend evalua umbrales del ambiente y genera comandos.
- Los comandos quedan guardados en MySQL.
- La ejecucion de comandos puede simularse desde la web o marcandolos por API.
- En modo manual, el usuario controla actuadores desde el dashboard y cada accion tambien queda registrada.

## Tablas principales

- `users`
- `spaces`
- `devices`
- `measurements`
- `device_states`
- `device_commands`

## Como conectar la ESP32 real en la siguiente etapa

La idea final es reutilizar exactamente esta base:

1. La ESP32 medira temperatura, humedad, CO2 y otras variables.
2. La ESP32 enviara esas mediciones con `HTTP POST` al endpoint:
   - `/api/devices/{device_uid}/measurements`
3. El backend analizara la medicion y generara comandos si corresponde.
4. La ESP32 consultara comandos pendientes con `HTTP GET`:
   - `/api/devices/{device_uid}/commands/pending`
5. Cuando ejecute una accion fisica, marcara el comando como ejecutado con `HTTP POST`:
   - `/api/devices/{device_uid}/commands/{id}/executed`

## Ejemplo conceptual de futuro firmware

```text
1. Leer sensores
2. POST mediciones al backend
3. GET comandos pendientes
4. Ejecutar ventilador / aromatizador / LED
5. POST confirmacion de ejecucion
6. Repetir ciclo
```

## Notas

- No se usa Firebase, Supabase, Node.js, React ni Laravel.
- No existe conexion directa de la ESP32 a MySQL.
- La logica central pasa siempre por la API en CodeIgniter 4.
