<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            return;
        }

        if (User::where('email', strtolower($email))->exists()) {
            return;
        }

        $usuario = new User;
        $usuario->name = env('ADMIN_NAME', 'Administrador');
        $usuario->email = strtolower($email);
        $usuario->password = $password;
        $usuario->role = UserRole::Administrador;
        $usuario->is_active = true;
        $usuario->save();
    }
}
