<?php
namespace App\Livewire;

use App\Models\Stock;
use App\Models\Shop;
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Stock properties
    public $stockId;
    public $shop_id = '';
    public $product_id = '';
    public $quantity = 0;
    public $alert_quantity = 0;
    
    // Filter and modal states
    public $selectedShop = '';
    public $showStockModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedStocks = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';
    // Validation rules
    protected function rules(): array
    {
        return [
            'shop_id' => 'required|exists:shops,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'alert_quantity' => 'required|integer|min:0|lte:quantity',
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // Get stocks with search and shop filter
    public function getStocksProperty()
    {
        return Stock::with(['shop', 'product'])
            ->when($this->selectedShop, function($query) {
                $query->where('shop_id', $this->selectedShop);
            })
            ->when($this->search, function($query) {
                $query->whereHas('product', function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('barcode', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    // Get shops for filter dropdown
    public function getShopsProperty()
    {
        return Shop::orderBy('name')->get();
    }

    // Get products for select dropdown
    public function getProductsProperty()
    {
        return Product::orderBy('name')->get();
    }

    // Reset shop filter
    public function resetShopFilter(): void
    {
        $this->selectedShop = '';
    }

    // Open stock modal
    public function openStockModal($id = null): void
    {
        if ($id) {
            $stock = Stock::findOrFail($id);
            $this->stockId = $id;
            $this->shop_id = $stock->shop_id;
            $this->product_id = $stock->product_id;
            $this->quantity = $stock->quantity;
            $this->alert_quantity = $stock->alert_quantity;
        }
        $this->showStockModal = true;
    }

    // Save stock (create/update)
    public function saveStock(): void
    {
        $validated = $this->validate();
        
        $stockData = [
            'shop_id' => $validated['shop_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'alert_quantity' => $validated['alert_quantity'],
        ];
        
        if ($this->stockId) {
            $stock = Stock::findOrFail($this->stockId);
            $stock->update($stockData);
            session()->flash('message', 'Stock updated successfully!');
        } else {
            Stock::create($stockData);
            session()->flash('message', 'Stock created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->stockId = $id;
        $this->showDeleteModal = true;
    }

    // Delete stock
    public function deleteStock(): void
    {
        Stock::findOrFail($this->stockId)->delete();
        session()->flash('message', 'Stock deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        Stock::whereIn('id', $this->selectedStocks)->delete();
        $this->selectedStocks = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected stocks deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'stockId',
            'shop_id',
            'product_id',
            'quantity',
            'alert_quantity'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showStockModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk selection
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedStocks = $this->stocks->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedStocks = [];
        }
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Stock Management</h1>
        <!-- Replace the dropdown section with this corrected version -->
        <div class="flex space-x-2">
            <flux:dropdown>
                <flux:button variant="filled" icon:trailing="chevron-down">
                    @if($selectedShop)
                        {{ $this->shops->firstWhere('id', $selectedShop)->name }}
                    @else
                        All Shops
                    @endif
                </flux:button>
                
                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedShop">
                        <flux:menu.radio value="">All Shops</flux:menu.radio>
                        @foreach($this->shops as $shop)
                        <flux:menu.radio value="{{ $shop->id }}">{{ $shop->name }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
            <flux:button 
                variant="primary" 
                wire:click="openStockModal"
            >
                Add New Stock
            </flux:button>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedStocks) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedStocks) }} selected
        </flux:text>
        
        <div class="flex flex-wrap gap-2">
            <flux:button 
                variant="ghost" 
                size="sm"
                wire:click="toggleSelectAll(false)"
            >
                Clear
            </flux:button>
            <flux:button 
                variant="danger" 
                size="sm"
                wire:click="$set('showBulkDeleteModal', true)"
            >
                Delete Selected
            </flux:button>
        </div>
    </div>
    @endif

    <!-- Search -->
    <div class="mb-4">
        <flux:input 
            wire:model.live="search" 
            placeholder="Search stocks by product or shop..." 
            icon="search"
            class="w-full"
        />
    </div>

    <!-- Mobile Card View -->
    <div class="lg:hidden space-y-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        wire:model.live="selectAll" 
                        wire:change="toggleSelectAll($event.target.checked)"
                    />
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Select All</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @forelse($this->stocks as $stock)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $stock->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $stock->id }}" 
                        wire:model.live="selectedStocks"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $stock->product->name }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Qty: {{ $stock->quantity }}</div>
                    <div class="text-sm {{ $stock->quantity <= $stock->alert_quantity ? 'text-red-500' : 'text-gray-500' }}">
                        Alert: {{ $stock->alert_quantity }}
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openStockModal({{ $stock->id }})"
                >
                    Edit
                </flux:button>
                <flux:button 
                    size="sm" 
                    variant="danger"
                    wire:click="confirmDelete({{ $stock->id }})"
                >
                    Delete
                </flux:button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No stock records found
        </div>
        @endforelse
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            <flux:checkbox 
                                wire:model.live="selectAll" 
                                wire:change="toggleSelectAll($event.target.checked)"
                            />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Alert Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->stocks as $stock)
                    <tr wire:key="{{ $stock->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $stock->id }}" 
                                wire:model.live="selectedStocks"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $stock->product->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $stock->quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $stock->alert_quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($stock->quantity <= $stock->alert_quantity)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Low Stock
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    In Stock
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openStockModal({{ $stock->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:button 
                                size="sm" 
                                variant="danger"
                                wire:click="confirmDelete({{ $stock->id }})"
                            >
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center">No stock records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->stocks->links() }}
    </div>

    <!-- Stock Modal -->
    <flux:modal 
        wire:model.self="showStockModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $stockId ? 'Edit Stock' : 'Add New Stock' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $stockId ? 'Update the stock details' : 'Add new stock inventory' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveStock">
                <div class="space-y-4">
                    <flux:select 
                        wire:model="shop_id" 
                        label="Shop"
                        required
                    >
                        <option value="">Select Shop</option>
                        @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('shop_id') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:select 
                        wire:model="product_id" 
                        label="Product"
                        required
                    >
                        <option value="">Select Product</option>
                        @foreach($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('product_id') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="quantity" 
                        label="Quantity" 
                        type="number"
                        min="0"
                        required
                    />
                    @error('quantity') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="alert_quantity" 
                        label="Alert Quantity" 
                        type="number"
                        min="0"
                        required
                    />
                    @error('alert_quantity') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveStock"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $stockId ? 'Update' : 'Create' }}</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal 
        wire:model.self="showDeleteModal" 
        class="min-w-[22rem]"
        :dismissible="false"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Stock Record?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this stock record.</p>
                    <p>This action cannot be reversed.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button 
                    variant="ghost" 
                    wire:click="$set('showDeleteModal', false)"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    variant="danger" 
                    wire:click="deleteStock"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Bulk Delete Confirmation Modal -->
    <flux:modal 
        wire:model.self="showBulkDeleteModal" 
        class="min-w-[22rem]"
        :dismissible="false"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Selected Stocks?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedStocks) }} stock records.</p>
                    <p>This action cannot be reversed.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button 
                    variant="ghost" 
                    wire:click="$set('showBulkDeleteModal', false)"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    variant="danger" 
                    wire:click="bulkDelete"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete All</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-ui.alert variant="success" :timeout="5000">
            {{ session('message') }}
        </x-ui.alert>
    </div>
    @endif
</div>