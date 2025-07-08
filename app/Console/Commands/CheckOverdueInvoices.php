<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    protected $description = 'Check for and mark overdue invoices';

    public function handle()
    {
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', Carbon::today())
            ->with(['customer', 'shop'])
            ->get();
            
        $overdueInvoices->each(function ($invoice) {
            // Update status
            $invoice->update(['status' => 'overdue']);
            
            // Notify customer
            $invoice->customer->notify(new InvoiceOverdue($invoice));
            
            $this->info("Marked invoice #{$invoice->invoice_number} as overdue");
        });
        
        $this->info("Processed {$overdueInvoices->count()} overdue invoices");
    }
}