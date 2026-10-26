<?php

namespace App\Commands;

use App\Models\DeviceModel;
use App\Models\DevicePairingModel;
use App\Models\MeasurementModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark edenair:limpiar — borra las cuentas que nadie usa.
 *
 * QUÉ ES UNA CUENTA "SIN USO"
 * Las tres cosas a la vez, durante los últimos --dias días:
 * - nadie inició sesión con ella (`users.last_login_at`; si nunca se anotó,
 *   cuenta la fecha de registro),
 * - ninguno de sus equipos reportó (`devices.last_seen_at`), o no tiene,
 * - y nunca inició una compra: los registros de pagos no se borran.
 *
 * QUÉ SE VA CON LA CUENTA
 * Las claves foráneas con ON DELETE CASCADE se llevan sus ambientes, equipos,
 * mediciones, estados y comandos. Las ventanas de vinculación no tienen clave
 * foránea, así que se borran a mano. Cada cuenta va en su propia transacción:
 * si una falla, no queda a medio borrar y las demás siguen.
 *
 * MEDICIONES DE MÁS
 * Cada equipo guarda solo sus últimas 15 lecturas (MeasurementModel::
 * POR_EQUIPO): el recorte se hace solo cada vez que llega una medición. Acá
 * se repasa en todos los equipos, para los que dejaron de reportar con
 * lecturas de antes de que existiera el recorte.
 *
 * De paso borra las ventanas de vinculación vencidas hace más de una semana.
 *
 * SIN --confirmar NO BORRA NADA: muestra la lista de lo que borraría.
 */
class LimpiarCuentas extends BaseCommand
{
    protected $group       = 'EdenAir';
    protected $name        = 'edenair:limpiar';
    protected $description = 'Borra las cuentas sin uso, las mediciones de más y las ventanas de vinculación vencidas.';
    protected $usage       = 'edenair:limpiar [--dias 180] [--confirmar]';
    protected $options     = [
        '--dias'      => 'Días sin ingresos ni equipos reportando para dar una cuenta por abandonada (180 por defecto, mínimo 30).',
        '--confirmar' => 'Borra de verdad. Sin esta opción solo muestra lo que borraría.',
    ];

    private const DIAS_POR_DEFECTO = 180;

    // Un "--dias 1" por error dejaría sin cuenta a cualquiera que no haya
    // entrado ayer.
    private const DIAS_MINIMOS = 30;

    // Una ventana dura minutos; una semana alcanza para revisar algún caso raro.
    private const DIAS_VENTANAS = 7;

    public function run(array $params)
    {
        // Ojo: CodeIgniter lee "--dias 90" (con espacio), no "--dias=90".
        $dias      = CLI::getOption('dias') ?? self::DIAS_POR_DEFECTO;
        $confirmar = CLI::getOption('confirmar') !== null;

        if (! is_numeric($dias) || (int) $dias < self::DIAS_MINIMOS) {
            CLI::error('--dias tiene que ser un número de al menos ' . self::DIAS_MINIMOS . '. Ejemplo: --dias 180');

            return EXIT_USER_INPUT;
        }

        $dias = (int) $dias;

        if (! $confirmar) {
            CLI::write('MODO PRUEBA: no se borra nada. Agregá --confirmar para borrar.', 'yellow');
            CLI::newLine();
        }

        // Primero las cuentas: sus mediciones se van con ellas y no hace
        // falta contarlas dos veces.
        $this->limpiarCuentas($dias, $confirmar);
        CLI::newLine();
        $this->limpiarMediciones($confirmar);
        CLI::newLine();
        $this->limpiarVentanas($confirmar);

        return EXIT_SUCCESS;
    }

