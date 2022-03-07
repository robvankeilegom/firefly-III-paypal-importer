<?php

namespace App\Console\Commands;

use App\Sync as AppSync;
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
        $client = new AppSync();

        $this->info('Start pulling data from PayPal');

        // Load all transactions from PayPal
        $client->syncPayPal();

        $this->info('Pushing data to Firefly');

        // Send them to Firefly
        $client->syncFirefly();

        $this->info('Done');

        return 0;
    }
}
