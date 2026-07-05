<div class="resp-rows">
    @foreach ($rows as $i => $value)
        <div class="resp-row" wire:key="resp-row-{{ $i }}">
            <select wire:model.live="rows.{{ $i }}">
                <option value="">Selecione um militar…</option>
                @foreach ($this->optionsFor($i) as $person)
                    <option value="{{ $person }}">{{ $person }}@if ($this->isInactive($person)) (inativo)@endif</option>
                @endforeach
            </select>
            @if (count($rows) > 1)
                <button type="button" class="resp-remove" wire:click="removeRow({{ $i }})" title="Remover">×</button>
            @endif
        </div>
    @endforeach
    @if ($this->canAddRow())
        <button type="button" class="text-btn resp-add" wire:click="addRow">+ Adicionar responsável</button>
    @endif
</div>
