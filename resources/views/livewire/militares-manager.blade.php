<div>
    <article class="card">
        <div class="card-head">
            <div>
                <h2>{{ $editingId ? 'Editar militar' : 'Novo militar' }}</h2>
                <p>Posto/graduação + nome de guerra, do jeito que deve aparecer nas missões.</p>
            </div>
        </div>
        <form wire:submit="save" style="padding: 0 20px 20px">
            <div class="form-grid">
                <div class="field">
                    <label for="mm-posto">Posto/Graduação *</label>
                    <input id="mm-posto" type="text" wire:model="posto_graduacao" maxlength="20" placeholder="Ex.: 3º Sgt">
                </div>
                <div class="field">
                    <label for="mm-nome">Nome de guerra *</label>
                    <input id="mm-nome" type="text" wire:model="nome_guerra" maxlength="60" placeholder="Ex.: Rodrigues Silva">
                </div>
                <div class="field">
                    <label for="mm-telegram">Telegram ID (opcional)</label>
                    <input id="mm-telegram" type="text" wire:model="telegram_id" maxlength="40">
                </div>
                <div class="field">
                    <label for="mm-telefone">Telefone (opcional)</label>
                    <input id="mm-telefone" type="text" wire:model="telefone" maxlength="20">
                </div>
            </div>
            @error('posto_graduacao') <p class="form-error">{{ $message }}</p> @enderror
            @error('nome_guerra') <p class="form-error">{{ $message }}</p> @enderror
            <div class="form-actions">
                @if ($editingId)
                    <button type="button" class="secondary-btn" wire:click="cancelEdit">Cancelar edição</button>
                @endif
                <button type="submit" class="primary-btn">{{ $editingId ? 'Salvar alterações' : 'Adicionar militar' }}</button>
            </div>
        </form>
    </article>

    <article class="card" style="margin-top: 17px; overflow-x: auto">
        <div class="card-head">
            <div>
                <h2>Militares cadastrados</h2>
                <p>Use ↑/↓ para reordenar. Inativar preserva o histórico das missões já atribuídas.</p>
            </div>
        </div>
        <table class="all-table">
            <thead>
                <tr>
                    <th style="width:70px">Ordem</th>
                    <th>Militar</th>
                    <th>Situação</th>
                    <th>Contato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($militares as $militar)
                    <tr wire:key="militar-{{ $militar->id }}">
                        <td style="white-space:nowrap">
                            <button type="button" class="icon-btn" style="width:30px;height:30px" title="Mover para cima" aria-label="Mover {{ $militar->nomeExibicao() }} para cima" wire:click="moveUp({{ $militar->id }})">↑</button>
                            <button type="button" class="icon-btn" style="width:30px;height:30px" title="Mover para baixo" aria-label="Mover {{ $militar->nomeExibicao() }} para baixo" wire:click="moveDown({{ $militar->id }})">↓</button>
                        </td>
                        <td>{{ $militar->nomeExibicao() }}</td>
                        <td>
                            <span class="badge {{ $militar->ativo ? 'tone-green' : 'tone-red' }}">
                                {{ $militar->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>{{ $militar->telefone ?: '—' }}</td>
                        <td style="text-align:right; white-space:nowrap">
                            <button type="button" class="secondary-btn" wire:click="edit({{ $militar->id }})">Editar</button>
                            <button type="button" class="secondary-btn {{ $militar->ativo ? 'danger-btn' : '' }}" wire:click="toggleAtivo({{ $militar->id }})">
                                {{ $militar->ativo ? 'Inativar' : 'Reativar' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--muted)">Nenhum militar cadastrado ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
</div>
