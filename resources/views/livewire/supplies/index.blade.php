<?php
namespace App\Livewire;

use App\Models\Supply;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Supplier;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Supply properties
    public $supplyId;
    public $supplier_id = '';
    public $product_id = '';
    public $shop_id = '';
    public $quantity = 0;
    public $cost_price = 0;
    public $total_cost = 0;
    public $supply_date;
    
    // Filter states
    public $selectedShop = '';
    public $selectedProduct = '';
    public $selectedSupplier = '';
    public $dateFrom = '';
    public $dateTo = '';
    
    // Modal states
    public $showSupplyModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedSupplies = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';

    // Validation rules
    protected function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'shop_id' => 'required|exists:shops,id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0.01',
            'total_cost' => 'required|numeric|min:0.01',
            'supply_date' => 'required|date',
        ];
    }

    // Calculate total cost when quantity or cost price changes
    public function updatedQuantity($value)
    {
        $this->total_cost = $this->quantity * $this->cost_price;
    }

    public function updatedCostPrice($value)
    {
        $this->total_cost = $this->quantity * $this->cost_price;
    }

    // Initialize component
    public function mount(): void
    {
        $this->supply_date = now()->format('Y-m-d');
        $this->resetForm();
    }

    // Get supplies with filters
    public function getSuppliesProperty()
    {
        return Supply::with(['supplier', 'product', 'shop'])
            ->when($this->selectedShop, function($query) {
                $query->where('shop_id', $this->selectedShop);
            })
            ->when($this->selectedProduct, function($query) {
                $query->where('product_id', $this->selectedProduct);
            })
            ->when($this->selectedSupplier, function($query) {
                $query->where('supplier_id', $this->selectedSupplier);
            })
            ->when($this->dateFrom, function($query) {
                $query->whereDate('supply_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->whereDate('supply_date', '<=', $this->dateTo);
            })
            ->when($this->search, function($query) {
                $query->whereHas('product', function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('barcode', 'like', '%'.$this->search.'%');
                })
                ->orWhereHas('supplier', function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    // Get shops, products, and suppliers for dropdowns
    public function getShopsProperty()
    {
        return Shop::orderBy('name')->get();
    }

    public function getProductsProperty()
    {
        return Product::orderBy('name')->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::orderBy('name')->get();
    }

    // Reset filters
    public function resetFilters(): void
    {
        $this->selectedShop = '';
        $this->selectedProduct = '';
        $this->selectedSupplier = '';
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    // Open supply modal
    public function openSupplyModal($id): void
    {
        $this->resetForm();
        if ($id) {
            $supply = Supply::findOrFail($id);
            $this->supplyId = $id;
            $this->supplier_id = $supply->supplier_id;
            $this->product_id = $supply->product_id;
            $this->shop_id = $supply->shop_id;
            $this->quantity = $supply->quantity;
            $this->cost_price = $supply->cost_price;
            $this->supply_date = $supply->supply_date;
        }
        $this->showSupplyModal = true;
    }

    // Save supply (create/update)
    public function saveSupply(): void
    {
        $validated = $this->validate(  
            [
                'supplier_id' => 'required|exists:suppliers,id',
                'product_id' => 'required|exists:products,id',
                'shop_id' => 'required|exists:shops,id',
                'quantity' => 'required|integer|min:1',
                'cost_price' => 'required|numeric|min:0.01',
                'supply_date' => 'required|date',
            ]
        );

        $supplyData = [
            'supplier_id' => $validated['supplier_id'],
            'product_id' => $validated['product_id'],
            'shop_id' => $validated['shop_id'],
            'quantity' => $validated['quantity'],
            'cost_price' => $validated['cost_price'],
            'supply_date' => $validated['supply_date'],
        ];
        
        if ($this->supplyId) {
            $supply = Supply::findOrFail($this->supplyId);
            $supply->total_cost = $supplyData['quantity'] * $supplyData['cost_price'];
            $supply->update($supplyData);
            session()->flash('message', 'Supply updated successfully!');
        } else {
            $supply = Supply::create($supplyData);
            $supply->total_cost = $supplyData['quantity'] * $supplyData['cost_price'];
            $supply->save();
            session()->flash('message', 'Supply created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Delete supply
    public function deleteSupply(): void
    {
        Supply::findOrFail($this->supplyId)->delete();
        session()->flash('message', 'Supply deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        Supply::whereIn('id', $this->selectedSupplies)->delete();
        $this->selectedSupplies = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected supplies deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'supplyId',
            'supplier_id',
            'product_id',
            'shop_id',
            'quantity',
            'cost_price',
            'total_cost',
            'supply_date'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showSupplyModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk selection
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedSupplies = $this->supplies->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedSupplies = [];
        }
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Supply Management</h1>
        
        <flux:button 
            variant="primary" 
            wire:click="openSupplyModal(null)"
        >
            Add New Supply
        </flux:button>
    </div>

    <!-- Filters Section -->
<div class="mb-4 bg-white rounded-lg shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Shop Filter -->
        <div>
            <flux:dropdown>
                <flux:button variant="filled" class="w-full" icon:trailing="chevron-down">
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
        </div>

        <!-- Product Filter -->
        <div>
            <flux:dropdown>
                <flux:button variant="filled" class="w-full" icon:trailing="chevron-down">
                    @if($selectedProduct)
                        {{ $this->products->firstWhere('id', $selectedProduct)->name }}
                    @else
                        All Products
                    @endif
                </flux:button>
                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedProduct">
                        <flux:menu.radio value="">All Products</flux:menu.radio>
                        @foreach($this->products as $product)
                        <flux:menu.radio value="{{ $product->id }}">{{ $product->name }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </div>

        <!-- Supplier Filter -->
        <div>
            <flux:dropdown>
                <flux:button variant="filled" class="w-full" icon:trailing="chevron-down">
                    @if($selectedSupplier)
                        {{ $this->suppliers->firstWhere('id', $selectedSupplier)->name }}
                    @else
                        All Suppliers
                    @endif
                </flux:button>
                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedSupplier">
                        <flux:menu.radio value="">All Suppliers</flux:menu.radio>
                        @foreach($this->suppliers as $supplier)
                        <flux:menu.radio value="{{ $supplier->id }}">{{ $supplier->name }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </div>

        <!-- Date From -->
        <div>
            <flux:input 
                type="date"
                wire:model.live="dateFrom"
                label="From Date"
            />
        </div>

        <!-- Date To -->
        <div>
            <flux:input 
                type="date"
                wire:model.live="dateTo"
                label="To Date"
            />
        </div>
    </div>

    <div class="flex justify-end mt-4">
        <flux:button 
            variant="ghost" 
            wire:click="resetFilters"
        >
            Reset Filters
        </flux:button>
    </div>
</div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedSupplies) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedSupplies) }} selected
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
            placeholder="Search supplies by product or supplier..." 
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
        @forelse($this->supplies as $supply)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $supply->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $supply->id }}" 
                        wire:model.live="selectedSupplies"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $supply->product->name }}</div>
                        <div class="text-sm text-gray-500">{{ $supply->supplier->name }}</div>
                        <div class="text-sm text-gray-500">{{ $supply->shop->name }}</div>
                        
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-medium">Qty: {{ $supply->quantity }}</div>
                    <div class="font-medium">Cost: {{ number_format($supply->cost_price, 2) }}</div>
                    <div class="text-sm text-gray-500">{{ $supply->supply_date ? $supply->supply_date->format('M d, Y') : 'N/A' }}</div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openSupplyModal({{ $supply->id }})"
                >
                    Edit
                </flux:button>
                <flux:button 
                    size="sm" 
                    variant="danger"
                    wire:click="confirmDelete({{ $supply->id }})"
                >
                    Delete
                </flux:button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No supply records found
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Unit Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Total Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->supplies as $supply)
                    <tr wire:key="{{ $supply->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $supply->id }}" 
                                wire:model.live="selectedSupplies"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supply->product->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supply->supplier->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supply->quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($supply->cost_price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($supply->total_cost, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supply->supply_date ? $supply->supply_date->format('M d, Y') : 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openSupplyModal({{ $supply->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:button 
                                size="sm" 
                                variant="danger"
                                wire:click="confirmDelete({{ $supply->id }})"
                            >
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center">No supply records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->supplies->links() }}
    </div>

    <!-- Supply Modal -->
    <flux:modal 
        wire:model.self="showSupplyModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $supplyId ? 'Edit Supply' : 'Add New Supply' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $supplyId ? 'Update the supply details' : 'Add new supply record' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveSupply">
                <div class="space-y-4">
                    <flux:select 
                        wire:model="supplier_id" 
                        label="Supplier"
                        required
                    >
                        <option value="">Select Supplier</option>
                        @foreach($this->suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('supplier_id') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

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

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-1">
                            <flux:input 
                                wire:model="quantity" 
                                label="Quantity" 
                                type="number"
                                min="1"
                                required
                            />
                            @error('quantity') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                        </div>
                        <div class="col-span-1">
                            <flux:input 
                                wire:model="cost_price" 
                                label="Unit Cost" 
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            @error('cost_price') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                        </div>
                    </div>

                    <flux:input 
                        wire:model="supply_date" 
                        label="Supply Date" 
                        type="date"
                        required
                    />
                    @error('supply_date') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveSupply"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $supplyId ? 'Update' : 'Create' }}</span>
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
                <flux:heading size="lg">Delete Supply Record?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this supply record.</p>
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
                    wire:click="deleteSupply"
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
                <flux:heading size="lg">Delete Selected Supplies?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedSupplies) }} supply records.</p>
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