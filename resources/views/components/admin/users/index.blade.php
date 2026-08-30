<?php

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public ?int $editingUserId = null;
    public array $userData = ['name' => '', 'email' => '', 'role' => 'user', 'password' => ''];

    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['name', 'email', 'role'], true), 400);
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function createUser(): void
    {
        $this->resetUserForm();
        Flux::modal('user-modal')->show();
    }

    public function editUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->userData = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'password' => ''];
        $this->resetValidation();
        Flux::modal('user-modal')->show();
    }

    public function saveUser(): void
    {
        $validated = $this->validate([
            'userData.name' => ['required', 'string', 'max:255'],
            'userData.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'userData.role' => ['required', Rule::enum(UserRole::class)],
            'userData.password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
        ])['userData'];

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            if ($validated['password'] === '') {
                unset($validated['password']);
            }
            $user->update($validated);
            $message = 'Usuario actualizado correctamente.';
        } else {
            $validated['email_verified_at'] = now();
            User::create($validated);
            $message = 'Usuario creado correctamente.';
        }

        Flux::modal('user-modal')->close();
        Flux::toast(variant: 'success', text: $message);
        $this->resetUserForm();
    }

    public function deleteUser(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(variant: 'danger', text: 'No puedes eliminar tu propio usuario.');
            return;
        }
        $user = User::with('articles')->findOrFail($id);
        foreach ($user->articles as $article) {
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
        }
        $user->delete();
        $this->resetPage();
        Flux::toast(variant: 'success', text: 'Usuario eliminado correctamente.');
    }

    private function resetUserForm(): void
    {
        $this->editingUserId = null;
        $this->userData = ['name' => '', 'email' => '', 'role' => 'user', 'password' => ''];
        $this->resetValidation();
    }

    #[Computed]
    public function users()
    {
        return User::query()->orderBy($this->sortBy, $this->sortDirection)->paginate(5);
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Administración de usuarios</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Crea, edita y elimina usuarios</flux:text>
    <div class="flex justify-end gap-4"><flux:button wire:click="createUser">Nuevo usuario</flux:button></div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Usuario</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'role'" :direction="$sortDirection" wire:click="sort('role')">Rol</flux:table.column>
            <flux:table.column>Acciones</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell class="flex items-center gap-3"><flux:avatar :name="$user->name" />{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm">{{ $user->role->value }}</flux:badge></flux:table.cell>
                    <flux:table.cell><div class="flex gap-2">
                        <flux:button wire:click="editUser({{ $user->id }})" variant="ghost" size="sm" icon="pencil-square">Editar</flux:button>
                        <flux:button wire:click="deleteUser({{ $user->id }})" wire:confirm="¿Eliminar este usuario y todos sus artículos?" variant="danger" size="sm" icon="trash" :disabled="$user->id === auth()->id()">Eliminar</flux:button>
                    </div></flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="user-modal" class="md:w-96">
        <form wire:submit="saveUser" class="space-y-6">
            <div><flux:heading size="lg">{{ $editingUserId ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>
                <flux:text class="mt-2">{{ $editingUserId ? 'Deja la contraseña vacía para conservar la actual.' : 'Asigna una contraseña de al menos 8 caracteres.' }}</flux:text></div>
            <flux:input label="Nombre" wire:model="userData.name" />
            <flux:input type="email" label="Email" wire:model="userData.email" />
            <flux:select label="Rol" wire:model="userData.role"><flux:select.option value="admin">Administrador</flux:select.option><flux:select.option value="user">Usuario</flux:select.option></flux:select>
            <flux:input type="password" label="Contraseña" wire:model="userData.password" />
            <div class="flex"><flux:spacer /><flux:button type="submit" variant="primary">Guardar</flux:button></div>
        </form>
    </flux:modal>
</div>
