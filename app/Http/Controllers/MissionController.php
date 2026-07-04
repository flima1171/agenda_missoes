<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MissionController extends Controller
{
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
        $data = Mission::applyCompletion($request->validate(Mission::rules()));

        $mission = Mission::create($data);

        return response()->json($mission, 201);
    }

    /**
     * Atualiza uma missão existente.
     */
    public function update(Request $request, Mission $mission): JsonResponse
    {
        $data = Mission::applyCompletion($request->validate(Mission::rules()), $mission);

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
     *
     * Disponível apenas em ambiente local: apaga TODAS as missões, o que seria
     * destrutivo demais para permitir fora de desenvolvimento/homologação.
     */
    public function reset(): JsonResponse
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        Mission::query()->delete();
        Artisan::call('db:seed', ['--class' => 'MissionSeeder', '--force' => true]);

        return response()->json(
            Mission::orderBy('date')->orderBy('time')->get()
        );
    }
}
