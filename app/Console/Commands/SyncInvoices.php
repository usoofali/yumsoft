<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncInvoices extends Command
{
    protected $signature = 'sync:invoices {shop_id : The shop ID to sync invoices for} {--push}';
    protected $description = 'Sync invoices and payments between desktop and server';

    public function handle()
    {
        $shopId = $this->argument('shop_id');
        
        if ($this->option('push')) {
            $this->pushInvoices($shopId);
        } else {
            $this->pullInvoices($shopId);
        }
    }

    protected function pushInvoices($shopId)
    {
        $invoices = Invoice::where('shop_id', $shopId)
            ->where('synced', false)
            ->with(['items', 'payments'])
            ->get();
            
        $this->withProgressBar($invoices, function ($invoice) {
            // API call to server would go here
            $invoice->update(['synced' => true]);
        });
        
        $this->info("\nSynced {$invoices->count()} invoices to server");
    }

    protected function pullInvoices($shopId)
    {
        $this->info("Fetching invoice updates for shop $shopId...");
        
        // Simulate API response
        $updatedInvoices = []; // Would come from API call
        
        foreach ($updatedInvoices as $invoiceData) {
            $invoice = Invoice::updateOrCreate(
                ['id' => $invoiceData['id']],
                $invoiceData
            );
            
            // Sync related payments
            foreach ($invoiceData['payments'] ?? [] as $paymentData) {
                $invoice->payments()->updateOrCreate(
                    ['id' => $paymentData['id']],
                    $paymentData
                );
            }
        }
        
        $this->info("Updated " . count($updatedInvoices) . " invoices");
    }
}