    private function limpiarCuentas(int $dias, bool $confirmar): void
    {
        $limite   = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        $usuarios = new UserModel();
        $cuentas  = $usuarios->sinUsoDesde($limite);

        CLI::write("Cuentas sin uso desde el {$limite} ({$dias} días): " . count($cuentas), 'green');

        if ($cuentas === []) {
            return;
        }

        CLI::table(array_map(static fn (array $c): array => [
            $c['id'],
            $c['usuario'],
            $c['email'],
            $c['created_at'] ?? '-',
            $c['last_login_at'] ?? 'nunca anotado',
            $c['equipos'],
            $c['mediciones'],
        ], $cuentas), ['id', 'usuario', 'email', 'registro', 'último ingreso', 'equipos', 'mediciones']);

        if (! $confirmar) {
            return;
        }

        $db       = db_connect();
        $ventanas = new DevicePairingModel();
        $borradas = 0;

        foreach ($cuentas as $c) {
            $id = (int) $c['id'];

            $db->transStart();
            $ventanas->borrarDeUsuario($id);
            $usuarios->delete($id);
            $db->transComplete();

            if ($db->transStatus() === false) {
                CLI::error("No se pudo borrar la cuenta {$id} ({$c['usuario']}). Quedó como estaba.");

                continue;
            }

            $this->dejarConstancia(sprintf(
                'Cuenta %d borrada (%s, %s, %d equipo(s)): sin uso desde %s.',
                $id,
                $c['usuario'],
                $c['email'],
                $c['equipos'],
                $limite
            ));
            $borradas++;
        }

        CLI::write("Cuentas borradas: {$borradas}", 'green');
    }

    private function limpiarMediciones(bool $confirmar): void
    {
        $mediciones = new MeasurementModel();
        $equipos    = (new DeviceModel())->findColumn('id') ?? [];
        $total      = 0;

        foreach ($equipos as $deviceId) {
            $total += $confirmar
                ? $mediciones->conservarUltimas((int) $deviceId)
                : $mediciones->contarSobrantes((int) $deviceId);
        }

        CLI::write(
            ($confirmar ? 'Mediciones borradas' : 'Mediciones de más')
                . ' (se dejan las últimas ' . MeasurementModel::POR_EQUIPO . " de cada equipo): {$total}",
            'green'
        );
    }

    private function limpiarVentanas(bool $confirmar): void
    {
        $limite   = date('Y-m-d H:i:s', strtotime('-' . self::DIAS_VENTANAS . ' days'));
        $ventanas = new DevicePairingModel();

        if (! $confirmar) {
            CLI::write('Ventanas de vinculación vencidas hace más de ' . self::DIAS_VENTANAS . ' días: ' . $ventanas->contarVencidasAntesDe($limite), 'green');

            return;
        }

        CLI::write('Ventanas de vinculación borradas: ' . $ventanas->borrarVencidasAntesDe($limite), 'green');
    }

    /**
     * Anota una línea en writable/logs/limpieza-cuentas.log, por si alguien
     * pregunta por su cuenta. Archivo propio y no log_message(): en
     * producción el logger descarta todo lo que no sea error.
     */
    private function dejarConstancia(string $linea): void
    {
        file_put_contents(
            WRITEPATH . 'logs/limpieza-cuentas.log',
            date('Y-m-d H:i:s') . ' ' . $linea . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO
   - run($params)        → lee las opciones y corre las tres limpiezas (sin
                           --confirmar solo muestra)
   - limpiarCuentas()    → lista las cuentas sin uso y, con --confirmar, las
                           borra una por una en su propia transacción
   - limpiarMediciones() → cuenta o borra, equipo por equipo, las lecturas
                           que pasan de las últimas 15
   - limpiarVentanas()   → cuenta o borra las ventanas de vinculación viejas
   - dejarConstancia()   → anota cada cuenta borrada en
                           writable/logs/limpieza-cuentas.log

   De CodeIgniter:
   - BaseCommand         → clase base de los comandos de `php spark`; $group,
                           $name, $usage y $options arman la ayuda
                           (`php spark help edenair:limpiar`)
   - CLI::getOption()    → valor de una opción; null si no vino, true si vino
                           sin valor
   - CLI::table()        → imprime una tabla en la consola
   - findColumn('id')    → (CI4 Model) los valores de una sola columna, o
                           null si la tabla está vacía
   - transStart()/transComplete()/transStatus() → transacción: si algo falla
                           en el medio, se deshace todo lo de esa cuenta
   ============================================================================ */
