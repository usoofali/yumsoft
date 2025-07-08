<?php
namespace App\Livewire;

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Product properties
    public $productId;
    public $name = '';
    public $barcode = '';
    public $price = 0;
    public $image;
    public $tempImageUrl;
    
    // Modal states
    public $showProductModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedProducts = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|max:50|unique:products,barcode,'.$this->productId,
            'price' => 'required|numeric|min:0',
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // In your component class
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            // Select only current page items
            $this->selectedProducts = $this->products->pluck('id')->map(fn($id) => (string)$id)->toArray();
            
        } else {
            $this->selectedProducts = [];
        }
    }

    // Open product modal
    public function openProductModal($id = null): void
    {
        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $id;
            $this->name = $product->name;
            $this->barcode = $product->barcode;
            $this->price = $product->price;
            $this->tempImageUrl = $product->image_url;
        }
        $this->showProductModal = true;
    }

    // Save product (create/update)
    public function saveProduct(): void
    {
        $validated = $this->validate();
        
        $productData = [
            'name' => $validated['name'],
            'barcode' => $validated['barcode'],
            'price' => $validated['price'],
        ];
        
        if ($this->image) {
            $productData['image_path'] = $this->image->store('products', 'public');
        }
        
        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($productData);
            session()->flash('message', 'Product updated successfully!');
        } else {
            Product::create($productData);
            session()->flash('message', 'Product created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->productId = $id;
        $this->showDeleteModal = true;
    }

    // Delete product
    public function deleteProduct(): void
    {
        Product::findOrFail($this->productId)->delete();
        session()->flash('message', 'Product deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'productId',
            'name',
            'barcode',
            'price',
            'image',
            'tempImageUrl'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showProductModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk actions
    public function updatedSelectAll($value): void
{
    if ($value) {
        $this->selectedProducts = $this->products->pluck('id')->map(fn($id) => (string)$id)->toArray();
    } else {
        $this->selectedProducts = [];
    }
}

    public function bulkDelete(): void
    {
        Product::whereIn('id', $this->selectedProducts)->delete();
        $this->selectedProducts = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected products deleted successfully!');
        $this->showBulkDeleteModal = false;
    }

    // Ensure your getProductsProperty returns paginated results
    public function getProductsProperty()
    {
        return Product::when($this->search, function($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('barcode', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);
    }

    // Remove temporary uploaded image
    public function removeImage(): void
    {
        $this->reset('image');
        $this->tempImageUrl = null;
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Product Management</h1>
        
        <!-- Add Product Button -->
        <flux:button 
            variant="primary" 
            wire:click="openProductModal"
        >
            Add New Product
        </flux:button>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedProducts) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex items-center justify-between">
        <flux:text>
            {{ count($selectedProducts) }} selected
        </flux:text>
        
        <div class="flex space-x-2">
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

    <!-- Search and Filters -->
    <div class="mb-4 flex items-center space-x-4">
        <flux:input 
            wire:model.live="search" 
            placeholder="Search products..." 
            icon="search"
            class="flex-1"
        />
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <flux:checkbox 
                            wire:model.live="selectAll" 
                            wire:change="toggleSelectAll($event.target.checked)"
                        />
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barcode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->products as $product)
                <tr wire:key="{{ $product->id }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <flux:checkbox 
                            value="{{ $product->id }}" 
                            wire:model.live="selectedProducts"
                        />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $product->barcode }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">₦{{ number_format($product->price, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($product->image_path)
                        <img 
                            src="{{ asset('storage/'.$product->image_path) }}" 
                            alt="{{ $product->name }}" 
                            class="h-10 w-10 rounded object-cover"
                        >
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                        <flux:button 
                            size="sm" 
                            variant="ghost"
                            wire:click="openProductModal({{ $product->id }})"
                        >
                            Edit
                        </flux:button>
                        
                        <flux:button 
                            size="sm" 
                            variant="danger"
                            wire:click="confirmDelete({{ $product->id }})"
                        >
                            Delete
                        </flux:button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center">No products found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->products->links() }}
    </div>

    <!-- Product Modal -->
    <flux:modal 
        wire:model.self="showProductModal" 
        class="md:w-[500px]"
        @close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $productId ? 'Edit Product' : 'Create New Product' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $productId ? 'Update the product details' : 'Add a new product to your inventory' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveProduct">
                <div class="space-y-4">
                    <flux:input 
                        wire:model="name" 
                        label="Product Name" 
                        placeholder="Enter product name"
                        required
                    />
                    @error('name') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="barcode" 
                        label="Barcode" 
                        placeholder="Enter barcode"
                        required
                    />
                    @error('barcode') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="price" 
                        label="Price" 
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        required
                    />
                    @error('price') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <!-- Image Upload -->
                    <div>
                        <flux:text class="block text-sm font-medium mb-1">Product Image</flux:text>
                        @if($image || $tempImageUrl)
                        <div class="flex items-center space-x-4 mb-2">
                            <img 
                                src="{{ $image ? $image->temporaryUrl() : $tempImageUrl }}" 
                                alt="Product preview" 
                                class="h-16 w-16 rounded object-cover"
                            >
                            <flux:button 
                                type="button" 
                                variant="danger" 
                                size="sm" 
                                wire:click="removeImage"
                            >
                                Remove Image
                            </flux:button>
                        </div>
                        @endif
                        <flux:input 
                            type="file" 
                            wire:model="image" 
                            accept="image/*"
                        />
                        @error('image') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveProduct"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $productId ? 'Update' : 'Create' }}</span>
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
        @close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Product?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this product.</p>
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
                    wire:click="deleteProduct"
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
        @close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Selected Products?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedProducts) }} products.</p>
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