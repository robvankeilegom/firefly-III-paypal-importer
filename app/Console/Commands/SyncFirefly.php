<?php

namespace App\Console\Commands;

use App\Sync as AppSync;
use Illuminate\Console\Command;

class SyncFirefly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:firefly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push all data to Firefly';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $client = new AppSync();

        $this->info('Pushing data to Firefly');

        // Send them to Firefly
        $client->syncFirefly();

        $this->info('Done');

        return 0;
    }
}
