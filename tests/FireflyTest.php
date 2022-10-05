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
