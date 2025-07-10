<?php
namespace App\Livewire;

use App\Models\Shop;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Shop properties
    public $shopId;
    public $name = '';
    public $location = '';
    public $phone = '';
    
    // Modal states
    public $showShopModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedShops = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // Get shops with search
    public function getShopsProperty()
    {
        return Shop::when($this->search, function($query) {
            $query->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('location', 'like', '%'.$this->search.'%')
                  ->orWhere('phone', 'like', '%'.$this->search.'%');
        })
        ->latest()
        ->paginate(10);
    }

    // Open shop modal
    public function openShopModal($id): void
    {
        if ($id) {
            $shop = Shop::findOrFail($id);
            $this->shopId = $id;
            $this->name = $shop->name;
            $this->location = $shop->location;
            $this->phone = $shop->phone;
        }
        $this->showShopModal = true;
    }

    // Save shop (create/update)
    public function saveShop(): void
    {
        $validated = $this->validate();
        
        if ($this->shopId) {
            $shop = Shop::findOrFail($this->shopId);
            $shop->update($validated);
            session()->flash('message', 'Shop updated successfully!');
        } else {
            Shop::create($validated);
            session()->flash('message', 'Shop created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->shopId = $id;
        $this->showDeleteModal = true;
    }

    // Delete shop
    public function deleteShop(): void
    {
        Shop::findOrFail($this->shopId)->delete();
        session()->flash('message', 'Shop deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        Shop::whereIn('id', $this->selectedShops)->delete();
        $this->selectedShops = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected shops deleted successfully!');
        $this->closeModals();
    }

    // Toggle select all
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedShops = $this->shops->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedShops = [];
        }
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'shopId',
            'name',
            'location',
            'phone'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showShopModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Shop Management</h1>
        
        <flux:button 
            variant="primary" 
            wire:click="openShopModal(null)"
        >
            Add New Shop
        </flux:button>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedShops) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedShops) }} selected
        </flux:text>
        
        <div class="flex flex-wrap gap-2">
            <flux:button 
                variant="ghost" 
                size="sm"
                wire:click="toggleSelectAll(false)"
            >
                Clear
            </flux:button>
            <flux:modal.trigger name="showBulkDeleteModal">
                <flux:button variant="danger" size="sm"
                wire:click="$set('showBulkDeleteModal', true)"
                >
                    Delete Selected
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    @endif

    <!-- Search -->
    <div class="mb-4">
        <flux:input 
            wire:model.live="search" 
            placeholder="Search shops..." 
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
                            <div class="font-medium">Select All</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @forelse($this->shops as $shop)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $shop->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $shop->id }}" 
                        wire:model.live="selectedShops"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $shop->name }}</div>
                        <div class="text-sm text-gray-500">{{ $shop->location }}</div>
                        <div class="text-sm text-gray-500">{{ $shop->phone }}</div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openShopModal({{ $shop->id }})"
                >
                    Edit
                </flux:button>
                <flux:modal.trigger name="delete-confirmation" wire:click="confirmDelete({{ $shop->id }})">
                    <flux:button size="sm" variant="danger">
                        Delete
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No shops found
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->shops as $shop)
                    <tr wire:key="{{ $shop->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $shop->id }}" 
                                wire:model.live="selectedShops"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $shop->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $shop->location }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $shop->phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openShopModal({{ $shop->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:modal.trigger name="delete-confirmation" wire:click="confirmDelete({{ $shop->id }})">
                                <flux:button size="sm" variant="danger">
                                    Delete
                                </flux:button>
                            </flux:modal.trigger>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center">No shops found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->shops->links() }}
    </div>

    <!-- Shop Modal -->
    <flux:modal 
        wire:model.self="showShopModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $shopId ? 'Edit Shop' : 'Create New Shop' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $shopId ? 'Update the shop details' : 'Add a new shop to your system' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveShop">
                <div class="space-y-4">
                    <flux:input 
                        wire:model="name" 
                        label="Shop Name" 
                        placeholder="Enter shop name"
                        required
                    />
                    @error('name') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="location" 
                        label="Location" 
                        placeholder="Enter shop location"
                        required
                    />
                    @error('location') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="phone" 
                        label="Phone Number" 
                        placeholder="Enter phone number"
                        required
                    />
                    @error('phone') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveShop"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $shopId ? 'Update' : 'Create' }}</span>
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
                <flux:heading size="lg">Delete Shop?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this shop.</p>
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
                    wire:click="deleteShop"
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
                <flux:heading size="lg">Delete Selected Shops?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedShops) }} shops.</p>
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