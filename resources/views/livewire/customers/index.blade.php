<?php
namespace App\Livewire;

use App\Models\Customer;
use App\Models\Shop;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Customer properties
    public $customerId;
    public $shop_id = '';
    public $name = '';
    public $phone = '';
    public $address = '';
    public $credit_limit = 0;
    public $payment_terms = '';
    
    // Filter states
    public $selectedShop = '';
    public $search = '';
    
    // Modal states
    public $showCustomerModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedCustomers = [];
    public $selectAll = false;

    // Validation rules
    protected function rules(): array
    {
        return [
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms' => 'required|string|max:255',
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // Get customers with filters
    public function getCustomersProperty()
    {
        return Customer::with(['shop', 'sales', 'invoices'])
            ->when($this->selectedShop, function($query) {
                $query->where('shop_id', $this->selectedShop);
            })
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('phone', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);
    }

    // Get shops for dropdown
    public function getShopsProperty()
    {
        return Shop::orderBy('name')->get();
    }

    // Open customer modal
    public function openCustomerModal($id): void
    {
        $this->resetForm();
        if ($id) {
            $customer = Customer::findOrFail($id);
            $this->customerId = $id;
            $this->shop_id = $customer->shop_id;
            $this->name = $customer->name;
            $this->phone = $customer->phone;
            $this->address = $customer->address;
            $this->credit_limit = $customer->credit_limit;
            $this->payment_terms = $customer->payment_terms;
        }
        $this->showCustomerModal = true;
    }

    // Save customer (create/update)
    public function saveCustomer(): void
    {
        $validated = $this->validate();
        
        $customerData = [
            'shop_id' => $validated['shop_id'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'credit_limit' => $validated['credit_limit'],
            'payment_terms' => $validated['payment_terms'],
        ];
        
        if ($this->customerId) {
            $customer = Customer::findOrFail($this->customerId);
            $customer->update($customerData);
            session()->flash('message', 'Customer updated successfully!');
        } else {
            Customer::create($customerData);
            session()->flash('message', 'Customer created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->customerId = $id;
        $this->showDeleteModal = true;
    }

    // Delete customer
    public function deleteCustomer(): void
    {
        Customer::findOrFail($this->customerId)->delete();
        session()->flash('message', 'Customer deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        Customer::whereIn('id', $this->selectedCustomers)->delete();
        $this->selectedCustomers = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected customers deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'customerId',
            'shop_id',
            'name',
            'phone',
            'address',
            'credit_limit',
            'payment_terms'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showCustomerModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk selection
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedCustomers = $this->customers->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedCustomers = [];
        }
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Customer Management</h1>
        
        <div class="flex space-x-2">
            <!-- Shop Filter -->
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
                wire:click="openCustomerModal(null)"
            >
                Add New Customer
            </flux:button>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedCustomers) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedCustomers) }} selected
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
            placeholder="Search customers by name or phone..." 
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
        @forelse($this->customers as $customer)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $customer->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $customer->id }}" 
                        wire:model.live="selectedCustomers"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $customer->name }}</div>
                        <div class="text-sm text-gray-500">{{ $customer->phone }}</div>
                        <div class="text-sm text-gray-500">{{ $customer->shop->name }}</div>
                    </div>
                </div>
                <div class="text-right">
                        <div class="font-small">Credit: ₦{{ number_format($customer->credit_limit, 2) }}</div>
                        <div class="text-sm text-gray-500">Terms: {{ $customer->payment_terms }}</div>
                </div>

            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openCustomerModal({{ $customer->id }})"
                >
                    Edit
                </flux:button>
                <flux:button 
                    size="sm" 
                    variant="danger"
                    wire:click="confirmDelete({{ $customer->id }})"
                >
                    Delete
                </flux:button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No customers found
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Shop</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Credit Limit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Payment Terms</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->customers as $customer)
                    <tr wire:key="{{ $customer->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $customer->id }}" 
                                wire:model.live="selectedCustomers"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customer->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customer->phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customer->shop->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">₦{{ number_format($customer->credit_limit, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customer->payment_terms }}</td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openCustomerModal({{ $customer->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:button 
                                size="sm" 
                                variant="danger"
                                wire:click="confirmDelete({{ $customer->id }})"
                            >
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center">No customers found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->customers->links() }}
    </div>

    <!-- Customer Modal -->
    <flux:modal 
        wire:model.self="showCustomerModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $customerId ? 'Edit Customer' : 'Add New Customer' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $customerId ? 'Update the customer details' : 'Add a new customer to the system' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveCustomer">
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

                    <flux:input 
                        wire:model="name" 
                        label="Full Name" 
                        placeholder="Enter customer name"
                        required
                    />
                    @error('name') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="phone" 
                        label="Phone Number" 
                        placeholder="Enter phone number"
                        required
                    />
                    @error('phone') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:textarea 
                        wire:model="address" 
                        label="Address" 
                        placeholder="Enter customer address"
                        required
                    />
                    @error('address') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:input 
                                wire:model="credit_limit" 
                                label="Credit Limit" 
                                type="number"
                                step="0.01"
                                min="0"
                                required
                            />
                            @error('credit_limit') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                        </div>
                        <div>
                            <flux:input 
                                wire:model="payment_terms" 
                                label="Payment Terms" 
                                placeholder="e.g. Net 30"
                                required
                            />
                            @error('payment_terms') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                        </div>
                    </div>
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveCustomer"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $customerId ? 'Update' : 'Create' }}</span>
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
                <flux:heading size="lg">Delete Customer?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this customer record.</p>
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
                    wire:click="deleteCustomer"
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
                <flux:heading size="lg">Delete Selected Customers?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedCustomers) }} customers.</p>
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