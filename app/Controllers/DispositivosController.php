<?php

namespace App\Controllers;

use App\Services\DeviceClaimService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Gestión de dispositivos del usuario: listado ("Mis dispositivos") y alta por
 * código de activación (asistente "Conectá tu Eden Air").
 *
 * Rutas (grupo panel, filtro auth):
 *   GET  panel/dispositivos            -> index()
 *   GET  panel/dispositivos/agregar    -> agregar()
 *   GET  panel/dispositivos/validar    -> validar()  (live check, JSON, sin CSRF)
 *   POST panel/dispositivos            -> guardar()
 */
class DispositivosController extends BaseController
{
    public function index(): string
    {
        $servicio = new DeviceClaimService();

        return view('dispositivos/index', [
            'dispositivos' => $servicio->listarDeUsuario($this->usuarioActual()),
        ]);
    }

    public function agregar(): string
    {
        $servicio = new DeviceClaimService();
        $userId   = $this->usuarioActual();
        $presets  = new \App\Services\EnvironmentPresetService();

        $ambientesExistentes = (new \App\Models\SpaceModel())
            ->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->findAll();

        $ambientesExistentes = array_map(function (array $s) use ($presets): array {
            return [
                'id'    => (int) $s['id'],
                'label' => $presets->getDisplayName($s),
                'tipo'  => $presets->getEnvironmentLabel((string) ($s['environment_type'] ?? 'hogar')),
            ];
        }, $ambientesExistentes);

        return view('dispositivos/agregar', [
            'tipos'              => $servicio->tiposDispositivo(),
            'espacios'           => $servicio->espacios(),
            'ambientesExistentes'=> $ambientesExistentes,
        ]);
    }

    /**
     * Validación en vivo del código (paso 1 del asistente). GET → exento de CSRF.
     */
    public function validar(): ResponseInterface
    {
        $codigo     = (string) $this->request->getGet('code');
        $inspeccion = (new DeviceClaimService())->inspeccionarCodigo($codigo);

        return $this->response->setJSON([
            'ok'           => $inspeccion['ok'],
            'estado'       => $inspeccion['estado'],
            'mensaje'      => $inspeccion['mensaje'],
            'device_type'  => $inspeccion['code']['device_type'] ?? null,
            'default_name' => $inspeccion['code']['default_name'] ?? null,
        ]);
    }

    public function guardar(): RedirectResponse
    {
        $datos = [
            'code'         => (string) $this->request->getPost('code'),
            'name'         => trim((string) $this->request->getPost('name')),
            'device_type'  => (string) $this->request->getPost('device_type'),
            'space_mode'   => (string) $this->request->getPost('space_mode'),
            'space_id'     => (int)    $this->request->getPost('space_id'),
            'space'        => (string) $this->request->getPost('space'),
            'space_custom' => trim((string) $this->request->getPost('space_custom')),
            'notes'        => trim((string) $this->request->getPost('notes')),
        ];

        $reglas = [
            'code'        => 'required|max_length[40]',
            'name'        => 'required|min_length[2]|max_length[60]',
            'device_type' => 'required|max_length[60]',
            'notes'       => 'permit_empty|max_length[255]',
        ];

        if (! $this->validateData($datos, $reglas)) {
            return redirect()->to('/panel/dispositivos/agregar')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $servicio = new DeviceClaimService();

        // Validar selección de ambiente: existente o nuevo.
        if ($datos['space_mode'] === 'existing') {
            if ($datos['space_id'] <= 0) {
                return redirect()->to('/panel/dispositivos/agregar')
                    ->withInput()
                    ->with('error', 'Seleccioná un ambiente existente para el dispositivo.');
            }
            // El servicio valida pertenencia.
        } else {
            // Modo "nuevo": el `space` viene del catálogo.
            if (! $servicio->esEspacioValido($datos['space'])) {
                return redirect()->to('/panel/dispositivos/agregar')
                    ->withInput()
                    ->with('error', 'Seleccioná un ambiente válido para el dispositivo.');
            }
        }

        try {
            $resultado = $servicio->vincular($this->usuarioActual(), $datos);
        } catch (RuntimeException $e) {
            return redirect()->to('/panel/dispositivos/agregar')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->to('/panel/dispositivos')
            ->with('success', '“' . $resultado['device']['name'] . '” quedó vinculado a tu cuenta.');
    }

    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }
}
