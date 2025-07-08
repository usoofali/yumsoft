<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SyncProducts extends Command
{
    protected $signature = 'sync:products {shop_id : The shop ID to sync products for} {--push}';
    protected $description = 'Sync products data between desktop and server';

    public function handle()
    {
        $shopId = $this->argument('shop_id');
        
        if ($this->option('push')) {
            $this->pushProducts($shopId);
        } else {
            $this->pullProducts($shopId);
        }
    }

    protected function pushProducts($shopId)
    {
        $products = Product::where('shop_id', $shopId)
            ->where('synced', false)
            ->get();
            
        $this->withProgressBar($products, function ($product) {
            // API call to server would go here
            $product->update(['synced' => true]);
        });
        
        $this->info("\nSynced {$products->count()} products to server");
    }

    protected function pullProducts($shopId)
    {
        $this->info("Fetching product updates for shop $shopId...");
        
        // Simulate API response
        $updatedProducts = []; // Would come from API call
        
        foreach ($updatedProducts as $productData) {
            Product::updateOrCreate(
                ['id' => $productData['id']],
                $productData
            );
        }
        
        $this->info("Updated " . count($updatedProducts) . " products");
    }
}