<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use App\Services\DeviceProvisioningService;
use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();
        $provisioningService = new DeviceProvisioningService();

        $user = $userModel
            ->where('email', 'demo@edenair.com')
            ->orWhere('usuario', 'demo')
            ->first();

        $payload = [
            'nombre'        => 'Usuario Demo',
            'email'         => 'demo@edenair.com',
            'usuario'       => 'demo',
            'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
        ];

        if ($user) {
            $userModel->update($user['id'], $payload);
            $userId = (int) $user['id'];
        } else {
            $userId = (int) $userModel->insert($payload);
        }

        $provisioningService->ensureUserSetup($userId, [
            'environment_type' => 'oficina',
        ]);
    }
}
