<div>
    <article class="card">
        <div class="card-head">
            <div>
                <h2>{{ $editingId ? 'Editar usuário' : 'Novo usuário' }}</h2>
                <p>Quem pode entrar no painel. Sem e-mail de recuperação — a senha é redefinida aqui.</p>
            </div>
        </div>
        <form wire:submit="save" style="padding: 0 20px 20px">
            <div class="form-grid">
                <div class="field">
                    <label for="um-name">Nome completo *</label>
                    <input id="um-name" type="text" wire:model="name" maxlength="120">
                </div>
                <div class="field">
                    <label for="um-nome-guerra">Nome de guerra (opcional)</label>
                    <input id="um-nome-guerra" type="text" wire:model="nome_guerra" maxlength="60" placeholder="Aparece na trilha e na conclusão">
                </div>
                <div class="field">
                    <label for="um-email">E-mail (login) *</label>
                    <input id="um-email" type="email" wire:model="email" maxlength="120" autocomplete="off">
                </div>
                <div class="field">
                    <label for="um-password">Senha {{ $editingId ? '(deixe em branco para manter)' : '*' }}</label>
                    <input id="um-password" type="password" wire:model="password" autocomplete="new-password">
                </div>
                <div class="field full">
                    <label class="auth-remember" style="margin:0">
                        <input type="checkbox" wire:model="is_admin">
                        Administrador (pode gerir militares e usuários)
                    </label>
                </div>
            </div>
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
            <div class="form-actions">
                @if ($editingId)
                    <button type="button" class="secondary-btn" wire:click="cancelEdit">Cancelar edição</button>
                @endif
                <button type="submit" class="primary-btn">{{ $editingId ? 'Salvar alterações' : 'Adicionar usuário' }}</button>
            </div>
        </form>
    </article>

    <article class="card" style="margin-top: 17px; overflow-x: auto">
        <div class="card-head">
            <div>
                <h2>Usuários cadastrados</h2>
                <p>O papel de administrador não pode ser removido de você mesmo.</p>
            </div>
        </div>
        @error('toggle') <p class="form-error" style="padding: 0 20px">{{ $message }}</p> @enderror
        <table class="all-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr wire:key="usuario-{{ $usuario->id }}">
                        <td>{{ $usuario->nomeExibicao() }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>
                            <span class="badge {{ $usuario->is_admin ? 'tone-green' : 'tone-blue' }}">
                                {{ $usuario->is_admin ? 'Administrador' : 'Usuário' }}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap">
                            <button type="button" class="secondary-btn" wire:click="edit({{ $usuario->id }})">Editar</button>
                            @if ($usuario->id !== auth()->id())
                                <button type="button" class="secondary-btn" wire:click="toggleAdmin({{ $usuario->id }})">
                                    {{ $usuario->is_admin ? 'Rebaixar' : 'Tornar admin' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:var(--muted)">Nenhum usuário cadastrado ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
</div>
