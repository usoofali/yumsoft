<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

class SyncSales extends Command
{
    protected $signature = 'sync:sales {shop_id : The shop ID to sync sales for} {--push}';
    protected $description = 'Sync sales data between desktop and server';

    public function handle()
    {
        $shopId = $this->argument('shop_id');
        
        if ($this->option('push')) {
            $this->pushSales($shopId);
        } else {
            $this->pullSales($shopId);
        }
    }

    protected function pushSales($shopId)
    {
        $sales = Sale::where('shop_id', $shopId)
            ->where('synced', false)
            ->with('items')
            ->get();
            
        $this->withProgressBar($sales, function ($sale) {
            // API call to server would go here
            $sale->update(['synced' => true]);
        });
        
        $this->info("\nSynced {$sales->count()} sales to server");
    }

    protected function pullSales($shopId)
    {
        $this->info("Fetching sales updates for shop $shopId...");
        
        // Simulate API response
        $updatedSales = []; // Would come from API call
        
        foreach ($updatedSales as $saleData) {
            Sale::updateOrCreate(
                ['id' => $saleData['id']],
                $saleData
            );
        }
        
        $this->info("Updated " . count($updatedSales) . " sales");
    }
}