<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class GameAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('game.admin_email');
        $password = config('game.admin_password');

        if (! $email && ! $password) {
            return;
        }

        if (! $email || ! $password) {
            throw new RuntimeException('Set both GAME_ADMIN_EMAIL and GAME_ADMIN_PASSWORD to provision the game administrator.');
        }

        $email = Str::lower($email);
        $username = config('game.admin_username');
        $existingUsername = User::where('username', $username)->where('email', '!=', $email)->exists();

        if ($existingUsername) {
            throw new RuntimeException('GAME_ADMIN_USERNAME is already in use. Choose a different username.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $username,
                'username' => $username,
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );
    }
}
