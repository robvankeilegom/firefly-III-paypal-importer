<?php

namespace App\Console\Commands;

use App\Sync as AppSync;
use Illuminate\Console\Command;

class SyncPayPal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:paypal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull all data from PayPal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $client = new AppSync();

        $this->info('Start pulling data from PayPal');

        // Load all transactions from PayPal
        $client->syncPayPal();

        $this->info('Done');

        return 0;
    }
}
