<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\LearningPathSeeder;

class CreateLearningPathsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning-paths:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create learning paths for all courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating learning paths for all courses...');

        $seeder = new LearningPathSeeder();
        $seeder->run();

        $this->info('Learning paths created successfully!');
    }
}
