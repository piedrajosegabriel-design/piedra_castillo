<?php

namespace App\Controllers;

use App\Models\DeviceModel;
use App\Models\SpaceModel;
use App\Services\EnvironmentPresetService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Listado y edición de ambientes del usuario. Cada ambiente puede tener uno o
 * varios dispositivos asociados.
 */
class AmbientesController extends BaseController
{
    public function index(): string
    {
        $userId = $this->usuarioActual();
        $spaces = new SpaceModel();
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

            return [
                'id'         => (int) $s['id'],
                'nombre'     => $presets->getDisplayName($s),
                'tipo'       => $presets->getEnvironmentLabel((string) ($s['environment_type'] ?? 'hogar')),
                'rango_temp' => sprintf('%.0f° a %.0f°', (float) $s['min_temperature'], (float) $s['max_temperature']),
                'rango_hum'  => sprintf('%.0f%% a %.0f%%', (float) $s['min_humidity'],   (float) $s['max_humidity']),
                'max_co2'    => (int) $s['max_co2'],
                'devices'    => $devs,
            ];
        }, $ambientes);

        return view('ambientes/index', [
            'ambientes' => $resultado,
        ]);
    }

    public function editar(int $id): string|RedirectResponse
    {
        $userId  = $this->usuarioActual();
        $spaces  = new SpaceModel();
        $ambiente = $spaces->find($id);

        if (! $ambiente || (int) $ambiente['user_id'] !== $userId) {
            return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
        }

        return view('ambientes/editar', [
            'ambiente' => $ambiente,
        ]);
    }

    public function actualizar(int $id): RedirectResponse
    {
        $userId   = $this->usuarioActual();
        $spaces   = new SpaceModel();
        $ambiente = $spaces->find($id);

        if (! $ambiente || (int) $ambiente['user_id'] !== $userId) {
            return redirect()->to('/panel/ambientes')->with('error', 'El ambiente no existe o no te pertenece.');
        }

        $datos = [
            'custom_name'     => trim((string) $this->request->getPost('custom_name')),
            'min_temperature' => (float) $this->request->getPost('min_temperature'),
            'max_temperature' => (float) $this->request->getPost('max_temperature'),
            'min_humidity'    => (float) $this->request->getPost('min_humidity'),
            'max_humidity'    => (float) $this->request->getPost('max_humidity'),
            'max_co2'         => (int) $this->request->getPost('max_co2'),
        ];

        if ($datos['min_temperature'] >= $datos['max_temperature']) {
            return redirect()->to('/panel/ambientes/' . $id . '/editar')->withInput()
                ->with('error', 'La temperatura mínima debe ser menor que la máxima.');
        }
        if ($datos['min_humidity'] >= $datos['max_humidity']) {
            return redirect()->to('/panel/ambientes/' . $id . '/editar')->withInput()
                ->with('error', 'La humedad mínima debe ser menor que la máxima.');
        }
        if ($datos['max_co2'] <= 0) {
            return redirect()->to('/panel/ambientes/' . $id . '/editar')->withInput()
                ->with('error', 'El límite de CO₂ debe ser mayor que cero.');
        }

        $spaces->update($id, $datos);

        return redirect()->to('/panel/ambientes')->with('success', 'Ambiente actualizado.');
    }

    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }
}
