<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
            CitySeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
