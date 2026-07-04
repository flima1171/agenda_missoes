<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class ResponsibleSelector extends Component
{
    /**
     * Militares ativos (+ "Toda a seção"), na ordem vinda do painel.
     *
     * @var array<int, string>
     */
    public array $people = [];

    /**
     * Uma linha por responsável escolhido. Sempre começa com uma linha vazia
     * (o seletor progressivo "um por vez + botão +").
     *
     * @var array<int, string>
     */
    public array $rows = [''];

    /**
     * @param  array<int, string>  $people
     */
    public function mount(array $people = []): void
    {
        $this->people = $people;
    }

    /**
     * Recebe do JS (formulário de missão, ainda em vanilla JS até a Fase 5) a
     * lista de responsáveis da missão sendo aberta para edição/criação, já que
     * este componente Livewire persiste no DOM entre uma abertura de modal e
     * outra.
     *
     * @param  array<int, string>  $list
     */
    #[On('set-responsibles')]
    public function setResponsibles(array $list): void
    {
        $this->rows = $list !== [] ? array_values($list) : [''];
    }

    public function addRow(): void
    {
        $this->rows[] = '';
        $this->emitChange();
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->rows = [''];
        }

        $this->emitChange();
    }

    /**
     * Dispara toda vez que uma linha muda de valor (wire:model.live), já que
     * esse hook do Livewire só roda para mudanças vindas do front-end.
     */
    public function updated(string $name): void
    {
        if (str_starts_with($name, 'rows.')) {
            $this->emitChange();
        }
    }

    /**
     * Avisa o componente pai (Fase 5: App\Livewire\Painel) qual é a lista
     * atual de responsáveis escolhidos, sem valores vazios. Substitui a
     * ponte de checkbox escondido usada até a Fase 4 (o formulário de
     * missão agora é 100% Livewire, sem JS lendo o DOM).
     *
     * @return array<int, string>
     */
    private function emitChange(): void
    {
        $this->dispatch(
            'responsibles-changed',
            responsibles: collect($this->rows)->filter(fn ($v) => $v !== '')->values()->all()
        );
    }

    /**
     * Opções ainda disponíveis para a linha $index: o próprio valor atual
     * dela (para não sumir do <select>) + quem ainda não foi escolhido em
     * nenhuma outra linha.
     *
     * @return array<int, string>
     */
    public function optionsFor(int $index): array
    {
        $current = $this->rows[$index] ?? '';
        $usedElsewhere = collect($this->rows)
            ->except($index)
            ->filter(fn ($v) => $v !== '')
            ->all();

        return collect($this->people)
            ->reject(fn ($p) => in_array($p, $usedElsewhere, true) && $p !== $current)
            ->values()
            ->all();
    }

    public function canAddRow(): bool
    {
        $chosen = collect($this->rows)->filter(fn ($v) => $v !== '')->count();

        return $chosen < count($this->people) && $chosen === count($this->rows);
    }

    public function render()
    {
        return view('livewire.responsible-selector');
    }
}
