<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Gestão de usuários (só admin). Cria usuários, redefine senha e alterna o
 * papel de administrador. Sem "esqueci a senha" (offline) — a redefinição é
 * feita aqui ou pelo comando app:create-user.
 */
class UsuariosManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $posto_graduacao = '';

    public string $nome_guerra = '';

    public string $username = '';

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
            'posto_graduacao' => ['required', Rule::in(array_keys(User::POSTOS))],
            'nome_guerra' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'max:120', Rule::unique('users', 'username')->ignore($this->editingId)],
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
        $this->posto_graduacao = $user->posto_graduacao ?? '';
        $this->nome_guerra = $user->nome_guerra ?? '';
        $this->username = $user->username;
        $this->password = '';
        $this->is_admin = $user->is_admin;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'posto_graduacao', 'nome_guerra', 'username', 'password', 'is_admin']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'posto_graduacao' => $data['posto_graduacao'],
            'nome_guerra' => $data['nome_guerra'],
            'username' => $data['username'],
            'is_admin' => $data['is_admin'],
        ];

        if ($data['password'] !== '' && $data['password'] !== null) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            if ($user->is_admin && ! $data['is_admin'] && User::where('is_admin', true)->count() <= 1) {
                $this->addError('is_admin', 'Não é possível remover o último administrador do sistema.');

                return;
            }

            $user->update($payload);
            ActivityLog::record('editar_usuario', 'usuario', $user->id, 'Editou o usuário "'.$user->username.'".');
        } else {
            $user = User::create($payload);
            ActivityLog::record('criar_usuario', 'usuario', $user->id, 'Criou o usuário "'.$user->username.'".');
        }

        $this->cancelEdit();
    }

    /**
     * Alterna o papel de admin. Impede o usuário logado de remover o próprio
     * papel e impede remover o papel do último administrador restante —
     * em ambos os casos, evita que o sistema fique sem nenhum administrador.
     */
    public function toggleAdmin(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->addError('toggle', 'Você não pode alterar seu próprio papel de administrador.');

            return;
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            $this->addError('toggle', 'Não é possível remover o último administrador do sistema.');

            return;
        }

        $user->update(['is_admin' => ! $user->is_admin]);
        ActivityLog::record(
            $user->is_admin ? 'promover_admin' : 'rebaixar_admin',
            'usuario',
            $user->id,
            ($user->is_admin ? 'Promoveu a administrador' : 'Removeu o papel de administrador de').' "'.$user->username.'".'
        );
    }

    public function render()
    {
        return view('livewire.usuarios-manager', [
            'usuarios' => User::orderBy('name')->get(),
            'postos' => User::POSTOS,
        ]);
    }
}
