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
        /*\App\Models\User::create([
            'name'=>'dev',
            'email'=>'dev@me',
            'password'=> bcrypt('secret')
        ]);
         \App\Models\User::factory(4)->create();
         \App\Models\Category::factory(10)->create();
         \App\Models\Product::factory(20)->create();
         \App\Models\Client::factory(50)->create();
         \App\Models\Service::factory(5)->create();

        \App\Models\Plan::create([
            'name'=>'FREE',
            'description'=>'Gratuito',
            'price'=>'0',
        ]);
        \App\Models\Plan::create([
            'name'=>'BASIC',
            'description'=>'Standar',
            'price'=>'99.99',
        ]);
        */

        $this->call(RoleTableSeeder::class);
    }
}
