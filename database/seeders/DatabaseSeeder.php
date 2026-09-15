<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Public self-registration is disabled (see FortifyServiceProvider),
     * so the very first admin account has to come from somewhere - this
     * creates one, idempotently (firstOrCreate keyed on email), so running
     * `php artisan db:seed` again never duplicates it or resets an
     * already-changed password. Every subsequent staff/admin account
     * should be created by an admin, not by re-running this seeder.
     *
     * Change ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in .env before first
     * deploy - or just log in with the default below once and change the
     * password immediately from the profile page.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@retirementportal.test')],
            [
                'name' => 'Portal Administrator',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'change-me-now')),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
