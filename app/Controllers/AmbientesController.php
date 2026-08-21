<?php

namespace App\Controllers;

use App\Models\DeviceModel;
use App\Models\SpaceModel;
use App\Services\EnvironmentPresetService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Listado y edición de ambientes del usuario. Cada ambiente puede tener uno o
 * varios dispositivos asociados.
 *
 * Los tipos de espacio (oficina, aula, hogar, dormitorio, personalizable) y
 * sus rangos NO se escriben acá: salen de EnvironmentPresetService, que es el
 * único lugar donde se tocan.
 */
class AmbientesController extends BaseController
{
    // =========================================================================
    // LISTADO DE AMBIENTES
    // =========================================================================

    /** Lista los ambientes del usuario con sus rangos y dispositivos asignados. */
    public function index(): string
    {
        $userId  = $this->usuarioActual();
        $spaces  = new SpaceModel();
        $devices = new DeviceModel();
        $presets = new EnvironmentPresetService();

        $ambientes = $spaces->where('user_id', $userId)->orderBy('id', 'ASC')->findAll();

        $resultado = array_map(function (array $s) use ($devices, $presets): array {
            $devsRaw = $devices->where('space_id', (int) $s['id'])->findAll();
            $devs = array_map(static fn (array $d): array => [
                'id'   => (int) $d['id'],
                'name' => (string) $d['name'],
                'tipo' => (string) ($d['device_type'] ?? 'Eden Air Core'),
            ], $devsRaw);

            $tipo = (string) ($s['environment_type'] ?? 'hogar');

            return [
                'id'          => (int) $s['id'],
                'nombre'      => $presets->getDisplayName($s),
                'tipo_clave'  => $tipo,
                'tipo'        => $presets->getEnvironmentLabel($tipo),
                'tipo_icono'  => $presets->getPreset($tipo)['icono'],
                // true → los números son los del tipo; false → el usuario los movió.
                'sigue_tipo'  => $presets->siguePreset($s),
                'rangos'      => $presets->formatearRangos($s),
                'devices'     => $devs,
            ];
        }, $ambientes);

        return view('ambientes/index', [
            'ambientes' => $resultado,
            // Referencia "qué rango usa cada espacio": el mismo catálogo que
            // ofrece el formulario de edición.
            'catalogo'  => $presets->getCatalogo(),
        ]);
    }

    // =========================================================================
    // EDICIÓN DE UN AMBIENTE
    // Ambos métodos validan pertenencia: el ambiente debe existir Y ser del
    // usuario logueado. Sin ese chequeo, cualquiera podría editar ambientes
    // ajenos cambiando el id de la URL.
    // =========================================================================

    /** Muestra el formulario: tipo de espacio, nombre y rangos. */
    public function editar(int $id): string|RedirectResponse
    {
        $ambiente = $this->ambienteDelUsuario($id);

        if ($ambiente === null) {
            return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
        }

        $presets = new EnvironmentPresetService();

        return view('ambientes/editar', [
            'ambiente'   => $ambiente,
            'catalogo'   => $presets->getCatalogo(),
            'nombre'     => $presets->getDisplayName($ambiente),
            'sigue_tipo' => $presets->siguePreset($ambiente),
        ]);
    }

    /**
     * Guarda tipo, nombre y rangos. Validaciones de coherencia: el tipo tiene
     * que existir en el catálogo, min < max (temperatura y humedad) y CO₂
     * máximo positivo. El nombre puede quedar vacío: entonces el ambiente se
     * muestra con la etiqueta de su tipo ("Oficina", "Aula"...).
     */
    public function actualizar(int $id): RedirectResponse
    {
        $ambiente = $this->ambienteDelUsuario($id);

        if ($ambiente === null) {
            return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
        }

        $presets = new EnvironmentPresetService();
        $tipo    = (string) $this->request->getPost('environment_type');

        if (! $presets->existePreset($tipo)) {
            return $this->volverAlForm($id, 'Elegí uno de los tipos de espacio disponibles.');
        }

        // buildSpaceData mezcla lo que mandó el form con el preset del tipo:
        // si un número viene vacío, queda el del tipo en vez de un 0.
        $datos = $presets->buildSpaceData([
            'environment_type' => $tipo,
            'custom_name'      => $this->request->getPost('custom_name'),
            'min_temperature'  => $this->request->getPost('min_temperature'),
            'max_temperature'  => $this->request->getPost('max_temperature'),
            'min_humidity'     => $this->request->getPost('min_humidity'),
            'max_humidity'     => $this->request->getPost('max_humidity'),
            'max_co2'          => $this->request->getPost('max_co2'),
        ]);

        if ($datos['min_temperature'] >= $datos['max_temperature']) {
            return $this->volverAlForm($id, 'La temperatura mínima debe ser menor que la máxima.');
        }
        if ($datos['min_humidity'] >= $datos['max_humidity']) {
            return $this->volverAlForm($id, 'La humedad mínima debe ser menor que la máxima.');
        }
        if ($datos['min_humidity'] < 0 || $datos['max_humidity'] > 100) {
            return $this->volverAlForm($id, 'La humedad se mide en porcentaje: tiene que quedar entre 0 % y 100 %.');
        }
        if ($datos['max_co2'] <= 0) {
            return $this->volverAlForm($id, 'El límite de CO₂ debe ser mayor que cero.');
        }

        (new SpaceModel())->update($id, $datos);

        return redirect()->to('/panel/ambientes')
            ->with('success', 'Ambiente actualizado: ' . $presets->getDisplayName($datos) . '.');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Devuelve el user_id guardado en sesión por el login. */
    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }

    /** El ambiente si existe y es del usuario logueado; si no, null. */
    private function ambienteDelUsuario(int $id): ?array
    {
        $ambiente = (new SpaceModel())->find($id);

        if (! $ambiente || (int) $ambiente['user_id'] !== $this->usuarioActual()) {
            return null;
        }

        return $ambiente;
    }

    /** Vuelve al formulario con lo escrito y el motivo del rechazo. */
    private function volverAlForm(int $id, string $mensaje): RedirectResponse
    {
        return redirect()->to('/panel/ambientes/' . $id . '/editar')->withInput()->with('error', $mensaje);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Métodos públicos (responden a rutas):
   - index()          → lista ambientes (rangos formateados, tipo, dispositivos)
                        + el catálogo de tipos como referencia
   - editar($id)      → muestra el form: tipo, nombre y rangos
   - actualizar($id)  → guarda (valida tipo, min/max y CO₂)

   Helpers privados:
   - usuarioActual()      → user_id de la sesión
   - ambienteDelUsuario() → busca el ambiente y chequea que sea del usuario
   - volverAlForm()       → redirect al form con withInput() y mensaje de error

   Métodos de Model (CI4) usados acá:
   - find($id)        → busca un registro por su clave primaria
   - findAll()        → devuelve todas las filas que cumplen los where()
   - where()/orderBy()→ arman la consulta SQL de a partes (query builder)
   - update($id, $datos) → UPDATE de los campos permitidos en allowedFields

   Funciones de PHP usadas acá:
   - array_map()      → transforma cada fila cruda en un array listo para la vista
   ============================================================================ */
