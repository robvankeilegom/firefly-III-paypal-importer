<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class WelcomeController
{
    public function index()
    {
        $txCount = Transaction::count();

        return view('welcome', compact('txCount'));
    }
}
