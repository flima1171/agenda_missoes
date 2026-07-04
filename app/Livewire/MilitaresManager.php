<?php

namespace App\Livewire;

use App\Models\Militar;
use Livewire\Component;

class MilitaresManager extends Component
{
    public ?int $editingId = null;

    public string $posto_graduacao = '';

    public string $nome_guerra = '';

    public ?string $telegram_id = null;

    public ?string $telefone = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'posto_graduacao' => ['required', 'string', 'max:20'],
            'nome_guerra' => ['required', 'string', 'max:60'],
            'telegram_id' => ['nullable', 'string', 'max:40'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Carrega um militar existente no formulário para edição.
     */
    public function edit(int $id): void
    {
        $militar = Militar::findOrFail($id);

        $this->editingId = $militar->id;
        $this->posto_graduacao = $militar->posto_graduacao;
        $this->nome_guerra = $militar->nome_guerra;
        $this->telegram_id = $militar->telegram_id;
        $this->telefone = $militar->telefone;
    }

    /**
     * Limpa o formulário e sai do modo de edição.
     */
    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'posto_graduacao', 'nome_guerra', 'telegram_id', 'telefone']);
        $this->resetValidation();
    }

    /**
     * Cria um militar novo ou salva a edição de um existente. Promoção/renomeação
     * só muda como o nome aparece nas MISSÕES FUTURAS — as já criadas guardam o
     * nome como texto (snapshot) e não são reescritas.
     */
    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Militar::findOrFail($this->editingId)->update($data);
        } else {
            $data['ativo'] = true;
            $data['ordem'] = (int) (Militar::max('ordem') ?? 0) + 1;
            Militar::create($data);
        }

        $this->cancelEdit();
    }

    /**
     * Alterna ativo/inativo. Nunca apaga: precisamos preservar o histórico de
     * missões já atribuídas a este militar.
     */
    public function toggleAtivo(int $id): void
    {
        $militar = Militar::findOrFail($id);
        $militar->update(['ativo' => ! $militar->ativo]);
    }

    /**
     * Troca a posição deste militar com a do anterior na lista de ordem.
     */
    public function moveUp(int $id): void
    {
        $atual = Militar::findOrFail($id);

        $anterior = Militar::where('ordem', '<', $atual->ordem)
            ->orderByDesc('ordem')
            ->first();

        if ($anterior) {
            $ordemAtual = $atual->ordem;
            $atual->update(['ordem' => $anterior->ordem]);
            $anterior->update(['ordem' => $ordemAtual]);
        }
    }

    /**
     * Troca a posição deste militar com a do próximo na lista de ordem.
     */
    public function moveDown(int $id): void
    {
        $atual = Militar::findOrFail($id);

        $proximo = Militar::where('ordem', '>', $atual->ordem)
            ->orderBy('ordem')
            ->first();

        if ($proximo) {
            $ordemAtual = $atual->ordem;
            $atual->update(['ordem' => $proximo->ordem]);
            $proximo->update(['ordem' => $ordemAtual]);
        }
    }

    public function render()
    {
        return view('livewire.militares-manager', [
            'militares' => Militar::orderBy('ordem')->get(),
        ]);
    }
}
