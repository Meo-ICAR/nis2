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
     */
    public function run(): void
    {
        $this->call([
            ApplicationSeeder::class,
        ]);

        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'piergiuseppe.meo@icar.cnr.it'],
            [
                'name' => 'Pier Giuseppe Meo',
                'password' => Hash::make('password'),
                //   'company_id' => $companyId,  // NULL per Super Admin globali
            ]
        );

        User::firstOrCreate(
            ['email' => 'emanuele.damiano@icar.cnr.it'],
            [
                'name' => 'Emanuele Damiano',
                'password' => Hash::make('password'),
                //   'company_id' => $companyId,  // NULL per Super Admin globali
            ]
        );
        User::firstOrCreate(
            ['email' => 'mario.sicuranza@icar.cnr.it'],
            [
                'name' => 'Mario Sicuranza',
                'password' => Hash::make('password'),
                //   'company_id' => $companyId,  // NULL per Super Admin globali
            ]
        );

        User::firstOrCreate(
            ['email' => 'mario.ciampi@icar.cnr.it'],
            [
                'name' => 'Mario Sicuranza',
                'password' => Hash::make('password'),
                //   'company_id' => $companyId,  // NULL per Super Admin globali
            ]
        );

        User::firstOrCreate(
            ['email' => 'piergiuseppe.meo@cnr.it'],
            [
                'name' => 'Piero Meo',
                'password' => Hash::make('password'),
                //   'company_id' => $companyId,  // NULL per Super Admin globali
            ]
        );

        // Crea utente Filament admin con email univoca
    }
}
