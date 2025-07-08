<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key constraints temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing data
        User::truncate();
        Shop::truncate();
        Product::truncate();
        Stock::truncate();
        Customer::truncate();
        Supplier::truncate();
        Supply::truncate();
        Sale::truncate();
        Invoice::truncate();
        InvoiceItem::truncate();
        Payment::truncate();

        // Enable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. Create Shops
        $shops = Shop::factory()->count(3)->create();
        $this->command->info('Created 3 shops.');

        // 2. Create Users
        User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@dightsoft.com',
            'role' => 'admin',
            'shop_id' => null
        ]);

        foreach ($shops as $shop) {
            // Shop Manager
            User::factory()->create([
                'name' => $shop->name . ' Manager',
                'email' => strtolower(str_replace(' ', '.', $shop->name)) . '.manager@dightsoft.com',
                'role' => 'manager',
                'shop_id' => $shop->id
            ]);

            // Salespersons
            User::factory()->count(2)->create([
                'role' => 'salesperson',
                'shop_id' => $shop->id
            ]);
        }
        $this->command->info('Created users for each shop.');

        // 3. Create Products
        $products = Product::factory()->count(50)->create();
        $this->command->info('Created 50 products.');

        // 4. Create Stock for each shop
        foreach ($shops as $shop) {
            foreach ($products as $product) {
                Stock::factory()->create([
                    'shop_id' => $shop->id,
                    'product_id' => $product->id
                ]);
            }
        }
        $this->command->info('Created stock entries for each shop.');

        // 5. Create Customers for each shop
        foreach ($shops as $shop) {
            Customer::factory()->count(15)->create([
                'shop_id' => $shop->id,
                'credit_limit' => rand(1000, 10000)
            ]);
        }
        $this->command->info('Created customers for each shop.');

        // 6. Create Suppliers
        $suppliers = Supplier::factory()->count(5)->create();
        $this->command->info('Created 5 suppliers.');

        // 7. Create Supply records
        foreach ($suppliers as $supplier) {
            foreach ($products->random(20) as $product) {
                foreach ($shops as $shop) {
                    Supply::factory()->create([
                        'supplier_id' => $supplier->id,
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'cost_price' => $product->price * 0.6 // 60% of retail price
                    ]);
                }
            }
        }
        $this->command->info('Created supply records.');

        // 8. Create Sales for each shop
        foreach ($shops as $shop) {
            $customers = Customer::where('shop_id', $shop->id)->get();
            $stockItems = Stock::where('shop_id', $shop->id)->with('product')->get();

            for ($i = 0; $i < 30; $i++) {
                $saleItems = $stockItems->random(rand(1, 5));
                $total = 0;
                $customer = $customers->random();

                foreach ($saleItems as $item) {
                    $qty = rand(1, 3);
                    $price = $item->product->price * $qty;
                    $total += $price;

                    Sale::factory()->create([
                        'shop_id' => $shop->id,
                        'product_id' => $item->product_id,
                        'customer_id' => $customer->id,
                        'quantity' => $qty,
                        'total_price' => $price,
                        'created_at' => now()->subDays(rand(0, 60))
                    ]);

                    // Update stock
                    $item->decrement('quantity', $qty);
                }
            }
        }
        $this->command->info('Created sales records for each shop.');

        // 9. Create Invoices for each shop
        foreach ($shops as $shop) {
            $customers = Customer::where('shop_id', $shop->id)->get();
            $products = Product::all();

            for ($i = 0; $i < 20; $i++) {
                $customer = $customers->random();
                $invoiceItems = $products->random(rand(1, 5));

                $invoice = Invoice::factory()->create([
                    'shop_id' => $shop->id,
                    'customer_id' => $customer->id,
                    'total_amount' => 0
                ]);

                $totalAmount = 0;

                foreach ($invoiceItems as $product) {
                    $quantity = rand(1, 10);
                    $unitPrice = $product->price;
                    $totalPrice = $quantity * $unitPrice;
                    $totalAmount += $totalPrice;

                    InvoiceItem::factory()->create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice
                    ]);
                }

                $invoice->update(['total_amount' => $totalAmount]);

                // Create payments for some invoices
                if (rand(0, 1)) {
                    $paymentAmount = $totalAmount;
                    if (rand(0, 1)) {
                        $paymentAmount = $totalAmount * 0.5; // Partial payment
                    }

                    Payment::factory()->create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $shop->users()->where('role', 'manager')->first()->id,
                        'amount' => $paymentAmount,
                        'payment_date' => $invoice->issue_date->addDays(rand(1, 30))
                    ]);

                    $invoice->update([
                        'amount_paid' => $paymentAmount,
                        'status' => $paymentAmount < $totalAmount ? 'partially_paid' : 'paid'
                    ]);
                }
            }
        }
        $this->command->info('Created invoices with payments for each shop.');

        $this->command->info('Database seeding completed successfully!');
    }
}