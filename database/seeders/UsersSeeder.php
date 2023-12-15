<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        User::create([
            'id' => 1,
            'name' => 'admin',
            'email' => 'alf@drollinger.info',
            'email_verified_at' => null,
            'password' => '$2y$10$xkh.Ak433tGhMPt7FRqhXOeoFdQD2DvLnjJAWEqZR32i293.tfKKC',
            'remember_token' => 'qLkHQBqXiHQp1D0ahyHAKN1biSto8E5iVwo9fAfuOVISwQyXalx8xOtM6Iwg',
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-11-30 18:22:40',
            'updated_at' => '2023-11-30 18:22:40',
        ]);

        User::create([
            'id' => 2,
            'name' => 'Alf',
            'email' => 'alf@alf-drollinger.com',
            'email_verified_at' => null,
            'password' => '$2y$10$2tmqvVbAFqK5z6knCEKo5.0.nCkvDyiM/jzhtYiWEZz6YB23nrKoW',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:37:56',
            'updated_at' => '2023-12-14 08:37:56',
        ]);

        User::create([
            'id' => 3,
            'name' => 'Aziz',
            'email' => 'aziz.gasim@heco.de',
            'email_verified_at' => null,
            'password' => '$2y$10$OdeNKC.5jTffdRAELig10OoL4pOr5jjMRU8s8RBNNzWVJsXqMiHKm',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:41:04',
            'updated_at' => '2023-12-14 08:41:04',
        ]);

        User::create([
            'id' => 4,
            'name' => 'Kim',
            'email' => 'kim.speer@co-it.eu',
            'email_verified_at' => null,
            'password' => '$2y$10$2pGnLlx66g.McfNrTFSIuOuFtihJ5AD7uZBnSL0nrl1Um6oeEd69e',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:42:11',
            'updated_at' => '2023-12-14 08:42:11',
        ]);

        User::create([
            'id' => 5,
            'name' => 'Reinhold',
            'email' => 'reinhold.jesse@heco.de',
            'email_verified_at' => null,
            'password' => '$2y$10$YPlslkcT31C1/zOxB.O88OFq6S1jBpHSj5qAAzGFoAaF9Maie5rUG',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:42:45',
            'updated_at' => '2023-12-14 08:42:45',
        ]);

        User::create([
            'id' => 6,
            'name' => 'Moox Testuser',
            'email' => 'dev@moox.org',
            'email_verified_at' => null,
            'password' => '$2y$10$lEVhRO6vJi.stWGfp7OzfOPvrBhZx.QCxsKcY89rN1Yr.VLxF5WQO',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:43:34',
            'updated_at' => '2023-12-14 08:43:34',
        ]);

        User::create([
            'id' => 7,
            'name' => 'Moox Customer',
            'email' => 'webdeveloper@heco.de',
            'email_verified_at' => null,
            'password' => '$2y$10$fTxAmu3UTANd8mIQQHxQAu5qfVm9YBcqexFmhYLAsMez0YtFTVafO',
            'remember_token' => null,
            'current_team_id' => null,
            'profile_photo_path' => null,
            'created_at' => '2023-12-14 08:44:35',
            'updated_at' => '2023-12-14 08:44:35',
        ]);

    }
}
