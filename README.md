# EdenAir - Tesina con CodeIgniter 4

Sistema inteligente de monitoreo y ambientacion automatica de espacios interiores.

El backend y la web estan terminados y esperan al hardware. La arquitectura final es:

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
- Conexion de dispositivos escaneando un QR generado en el momento, sin codigos
  ni pasos intermedios.
- Ambientes por espacio: oficina, aula, hogar, dormitorio o personalizable.
- Dashboard con temperatura, humedad, CO2 y calidad del aire.
- Estados de actuadores: ventilador, aromatizador y LED de alerta.
- Modo automatico y modo manual.
- Control manual desde la web.
- Guardado de comandos en MySQL.
- Logica de automatizacion basica.
- API lista para recibir mediciones futuras desde una ESP32 real.
- API lista para devolver comandos pendientes y marcar comandos ejecutados.
- Generador de codigos QR propio en PHP, sin dependencias externas.

## Puesta en marcha

1. Crear la base de datos:

```sql
CREATE DATABASE IF NOT EXISTS tesina_esp32
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
```

2. Crear tu `.env` a partir de la plantilla:

```bash
copy .env.example .env
```

Y ajustar los datos de conexion:

```dotenv
database.default.hostname = 127.0.0.1
database.default.database = tesina_esp32
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

> **El `.env` es de cada maquina, no del repo.** Esta en `.gitignore`, asi que
> no viaja por git: si viajara, le pisaria la configuracion a la otra maquina.
> Lo que si se versiona es `.env.example`, que es la lista de claves a completar.
>
> El puerto es lo que mas cambia: 3306 es lo habitual, pero si ya hay otro MySQL
> ocupando ese puerto, XAMPP suele quedar en 3307. Podes confirmarlo en
> `C:\xampp\mysql\bin\my.ini`, seccion `[mysqld]`.

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

## Trabajar en dos computadoras

**La base de datos NO viaja por git.** Git mueve archivos; la base vive adentro
del servidor MySQL de cada maquina. Podes hacer `push` y `pull` mil veces: no
cambia ninguna base por eso.

Lo que si viaja son las **migraciones**: archivos en
`app/Database/Migrations/` con las instrucciones para modificar el esquema. Son
una receta, no la comida. El archivo lo trae git; **cocinarlo lo tenes que
hacer vos en cada maquina**, con `php spark migrate`.

### Al llegar a la otra PC

```bash
git pull
```

Despues mira que migraciones le faltan (las pendientes salen con la columna
*Migrated On* vacia):

```bash
php spark migrate:status
```

Y aplica las que falten:

```bash
php spark migrate
```

Eso es todo. **No perdes datos**: una migracion cambia la estructura (tablas y
columnas), no el contenido. Tus usuarios, dispositivos y mediciones quedan.

### Por que es seguro repetirlo

Cada base tiene una tabla `migrations` donde CodeIgniter anota cuales ya corrio.
`spark migrate` la lee, ve cual falta y aplica **solo esa**. Si lo corres dos
veces seguidas, la segunda no hace nada.

### Comandos utiles

| Comando | Para que |
|---|---|
| `php spark migrate:status` | Ver en que estado esta la base y que falta aplicar |
| `php spark migrate` | Aplicar las migraciones pendientes |
| `php spark migrate:rollback` | Deshacer la ultima migracion (ojo: el codigo PHP tiene que volver atras tambien) |
| `mysql -u root < mysql_setup.sql` | Armar la base entera de cero, en una maquina nueva |

### Cosas para tener en cuenta

- **No commitees el `.env`.** Cada maquina tiene su puerto y sus credenciales.
- El esquema vive en **dos lugares**: `mysql_setup.sql` (crear de cero) y las
  migraciones (evolucionar una base que ya existe). Si tocas uno, tocá el otro.
- Si `migrate:status` da error o sale vacio en una maquina, esa base se creo con
  `mysql_setup.sql` y nunca uso migraciones. Revisalo antes de correr `migrate`,
  para no chocar con tablas que ya existen.

## Conexion del dispositivo (sin codigos)

No hay codigos de activacion ni nada que buscar en la caja. El usuario aprieta
**Conectar** y la web abre una *ventana de vinculacion* de 10 minutos, mostrando
un QR generado en ese momento con las credenciales del WiFi de configuracion del
equipo. El equipo que se presente a la API durante esa ventana queda asociado a
esa cuenta.

El QR se genera en el servidor con `app/Libraries/QrCode.php` (implementacion
propia del estandar ISO/IEC 18004): no hay Composer ni CDN, asi que la pantalla
funciona sin internet.

> El flujo viejo por codigo `EDEN-XXXX-XXXX` se elimino junto con la tabla
> `device_activation_codes`. Ver la migracion
> `2026-08-02-000001_ReplaceClaimCodesWithPairing`.

Ventanas abiertas ahora mismo:

```sql
SELECT id, user_id, status, expires_at FROM device_pairings
WHERE status = 'esperando' AND expires_at >= NOW();
```

## Flujo web

1. El usuario se registra e inicia sesion.
2. Si todavia no tiene dispositivos, ve la pantalla de bienvenida y elige
   conectar su equipo o comprar uno.
3. Aprieta **Conectar** y escanea el QR con el celular. El celular entra a la red
   del equipo, se abre su portal y ahi elige el WiFi de la casa. Cuando el equipo
   sale a internet llama a `POST api/devices/pair` y el sistema crea:
   - el ambiente (o reutiliza el que ya tenga la cuenta)
   - el dispositivo con su `device_uid` y su `api_token`
   - el estado inicial de actuadores
4. Desde el dashboard puede:
   - cargar mediciones manuales
   - cambiar entre modo automatico y manual
   - encender o apagar actuadores en modo manual
   - ejecutar comandos pendientes mientras no haya ESP32 conectada
5. El dashboard se actualiza con `fetch` sin recargar toda la pagina.

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

## Quien hace que: la web y el ESP32

Son **dos programas separados** que se hablan por HTTP. No comparten codigo.

| | Quien lo hace | Donde vive |
|---|---|---|
| Leer el sensor SCD41 | ESP32 | `firmware/sensor.py` |
| **Decidir que actuador prender** | **ESP32** | **`firmware/reglas.py`** |
| Mover los reles | ESP32 | `firmware/actuadores.py` |
| Definir los umbrales de cada ambiente | Usuario, desde la web | `/panel/ambientes` |
| Mandarle esos umbrales al equipo | Servidor | `DeviceConfigService.php` |
| Guardar el historial | Servidor | `MeasurementService.php` |
| Mostrar el dashboard | Servidor | `PanelService.php` |

**El servidor no decide nada.** Le manda los NUMEROS al equipo (los umbrales
que configuro el usuario) y el equipo aplica la REGLA. Consecuencias:

- Si se corta internet, el equipo **sigue regulando el ambiente**. Solo deja de
  reportar.
- Cambiar un rango desde la web **no requiere reprogramar la placa**.
- La reaccion a un CO2 alto no depende de la latencia de una peticion HTTP.

La unica excepcion es el **modo manual**: ahi el equipo no decide y solo obedece
las ordenes que el usuario manda desde el dashboard.

> El codigo del ESP32 esta en la carpeta `firmware/`, con su propio README que
> explica como abrirlo y subirlo con Thonny. Es MicroPython: no tiene nada que
> ver con el PHP.

## Como funciona la logica actual

- **Las mediciones son reales.** Llegan por la API desde el hardware (o desde
  Postman, mientras no haya hardware). El backend **no inventa lecturas**: si
  falta un valor o viene fuera del rango fisico posible, responde 422 en vez de
  rellenarlo.
- El indice de calidad de aire no es un sensor: es una cuenta sobre temperatura,
  humedad y CO2 comparados con los rangos del ambiente. La hacen **los dos
  lados con la misma formula**: el equipo para decidir, el servidor para
  mostrar. Estan verificadas como identicas, incluido el redondeo.
- El equipo informa que actuadores quedaron encendidos y por que. El servidor
  guarda ese estado y lo anota en el historial solo cuando algo cambia.
- Las ordenes manuales del usuario quedan **pendientes** en MySQL hasta que el
  equipo las consulta, las aplica fisicamente y las confirma. Recien ahi el
  panel muestra el cambio: nunca se da por hecho algo que el equipo no hizo.

## Tablas principales

- `users`
- `spaces`
- `devices`
- `measurements`
- `device_states`
- `device_commands`
- `device_pairings`

## Como conectar la ESP32 real en la siguiente etapa

La idea final es reutilizar exactamente esta base:

1. Sin WiFi configurado, la ESP32 levanta su punto de acceso con **exactamente**
   estas credenciales (son las que la web mete adentro del QR):
   - SSID: `EdenAir-Setup`
   - Clave: `edenair.setup`

   Si en el firmware se eligen otras, hay que cambiarlas tambien en el `.env`
   (`edenair.apSsid` / `edenair.apPassword`).
2. Sirve un portal cautivo en `192.168.4.1` donde el usuario elige el WiFi de la
   casa y pone su clave.
3. Ya conectada, pide sus credenciales con `HTTP POST`:
   - `/api/devices/pair` con `{"mac": "...", "firmware": "1.0.0"}`
   - **200** -> guarda `device_uid` y `api_token` y empieza a medir.
   - **202** -> el dueno todavia no apreto "Conectar": reintenta a los 15 s.
4. Envia las mediciones con `HTTP POST` a:
   - `/api/devices/{device_uid}/measurements`
5. El backend analiza la medicion y genera comandos si corresponde.
6. La ESP32 consulta comandos pendientes con `HTTP GET`:
   - `/api/devices/{device_uid}/commands/pending`
7. Cuando ejecuta una accion fisica, marca el comando como ejecutado con `HTTP POST`:
   - `/api/devices/{device_uid}/commands/{id}/executed`

## Ejemplo conceptual de futuro firmware

```text
Una sola vez (primer arranque):
  1. Levantar AP EdenAir-Setup + portal cautivo
  2. Recibir el WiFi de la casa y conectarse
  3. POST /api/devices/pair -> guardar device_uid y api_token

Ciclo normal:
  4. Leer sensores
  5. POST mediciones al backend
  6. GET comandos pendientes
  7. Ejecutar ventilador / aromatizador / LED
  8. POST confirmacion de ejecucion
  9. Repetir ciclo
```

## Notas

- No se usa Firebase, Supabase, Node.js, React ni Laravel.
- No existe conexion directa de la ESP32 a MySQL.
- La logica central pasa siempre por la API en CodeIgniter 4.
