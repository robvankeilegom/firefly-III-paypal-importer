<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'pp_id'           => $this->faker->regexify('[A-Z0-9]{17}'),
            'reference_id'    => null,
            'event_code'      => 'T0006',
            'initiation_date' => $this->faker->dateTime(),
            'currency'        => 'EUR',
            'value'           => $this->faker->randomFloat(2),
            'description'     => '',
        ];
    }
}
