<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Mission extends Model
{
    /**
     * Campos preenchíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'date',
        'time',
        'responsibles',
        'priority',
        'status',
        'requester',
        'notes',
        'completed_by',
        'completed_at',
        'previous_status',
    ];

    /**
     * Conversões de tipo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // A data é guardada como texto "AAAA-MM-DD" para casar 1:1 com o front.
            'date' => 'string',
            'completed_at' => 'datetime',
            'responsibles' => 'array',
        ];
    }

    /**
     * Regras de validação centralizadas no model, consumidas pelo formulário
     * Livewire (App\Livewire\Painel), para não duplicar as constraints. A antiga
     * API JSON (MissionController) foi removida na Fase A2.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            // Intervalo sensato (A3): evita datas absurdas como "2999-12-31" ou
            // anos anteriores ao próprio serviço. Casado com date_format:Y-m-d.
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before:2100-01-01'],
            'time' => ['required', 'date_format:H:i'],
            'responsibles' => ['required', 'array', 'min:1'],
            'responsibles.*' => ['string', 'max:80'],
            'priority' => ['required', Rule::in(['baixa', 'media', 'alta'])],
            'status' => ['required', Rule::in(['pendente', 'andamento', 'concluida'])],
            'requester' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'completed_by' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * Ajusta os campos de conclusão conforme a situação escolhida. Centralizado
     * no model pelo mesmo motivo do rules() acima.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyCompletion(array $data, ?self $atual = null): array
    {
        if (($data['status'] ?? null) === 'concluida') {
            // Quando ninguém é indicado explicitamente, usa o primeiro responsável
            // que não seja "Toda a seção" (não faz sentido a seção inteira "ser" quem concluiu).
            $responsibles = $data['responsibles'] ?? [];
            $fallback = collect($responsibles)->first(fn ($r) => $r !== 'Toda a seção');

            $data['completed_by'] = $data['completed_by']
                ?: ($atual?->completed_by ?: $fallback);
            $data['completed_at'] = $atual?->completed_at ?? now();
            // Guarda a situação anterior (só na primeira vez que é concluída) para
            // o "Reabrir" conseguir restaurá-la em vez de sempre virar "pendente".
            $data['previous_status'] = $atual && $atual->status !== 'concluida'
                ? $atual->status
                : $atual?->previous_status;
        } else {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
            $data['previous_status'] = null;
        }

        return $data;
    }
}
