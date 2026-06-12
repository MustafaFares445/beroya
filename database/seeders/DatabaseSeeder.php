<?php

namespace Database\Seeders;

use App\Models\Gallery;
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
        $this->call(RealEstateLookupSeeder::class);

        $gallery = Gallery::query()->firstOrCreate([
            'name' => 'Main Gallery',
        ], [
            'address' => 'Main Street',
        ]);

        User::query()->firstOrCreate([
            'user_name' => 'seed-admin',
        ], [
            'password' => Hash::make('password'),
            'gallery_id' => $gallery->id,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '0000000000',
            'is_active' => true,
        ]);
    }
}
