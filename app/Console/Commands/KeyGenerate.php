<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class KeyGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate {--show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $key = $this->getRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>' . $key . '</comment>');
        }

        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents(
                $path,
                str_replace(env('APP_KEY'), $key, file_get_contents($path))
            );
        }

        $this->info("Application key [{$key}] set successfully.");

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function getRandomKey()
    {
        return Str::random(32);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'show', null, InputOption::VALUE_NONE, 'Simply display the key instead of modifying files.',
            ],
        ];
    }
}
