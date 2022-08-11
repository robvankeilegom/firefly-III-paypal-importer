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
            'initiation_date' => $this->faker->dateTimeBetween('-10 week', 'now'),
            'currency'        => 'EUR',
            'value'           => $this->faker->randomFloat(2, 10, 1000),
            'description'     => '',
        ];
    }

    public function refund()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_code' => 'T1106',
            ];
        });
    }

    public function expense()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_code' => 'T0006',
            ];
        });
    }
}
