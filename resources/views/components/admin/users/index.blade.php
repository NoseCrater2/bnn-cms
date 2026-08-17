<?php

use App\Models\User;
use \Livewire\WithPagination;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $sortBy = 'name';
    public $sortDirection = 'desc';
    public $showUserModal = true;
    public $userData = [
        'name' => null,
        'email' => null,
        'role' => null,
        'password' => null,
        'email_verified_at' => null,
    ];

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function saveUser() {
        $this->userData['password'] = 'password';
        $this->userData['email_verified_at'] = now();
        User::create($this->userData);
        Flux::modal('user-modal')->close();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }
};
?>

 <div>
    <flux:heading size="xl" level="1">Administración de usuarios</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea y edita usuarios</flux:text>

    <div class="flex justify-end gap-4">
        <flux:modal.trigger name="user-modal">
            <flux:button>Nuevo usuario</flux:button>
        </flux:modal.trigger>
    </div>
    <flux:table :paginate="$this->users">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Usuario</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">Email</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'role'" :direction="$sortDirection" wire:click="sort('role')">Rol</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->users as $user)
            <flux:table.row :key="$user->id">
                <flux:table.cell class="flex items-center gap-3">
                    <flux:avatar :name="$user->name" />
                    {{ $user->name }}
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">{{ $user->email }}</flux:table.cell>

                <flux:table.cell class="py-0">
                    <flux:badge size="sm">{{ $user->role }}</flux:badge>
                </flux:table.cell>


                <flux:table.cell class="py-0">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"></flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
<flux:modal name="user-modal" class="md:w-96">
    <form wire:submit="saveUser">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo usuario</flux:heading>
                <flux:text class="mt-2">Todo nuevo usuario tendrá la misma contraseña: "password"</flux:text>
            </div>
            <flux:input label="Nombre" wire:model="userData.name" />
            <flux:input type="email" label="Email"  wire:model="userData.email" />
            <flux:select placeholder="Rol"  wire:model="userData.role">
                <flux:select.option label="Admin" value="admin"></flux:select.option>
                <flux:select.option label="Usuario" value="admin">Usuario</flux:select.option>
            </flux:select>
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
    </div>
    </form>
</flux:modal>
 </div>


