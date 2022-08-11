<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class WelcomeController
{
    public function index()
    {
        $txs = Transaction::all()
            ->filter(function ($t) {
                return $t->is_payment || $t->is_refund;
            });

        $txCount = $txs->count();

        $txPushed = $txs
            ->filter(function ($t) {
                return (bool) $t->firefly_id;
            })
            ->count();

        return view('welcome', compact('txCount', 'txPushed'));
    }
}
