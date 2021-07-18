<?php

namespace JawabApp\CloudMessaging\Console;


use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jawab-fcm:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the JawabApp-CloudMessaging resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'jawab-fcm-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'jawab-fcm-assets',
            '--force' => true,
        ]);
    }
}
