<?php

namespace App\Controllers;

use App\Services\AutomationService;
use App\Services\CommandService;
use App\Services\DeviceProvisioningService;
use App\Services\PanelService;
use App\Services\SimulationService;
use CodeIgniter\HTTP\RedirectResponse;

class PanelController extends BaseController
{
    private const MODOS = ['automatic', 'manual'];
    private const ACTUADORES = ['fan', 'aromatizer', 'alert_led'];
    private const VALORES_ACTUADOR = ['on', 'off'];

    public function index(): string|RedirectResponse
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        return view('panel', [
            'panel' => $this->crearPanel(),
        ]);
    }

    public function guardarMedicion()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $datos = $this->request->getPost();

        if (! $this->validateData($datos, [
            'temperature'       => 'permit_empty|decimal',
            'humidity'          => 'permit_empty|decimal',
            'co2_ppm'           => 'permit_empty|integer',
            'air_quality_index' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'notes'             => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->to('/panel')
                ->withInput()
                ->with('error', implode(' ', array_values($this->validator->getErrors())));
        }

        ['device' => $device, 'space' => $space] = $this->obtenerContexto();

        $resultado = (new SimulationService())->createMeasurement($device, $space, 'web', $datos);

        return redirect()->to('/panel')
            ->with('success', 'Medicion registrada correctamente. ' . $resultado['automation']['summary']);
    }

    public function cambiarModo()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $modo = (string) $this->request->getPost('mode');

        if (! in_array($modo, self::MODOS, true)) {
            return redirect()->to('/panel')->with('error', 'El modo seleccionado no es valido.');
        }

        ['device' => $device, 'space' => $space] = $this->obtenerContexto();

        (new CommandService())->changeOperatingMode((int) $device['id'], $modo, $this->usuarioActual());

        $mensaje = $modo === 'manual'
            ? 'Modo manual activado.'
            : 'Modo automatico activado.';

        if ($modo === 'automatic') {
            $automatico = (new AutomationService())->processLatestMeasurement($device, $space);
            $mensaje   .= ' ' . $automatico['summary'];
        }

        return redirect()->to('/panel')->with('success', $mensaje);
    }

    public function cambiarActuador()
    {
        if ($redirect = $this->redireccionarSiFaltaAmbiente()) {
            return $redirect;
        }

        $actuador = (string) $this->request->getPost('actuator');
        $valor    = (string) $this->request->getPost('value');

        if (! in_array($actuador, self::ACTUADORES, true) || ! in_array($valor, self::VALORES_ACTUADOR, true)) {
            return redirect()->to('/panel')->with('error', 'La accion seleccionada no es valida.');
        }

        ['device' => $device] = $this->obtenerContexto();
        $estado = (new CommandService())->getStateByDeviceId((int) $device['id']);

        if (($estado['operating_mode'] ?? 'automatic') !== 'manual') {
            return redirect()->to('/panel')->with('error', 'Activa el modo manual para controlar actuadores.');
        }

        (new CommandService())->queueAndExecuteManualCommand(
            (int) $device['id'],
            $actuador,
            $valor,
            $this->usuarioActual()
        );

        return redirect()->to('/panel')->with('success', 'Accion aplicada correctamente.');
    }

    private function crearPanel(): array
    {
        $userId = $this->usuarioActual();
        (new DeviceProvisioningService())->ensureUserSetup($userId, [], false);

        return (new PanelService())->obtenerDatos($userId);
    }

    private function obtenerContexto(): array
    {
        $panel = $this->crearPanel();

        return [
            'device' => $panel['device_raw'],
            'space'  => $panel['space_raw'],
        ];
    }

    private function usuarioActual(): int
    {
        return (int) session()->get('user_id');
    }

    private function redireccionarSiFaltaAmbiente(): ?RedirectResponse
    {
        if ((new DeviceProvisioningService())->hasConfiguredSpace($this->usuarioActual())) {
            return null;
        }

        return redirect()->to('/panel/ambiente')
            ->with('error', 'Primero elige el ambiente que deseas monitorear.');
    }
}
