<?php
namespace App\Livewire;

use App\Models\User;
use App\Models\Shop;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    // User properties
    public $userId;
    public $name = '';
    public $email = '';
    public $role = 'salesperson';
    public $phone = '';
    public $shop_id = null;
    public $password = '';
    public $password_confirmation = '';
    
    // Modal states
    public $showUserModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    
    // Bulk actions
    public $selectedUsers = [];
    public $selectAll = false;
    
    // Search state
    public $search = '';

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->userId,
            'role' => 'required|in:admin,manager,salesperson',
            'phone' => 'nullable|string|max:20',
            'shop_id' => 'required_if:role,manager,salesperson|nullable|exists:shops,id',
            'password' => $this->userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
        ];
    }

    // Initialize component
    public function mount(): void
    {
        $this->resetForm();
    }

    // Get users with search
    public function getUsersProperty()
    {
        return User::with('shop')
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);
    }

    // Get shops for select dropdown
    public function getShopsProperty()
    {
        return Shop::orderBy('name')->get();
    }

    // Open user modal
    public function openUserModal($id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->userId = $id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->phone = $user->phone;
            $this->shop_id = $user->shop_id;
        }
        $this->showUserModal = true;
    }

    // Save user (create/update)
    public function saveUser(): void
    {
        $validated = $this->validate();
        
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'],
            'shop_id' => $validated['shop_id'],
        ];
        
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }
        
        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($userData);
            session()->flash('message', 'User updated successfully!');
        } else {
            User::create($userData);
            session()->flash('message', 'User created successfully!');
        }
        
        $this->closeModals();
        $this->resetForm();
    }

    // Prepare delete
    public function confirmDelete($id): void
    {
        $this->userId = $id;
        $this->showDeleteModal = true;
    }

    // Delete user
    public function deleteUser(): void
    {
        User::findOrFail($this->userId)->delete();
        session()->flash('message', 'User deleted successfully!');
        $this->closeModals();
    }

    // Bulk delete
    public function bulkDelete(): void
    {
        User::whereIn('id', $this->selectedUsers)->delete();
        $this->selectedUsers = [];
        $this->selectAll = false;
        session()->flash('message', 'Selected users deleted successfully!');
        $this->closeModals();
    }

    // Reset form fields
    private function resetForm(): void
    {
        $this->reset([
            'userId',
            'name',
            'email',
            'role',
            'phone',
            'shop_id',
            'password',
            'password_confirmation'
        ]);
        $this->resetErrorBag();
    }

    // Close all modals
    public function closeModals(): void
    {
        $this->showUserModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
    }

    // Bulk selection
    public function toggleSelectAll($value): void
    {
        $this->selectAll = $value;
        
        if ($value) {
            $this->selectedUsers = $this->users->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }
};
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">User Management</h1>
        
        <flux:button 
            variant="primary" 
            wire:click="openUserModal"
        >
            Add New User
        </flux:button>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedUsers) > 0)
    <div class="mb-4 p-3 bg-gray-50 rounded-lg flex flex-wrap items-center justify-between gap-2">
        <flux:text class="whitespace-nowrap">
            {{ count($selectedUsers) }} selected
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
            placeholder="Search users..." 
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
        @forelse($this->users as $user)
        <div class="bg-white rounded-lg shadow p-4" wire:key="mobile-{{ $user->id }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <flux:checkbox 
                        value="{{ $user->id }}" 
                        wire:model.live="selectedUsers"
                        class="mt-1"
                    />
                    <div>
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        <div class="text-sm text-gray-500 capitalize">{{ $user->role }}</div>
                        @if($user->shop)
                        <div class="text-sm text-gray-500">{{ $user->shop->name }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm">{{ $user->phone }}</div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2 mt-3 pt-3 border-t">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="openUserModal({{ $user->id }})"
                >
                    Edit
                </flux:button>
                <flux:modal.trigger name="delete-confirmation" wire:click="confirmDelete({{ $user->id }})">
                    <flux:button size="sm" variant="danger">
                        Delete
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-4 text-center">
            No users found
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Shop</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->users as $user)
                    <tr wire:key="{{ $user->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <flux:checkbox 
                                value="{{ $user->id }}" 
                                wire:model.live="selectedUsers"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $user->role }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->shop?->name ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="openUserModal({{ $user->id }})"
                            >
                                Edit
                            </flux:button>
                            <flux:modal.trigger name="delete-confirmation" wire:click="confirmDelete({{ $user->id }})">
                                <flux:button size="sm" variant="danger">
                                    Delete
                                </flux:button>
                            </flux:modal.trigger>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center">No users found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->users->links() }}
    </div>

    <!-- User Modal -->
    <flux:modal 
        wire:model.self="showUserModal" 
        class="md:w-[500px]"
        wire:close="closeModals"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $userId ? 'Edit User' : 'Create New User' }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $userId ? 'Update the user details' : 'Add a new user to the system' }}
                </flux:text>
            </div>

            <form wire:submit.prevent="saveUser">
                <div class="space-y-4">
                    <flux:input 
                        wire:model="name" 
                        label="Full Name" 
                        placeholder="Enter full name"
                        required
                    />
                    @error('name') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="email" 
                        label="Email" 
                        type="email"
                        placeholder="Enter email"
                        required
                    />
                    @error('email') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:select 
                        wire:model="role" 
                        label="Role"
                        required
                    >
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="salesperson">Salesperson</option>
                    </flux:select>
                    @error('role') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:select 
                        wire:model="shop_id" 
                        label="Shop"
                        :disabled="in_array($role, ['admin'])"
                    >
                        <option value="">Select Shop</option>
                        @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('shop_id') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="phone" 
                        label="Phone" 
                        placeholder="Enter phone number"
                    />
                    @error('phone') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="password" 
                        label="{{ $userId ? 'New Password' : 'Password' }}" 
                        type="password"
                        placeholder="Enter password"
                        :required="!$userId"
                    />
                    @error('password') <flux:text class="!text-red-500 mt-1">{{ $message }}</flux:text> @enderror

                    <flux:input 
                        wire:model="password_confirmation" 
                        label="Confirm Password" 
                        type="password"
                        placeholder="Confirm password"
                        :required="!$userId"
                    />
                </div>
            </form>

            <div class="flex">
                <flux:spacer />
                
                <flux:button 
                    variant="primary" 
                    wire:click="saveUser"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ $userId ? 'Update' : 'Create' }}</span>
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
                <flux:heading size="lg">Delete User?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this user account.</p>
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
                    wire:click="deleteUser"
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
                <flux:heading size="lg">Delete Selected Users?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete {{ count($selectedUsers) }} user accounts.</p>
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