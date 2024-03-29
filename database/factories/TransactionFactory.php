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
            'value'           => $this->faker->randomFloat(2, -10, -1000),
            'description'     => '',
        ];
    }

    public function refund(Transaction $original, int $value = null)
    {
        if (is_null($value)) {
            $value = $original->value * -1;
        }

        return $this->state(function (array $attributes) use ($original, $value) {
            return [
                'event_code'   => 'T1106',
                'reference_id' => $original->pp_id,
                'value'        => $value,
            ];
        });
    }

    public function revenue()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_code' => 'T0006',
                'value'      => $this->faker->randomFloat(2, 10, 1000),
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

    public function conversion()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_code' => 'T0200',
            ];
        });
    }
}
