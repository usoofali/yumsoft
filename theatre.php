<div class="container mx-auto px-4 py-6">
    <!-- Header and search remain the same -->

    <!-- Bulk Actions Bar -->
    @if(count($selectedProducts) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedProducts) }} selected
        </flux:text>
        
        <div class="flex flex-wrap gap-2">
            <flux:button 
                variant="ghost" 
                size="sm"
                wire:click="toggleSelectAll(false)"
            >
                Clear
            </flux:button>
            <flux:modal.trigger name="bulk-delete-modal">
                <flux:button variant="ghost-danger" size="sm">
                    Delete Selected
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    @endif

    <!-- Mobile Card View (replaces table on small screens) -->
    <div class="lg:hidden space-y-4">
        @forelse($this->products as $product)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $product->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $product->id }}" 
                        wire:model="selectedProducts"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $product->name }}</div>
                        <div class="text-sm text-gray-500">{{ $product->barcode }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-medium">{{ number_format($product->price, 2) }}</div>
                    @if($product->image_path)
                    <img 
                        src="{{ asset('storage/'.$product->image_path) }}" 
                        alt="{{ $product->name }}" 
                        class="h-8 w-8 rounded object-cover mt-1 ml-auto"
                    >
                    @endif
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openProductModal({{ $product->id }})"
                >
                    Edit
                </flux:button>
                <flux:modal.trigger name="delete-confirmation" wire:click="confirmDelete({{ $product->id }})">
                    <flux:button size="sm" variant="ghost-danger">
                        Delete
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No products found
        </div>
        @endforelse
    </div>

    <!-- Desktop Table View (hidden on mobile) -->
    <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <!-- Table header and body remain the same as before -->
        </table>
    </div>

    <!-- Pagination and modals remain the same -->
</div>