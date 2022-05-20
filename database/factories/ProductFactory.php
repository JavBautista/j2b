<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'category_id'=>$this->faker->randomDigitNot(0),
            'active'=>$this->faker->boolean(),
            'key'=>$this->faker->numerify('MC-####'),
            'barcode'=>$this->faker->numberBetween(10000, 99999),
            'name'=>$this->faker->word(),
            'description'=>$this->faker->sentence(),
            'cost'=>$this->faker->randomFloat(2,100,9999),
            'retail'=>$this->faker->randomFloat(2,100,9999),
            'wholesale'=>$this->faker->randomFloat(2,100,9999),
            'image'=>$this->faker->imageUrl(640, 480, 'animals', true)
        ];
    }
}
