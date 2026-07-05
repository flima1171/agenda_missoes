<?php

namespace Tests\Unit;

use App\Models\Mission;
use Tests\TestCase;

/**
 * Baseline (Fase A0): captura o comportamento ATUAL de Mission::applyCompletion
 * antes de qualquer mudança. applyCompletion é lógica pura de array — não toca no
 * banco —, então basta a aplicação bootada (para os casts do model) sem RefreshDatabase.
 */
class MissionTest extends TestCase
{
    public function test_conclui_grava_completed_by_completed_at_e_previous_status(): void
    {
        $result = Mission::applyCompletion([
            'status' => 'concluida',
            'responsibles' => ['Cb Luide', 'Toda a seção'],
            'completed_by' => '',
        ]);

        // completed_by cai no primeiro responsável que não é "Toda a seção".
        $this->assertSame('Cb Luide', $result['completed_by']);
        $this->assertNotNull($result['completed_at']);
        // Sem missão anterior, não há situação prévia a guardar.
        $this->assertNull($result['previous_status']);
    }

    public function test_conclui_respeita_completed_by_informado_explicitamente(): void
    {
        $result = Mission::applyCompletion([
            'status' => 'concluida',
            'responsibles' => ['Cb Luide'],
            'completed_by' => 'Sd EP Jones',
        ]);

        $this->assertSame('Sd EP Jones', $result['completed_by']);
    }

    public function test_conclui_com_apenas_toda_a_secao_deixa_completed_by_nulo(): void
    {
        $result = Mission::applyCompletion([
            'status' => 'concluida',
            'responsibles' => ['Toda a seção'],
            'completed_by' => '',
        ]);

        $this->assertNull($result['completed_by']);
    }

    public function test_nao_conclui_limpa_os_tres_campos_de_conclusao(): void
    {
        $result = Mission::applyCompletion([
            'status' => 'pendente',
            'responsibles' => ['Cb Luide'],
            'completed_by' => 'Cb Luide',
            'completed_at' => now(),
            'previous_status' => 'andamento',
        ]);

        $this->assertNull($result['completed_by']);
        $this->assertNull($result['completed_at']);
        $this->assertNull($result['previous_status']);
    }

    public function test_previous_status_guarda_a_situacao_anterior_apenas_na_primeira_conclusao(): void
    {
        // 1ª conclusão: vinha de "andamento" → guarda "andamento".
        $emAndamento = new Mission(['status' => 'andamento', 'previous_status' => null]);
        $primeira = Mission::applyCompletion([
            'status' => 'concluida',
            'responsibles' => ['Cb Luide'],
            'completed_by' => '',
        ], $emAndamento);

        $this->assertSame('andamento', $primeira['previous_status']);
        $this->assertNotNull($primeira['completed_at']);
    }

    public function test_reconclusao_preserva_previous_status_completed_at_e_completed_by(): void
    {
        $original = now()->subHours(3);
        // Já concluída antes, com situação prévia "andamento" e autoria "João".
        $jaConcluida = new Mission([
            'status' => 'concluida',
            'previous_status' => 'andamento',
            'completed_at' => $original,
            'completed_by' => 'João',
        ]);

        $result = Mission::applyCompletion([
            'status' => 'concluida',
            'responsibles' => ['Cb Luide'],
            'completed_by' => '',
        ], $jaConcluida);

        // Não sobrescreve nada da primeira conclusão.
        $this->assertSame('andamento', $result['previous_status']);
        $this->assertSame('João', $result['completed_by']);
        $this->assertSame(
            $original->format('Y-m-d H:i:s'),
            $result['completed_at']->format('Y-m-d H:i:s')
        );
    }

    public function test_rules_exige_campos_obrigatorios_e_restringe_enumeracoes(): void
    {
        $rules = Mission::rules();

        $this->assertContains('required', $rules['title']);
        $this->assertContains('required', $rules['date']);
        $this->assertContains('required', $rules['time']);
        $this->assertContains('required', $rules['responsibles']);
        $this->assertContains('min:1', $rules['responsibles']);
    }
}
