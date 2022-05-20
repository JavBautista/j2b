<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name'=>'dev',
            'email'=>'dev@me',
            'password'=> bcrypt('secret')
        ]);
         \App\Models\User::factory(4)->create();
         \App\Models\Category::factory(10)->create();
         \App\Models\Product::factory(50)->create();
    }
}
