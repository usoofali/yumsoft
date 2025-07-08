<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallDightsoftSystem extends Command
{
    protected $signature = 'install:ysoft {--fresh : Fresh install with database wipe}';
    protected $description = 'Install the complete Dightsoft system with database setup';

    public function handle()
    {
        $this->info('Starting Dightsoft System Installation...');
        
        // Run migrations
        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
            $this->info('Database wiped and recreated');
        } else {
            $this->call('migrate');
        }
        
        // Seed the database
        $this->call('db:seed');
        
        // // Generate encryption keys
        // $this->call('passport:install');
        
        // // Create storage links
        // $this->call('storage:link');
        
        $this->info('Dightsoft system installed successfully!');
        $this->info('Admin credentials: admin@dightsoft.com / password');
    }
}