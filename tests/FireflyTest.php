<?php

use App\Firefly;
use App\Models\Payer;
use App\Models\Transaction;

beforeEach(function () {
    $this->firefly = new Firefly();
});

it('can push an expense', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
    expect($transaction->firefly_id)->not->toBeNull();
});

it('can push a known expense that is unknown in firefly', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    // Give it a random id that should be unknown to firefly
    $transaction->firefly_id = 666;

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
    expect($transaction->firefly_id)->not->toBeNull();
});

it('can push a new expense that is known in firefly', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $response = $this->firefly->push($transaction);

    $transaction->firefly_id = null;
    $transaction->save();

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
    // We expect the id to be null since the transaction won't be pushed
    expect($transaction->firefly_id)->toBeNull();
});

it('can push a revenue', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->revenue()
        ->create();

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
    expect($transaction->firefly_id)->not->toBeNull();
});

it('can push a transaction with an existing unknown payer', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $response = $this->firefly->push($transaction);

    // Clear the firefly data
    $payer->firefly_expense_id = null;
    $payer->firefly_revenue_id = null;
    $payer->save();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
});

it('can push the same transaction twice', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $response = $this->firefly->push($transaction);
    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
});

it('can push a refund', function () {
    $payer = Payer::factory()->create();

    // Original transaction
    $transaction = Transaction::factory()
        ->for($payer)
        ->create();

    $refund = Transaction::factory()
        ->for($payer)
        ->refund($transaction)
        ->create();

    $response = $this->firefly->push($refund);

    expect($response)->not->toBeFalse();
});

it('can push a refund in a foreign currency', function () {
    $payer = Payer::factory()->create();

    // Original transaction
    $transaction = Transaction::factory([
        'currency' => 'USD',
    ])
        ->for($payer)
        ->create();

    $usd = $transaction->value;
    $eur = $usd * 0.9; // Give or take

    // Conversion in usd
    Transaction::factory([
        'currency'        => 'USD',
        'reference_id'    => $transaction->pp_id,
        'initiation_date' => $transaction->initiation_date,
        'value'           => $usd * -1, // Make value positive
    ])
        ->conversion()
        ->create();

    // Conversion in EUR
    Transaction::factory([
        'currency'        => 'EUR',
        'reference_id'    => $transaction->pp_id,
        'initiation_date' => $transaction->initiation_date,
        'value'           => $eur,
    ])
        ->conversion()
        ->create();

    // This was the original transaction, now generate the refund

    // Refund only half
    $value = $transaction->value * 0.5 * -1;

    $refund = Transaction::factory([
        'currency' => 'USD',
    ])
        ->for($payer)
        ->refund($transaction, $value)
        ->create();

    $usd = $refund->value;
    $eur = $usd * 0.9; // Give or take

    // Conversion in usd
    Transaction::factory([
        'currency'        => 'USD',
        'reference_id'    => $transaction->pp_id,
        'initiation_date' => $refund->initiation_date,
        'value'           => $usd * -1, // Make value positive
    ])
        ->conversion()
        ->create();

    // Conversion in EUR
    Transaction::factory([
        'currency'        => 'EUR',
        'reference_id'    => $transaction->pp_id,
        'initiation_date' => $refund->initiation_date,
        'value'           => $eur,
    ])
        ->conversion()
        ->create();

    $response = $this->firefly->push($refund);

    expect($response)->not->toBeFalse();
});

// Make sure there is no error thrown.
it('can\'t push a transaction with unknown currency', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    $transaction->currency = 'non-existent';

    $response = $this->firefly->push($transaction);

    expect($response)->toBeFalse();
});

it('can push a transaction with a >1000 long description', function () {
    $payer = Payer::factory()->create();

    $transaction = Transaction::factory()
        ->for($payer)
        ->expense()
        ->create();

    // Create a 4000 chr long description
    $transaction->description = str_repeat('test', 1000);

    $response = $this->firefly->push($transaction);

    expect($response)->not->toBeFalse();
});
