<?php

namespace Database\Factories;

use App\Models\Payer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'pp_id'        => $this->faker->regexify('[A-Z0-9]{17}'),
            'email'        => $this->faker->unique()->safeEmail,
            'name'         => $this->faker->name(),
            'country_code' => $this->faker->countryCode(),
        ];
    }
}
