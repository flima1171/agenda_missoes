<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Fase A2: gestão de usuários (só admin). Cria usuários, redefine senha e
 * alterna o papel de administrador. Sem "esqueci a senha" (offline) — a
 * redefinição é feita aqui ou pelo comando app:create-user.
 */
class UsuariosManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $nome_guerra = '';

    public string $email = '';

    public string $password = '';

    public bool $is_admin = false;

    public function mount(): void
    {
        // Defesa em profundidade: além do middleware 'admin' na rota.
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'nome_guerra' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($this->editingId)],
            // Senha obrigatória ao criar; opcional (deixe em branco para manter) ao editar.
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
        ];
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->nome_guerra = $user->nome_guerra ?? '';
        $this->email = $user->email;
        $this->password = '';
        $this->is_admin = $user->is_admin;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'nome_guerra', 'email', 'password', 'is_admin']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'nome_guerra' => $data['nome_guerra'] ?: null,
            'email' => $data['email'],
            'is_admin' => $data['is_admin'],
        ];

        if ($data['password'] !== '' && $data['password'] !== null) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($payload);
            ActivityLog::record('editar_usuario', 'usuario', $user->id, 'Editou o usuário "'.$user->email.'".');
        } else {
            $user = User::create($payload);
            ActivityLog::record('criar_usuario', 'usuario', $user->id, 'Criou o usuário "'.$user->email.'".');
        }

        $this->cancelEdit();
    }

    /**
     * Alterna o papel de admin. Impede o usuário logado de remover o próprio
     * papel (evita ficar sem nenhum administrador por engano).
     */
    public function toggleAdmin(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->addError('toggle', 'Você não pode alterar seu próprio papel de administrador.');

            return;
        }

        $user->update(['is_admin' => ! $user->is_admin]);
        ActivityLog::record(
            $user->is_admin ? 'promover_admin' : 'rebaixar_admin',
            'usuario',
            $user->id,
            ($user->is_admin ? 'Promoveu a administrador' : 'Removeu o papel de administrador de').' "'.$user->email.'".'
        );
    }

    public function render()
    {
        return view('livewire.usuarios-manager', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }
}
