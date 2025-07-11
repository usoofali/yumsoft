<?php
namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Supplier properties
    public $supplierId;
    public $name = '';
    public $phone = '';
    public $address = '';
    
    // Modal states
    public $showSupplierModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedSuppliers = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // Get suppliers with search
    public function getSuppliersProperty()
    {
        return Supplier::when($this->search, function($query) {
            $query->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('phone', 'like', '%'.$this->search.'%')
                  ->orWhere('address', 'like', '%'.$this->search.'%');
        })
        ->latest()
        ->paginate(10);
    }

    // Open supplier modal
    public function openSupplierModal($id): void
    {
        $this->resetForm();
        if ($id) {
            $supplier = Supplier::findOrFail($id);
            $this->supplierId = $id;
            $this->name = $supplier->name;
            $this->phone = $supplier->phone;
            $this->address = $supplier->address;
        }
        $this->showSupplierModal = true;
    }

    // Save supplier (create/update)
    public function saveSupplier(): void
    {
        $validated = $this->validate();
        
        if ($this->supplierId) {
            $supplier = Supplier::findOrFail($this->supplierId);
            $supplier->update($validated);
            session()->flash('message', 'Supplier updated successfully!');
        } else {
            Supplier::create($validated);
            session()->flash('message', 'Supplier created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->supplierId = $id;
        $this->showDeleteModal = true;
    }

    // Delete supplier
    public function deleteSupplier(): void
    {
        Supplier::findOrFail($this->supplierId)->delete();
        session()->flash('message', 'Supplier deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        Supplier::whereIn('id', $this->selectedSuppliers)->delete();
        $this->selectedSuppliers = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected suppliers deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'supplierId',
            'name',
            'phone',
            'address'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showSupplierModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk selection
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedSuppliers = $this->suppliers->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedSuppliers = [];
        }
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Supplier Management</h1>
        
        <flux:button 
            variant="primary" 
            wire:click="openSupplierModal(null)"
        >
            Add New Supplier
        </flux:button>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedSuppliers) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedSuppliers) }} selected
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
            placeholder="Search suppliers..." 
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
        @forelse($this->suppliers as $supplier)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $supplier->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $supplier->id }}" 
                        wire:model.live="selectedSuppliers"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $supplier->name }}</div>
                        <div class="text-sm text-gray-500">{{ $supplier->phone }}</div>
                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $supplier->address }}</div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openSupplierModal({{ $supplier->id }})"
                >
                    Edit
                </flux:button>
                <flux:button 
                    size="sm" 
                    variant="danger"
                    wire:click="confirmDelete({{ $supplier->id }})"
                >
                    Delete
                </flux:button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No suppliers found
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->suppliers as $supplier)
                    <tr wire:key="{{ $supplier->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $supplier->id }}" 
                                wire:model.live="selectedSuppliers"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap max-w-xs truncate">{{ $supplier->address }}</td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openSupplierModal({{ $supplier->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:button 
                                size="sm" 
                                variant="danger"
                                wire:click="confirmDelete({{ $supplier->id }})"
                            >
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center">No suppliers found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->suppliers->links() }}
    </div>

    <!-- Supplier Modal -->
    <flux:modal 
        wire:model.self="showSupplierModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $supplierId ? 'Edit Supplier' : 'Create New Supplier' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $supplierId ? 'Update the supplier details' : 'Add a new supplier to the system' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveSupplier">
                <div class="space-y-4">
                    <flux:input 
                        wire:model="name" 
                        label="Supplier Name" 
                        placeholder="Enter supplier name"
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
                        placeholder="Enter full address"
                        required
                        rows="3"
                    />
                    @error('address') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveSupplier"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $supplierId ? 'Update' : 'Create' }}</span>
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
                <flux:heading size="lg">Delete Supplier?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this supplier.</p>
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
                    wire:click="deleteSupplier"
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
                <flux:heading size="lg">Delete Selected Suppliers?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedSuppliers) }} suppliers.</p>
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