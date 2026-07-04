<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class MissionController extends Controller
{
    /**
     * Regras de validação compartilhadas entre criar e editar.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'responsible' => ['required', 'string', 'max:80'],
            'priority' => ['required', Rule::in(['baixa', 'media', 'alta'])],
            'status' => ['required', Rule::in(['pendente', 'andamento', 'concluida'])],
            'requester' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'completed_by' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * Ajusta os campos de conclusão conforme a situação escolhida.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyCompletion(array $data, ?Mission $atual = null): array
    {
        if (($data['status'] ?? null) === 'concluida') {
            $data['completed_by'] = $data['completed_by']
                ?: ($atual?->completed_by
                    ?: ($data['responsible'] === 'Toda a seção' ? null : $data['responsible']));
            $data['completed_at'] = $atual?->completed_at ?? now();
        } else {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
        }

        return $data;
    }

    /**
     * Lista todas as missões.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Mission::orderBy('date')->orderBy('time')->get()
        );
    }

    /**
     * Cria uma nova missão.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->applyCompletion($request->validate($this->rules()));

        $mission = Mission::create($data);

        return response()->json($mission, 201);
    }

    /**
     * Atualiza uma missão existente.
     */
    public function update(Request $request, Mission $mission): JsonResponse
    {
        $data = $this->applyCompletion($request->validate($this->rules()), $mission);

        $mission->update($data);

        return response()->json($mission);
    }

    /**
     * Remove uma missão.
     */
    public function destroy(Mission $mission): JsonResponse
    {
        $mission->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Restaura os dados de demonstração (limpa e re-semeia).
     */
    public function reset(): JsonResponse
    {
        Mission::query()->delete();
        Artisan::call('db:seed', ['--class' => 'MissionSeeder', '--force' => true]);

        return response()->json(
            Mission::orderBy('date')->orderBy('time')->get()
        );
    }
}
