<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAllData extends Command
{
    protected $signature = 'sync:all-data {shop_id : The shop ID to sync} {--push} {--pull}';
    protected $description = 'Sync all data types for a shop';

    public function handle()
    {
        $shopId = $this->argument('shop_id');
        
        if ($this->option('push')) {
            $this->call('sync:shops', ['shop_id' => $shopId, '--push' => true]);
            $this->call('sync:products', ['shop_id' => $shopId, '--push' => true]);
            $this->call('sync:sales', ['shop_id' => $shopId, '--push' => true]);
            $this->call('sync:invoices', ['shop_id' => $shopId, '--push' => true]);
        }
        
        if ($this->option('pull')) {
            $this->call('sync:shops', ['shop_id' => $shopId]);
            $this->call('sync:products', ['shop_id' => $shopId]);
            $this->call('sync:sales', ['shop_id' => $shopId]);
            $this->call('sync:invoices', ['shop_id' => $shopId]);
        }
        
        $this->info('Complete sync finished for shop ' . $shopId);
    }
}