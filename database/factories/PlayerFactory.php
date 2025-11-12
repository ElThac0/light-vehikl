<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\LightVehikl\LvObjects\GameObjects\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location'=> ['x' => 5, 'y' => 5],
            'id'=> $this->faker->uuid(),
        ];
    }
}
