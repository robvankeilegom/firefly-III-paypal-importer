<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $client = new self();

        // Load all transactions from PayPal
        $client->syncPayPal();

        // Send them to Firefly
        $client->syncFirefly();

        return 0;
    }
}
