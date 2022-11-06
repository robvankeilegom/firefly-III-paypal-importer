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

    $transaction = Transaction::factory()
        ->for($payer)
        ->refund()
        ->create();

    $response = $this->firefly->push($transaction);

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
