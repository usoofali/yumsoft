<?php

namespace App\Console\Commands;

use App\Models\Shop;
use Illuminate\Console\Command;

class SyncShops extends Command
{
    protected $signature = 'sync:shops {shop_id? : Specific shop ID to sync} {--push : Push local changes to server}';
    protected $description = 'Sync shops data between desktop and server';

    public function handle()
    {
        $shopId = $this->argument('shop_id');
        
        if ($this->option('push')) {
            $this->pushShops($shopId);
        } else {
            $this->pullShops($shopId);
        }
    }

    protected function pushShops($shopId = null)
    {
        $query = Shop::query();
        
        if ($shopId) {
            $query->where('id', $shopId);
        }
        
        $shops = $query->where('synced', false)->get();
        
        $this->withProgressBar($shops, function ($shop) {
            // API call to server would go here
            $shop->update(['synced' => true]);
        });
        
        $this->info("\nSynced {$shops->count()} shops to server");
    }

    protected function pullShops($shopId = null)
    {
        $this->info('Fetching shop updates from server...');
        
        // Simulate API response
        $updatedShops = []; // Would come from API call
        
        foreach ($updatedShops as $shopData) {
            Shop::updateOrCreate(['id' => $shopData['id']], $shopData);
        }
        
        $this->info("Updated " . count($updatedShops) . " shops from server");
    }
}