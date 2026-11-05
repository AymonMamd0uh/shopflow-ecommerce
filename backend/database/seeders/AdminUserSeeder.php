<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('shopflow.admin.email');
        $password = config('shopflow.admin.password');
        $name = config('shopflow.admin.name');

        if (empty($email) || empty($password)) {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be configured.'
            );
        }

        $admin = User::firstOrNew([
            'email' => $email,
        ]);

        if (! $admin->exists) {
            $admin->name = $name;
            $admin->password = Hash::make($password);
        }

        $admin->role = 'admin';
        $admin->save();
    }
}