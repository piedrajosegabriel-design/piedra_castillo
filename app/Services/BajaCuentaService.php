<?php

namespace App\Services;

use App\Models\DevicePairingModel;
use App\Models\PurchaseModel;
use App\Models\UserModel;

/**
 * BajaCuentaService — borra una cuenta y todo lo que cuelga de ella.
 *
 * QUIÉN LO USA
 * El propio usuario, desde /panel/perfil. Es el derecho de supresión de la
 * Ley 25.326: puede irse sin pedirle permiso a nadie.
 *
 * QUÉ SE BORRA
 * La cuenta, sus ambientes, sus equipos, sus mediciones, los estados y las
 * órdenes. Casi todo se va solo: las claves foráneas hacia `users` están con
 * ON DELETE CASCADE, así que basta con borrar la fila del usuario. Las
 * ventanas de vinculación NO tienen clave foránea, así que se borran a mano.
 *
 * QUÉ NO SE BORRA
 * Las compras. Son el respaldo de una operación de dinero, así que en vez de
 * borrarlas se les saca el `user_id` (ver PurchaseModel::anonimizarDeUsuario):
 * queda el monto, la fecha y el número de pago, y nada que identifique a una
 * persona. Si se borraran, no habría forma de responder por un pago hecho.
 *
 * TODO O NADA
 * Las tres cosas van en una transacción. Si algo falla, la cuenta queda como
 * estaba: es peor una cuenta a medio borrar (sin equipos pero con login) que
 * una que no se borró y lo dice.
 *
 * Es la misma lógica que usa `php spark edenair:limpiar` para las cuentas
 * abandonadas, salvo que la limpieza directamente saltea las cuentas con
 * compras y acá no hace falta, porque la compra se conserva anonimizada.
 */
class BajaCuentaService
{
    /**
     * Borra la cuenta $userId.
     *
     * Devuelve true si se borró. false significa que no se tocó nada: o la
     * cuenta no existe, o la transacción falló.
     */
    public function eliminar(int $userId): bool
    {
        $usuarios = new UserModel();

        if ($usuarios->find($userId) === null) {
            return false;
        }

        $db = db_connect();

        $db->transStart();

        (new PurchaseModel())->anonimizarDeUsuario($userId);
        (new DevicePairingModel())->borrarDeUsuario($userId);
        $usuarios->delete($userId);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'No se pudo borrar la cuenta {id}: la transaccion fallo.', ['id' => $userId]);

            return false;
        }

        // Sin datos de la persona: solo el id, que ya no existe en ninguna
        // tabla. Sirve para contestar "¿cuándo se dio de baja esta cuenta?".
        log_message('info', 'Cuenta {id} borrada a pedido del usuario.', ['id' => $userId]);

        return true;
    }
}

/* ============================================================================
   GLOSARIO DE ESTE ARCHIVO
   - eliminar($userId)        → borra la cuenta entera; true si se borró
   - transStart()/Complete()  → (CI4) todo o nada
   - transStatus()            → (CI4) false si algo falló adentro
   - ON DELETE CASCADE        → (MySQL) borrar el usuario se lleva sus filas
                                en spaces, devices, measurements y commands
   ============================================================================ */
