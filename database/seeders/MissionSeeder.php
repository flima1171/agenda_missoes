<?php

namespace Database\Seeders;

use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    /**
     * Popula a tabela com missões de exemplo, relativas à semana atual.
     */
    public function run(): void
    {
        $hoje = Carbon::today();
        $segunda = $hoje->copy()->startOfWeek(Carbon::MONDAY);
        $iso = fn (Carbon $d) => $d->format('Y-m-d');

        $exemplos = [
            // [titulo, data, hora, responsaveis[], prioridade, situacao, demandante, obs, concluida_por]
            ['VC', $iso($segunda), '07:30', ['Asp Araújo'], 'alta', 'concluida', 'Cmt do 25º BC', 'Apresentação pronta no local previsto.', 'Asp Araújo'],
            ['Conferência de material carga', $iso($hoje), '08:30', ['Cb Luide'], 'alta', 'andamento', 'Fiscal Administrativo', 'Conferir relação e registrar alterações.', null],
            ['Atualizar mapa de efetivo', $iso($hoje), '10:00', ['3º Sgt Rodrigues Silva'], 'media', 'pendente', 'Adjunto', 'Enviar versão atualizada até o fim da manhã.', null],
            ['Entregar documentação no protocolo', $iso($hoje), '14:00', ['Sd EP Jones'], 'baixa', 'pendente', 'Chefe da Seção', 'Levar duas vias assinadas.', null],
            ['Reunião com o chefe da seção', $iso($hoje->copy()->addDay()), '09:00', ['Toda a seção'], 'media', 'pendente', 'Chefe da Seção', 'Pauta: planejamento da próxima semana.', null],
            ['Inspeção das instalações', $iso($hoje->copy()->addDays(2)), '08:00', ['Sd EP Ferreira Lima', 'Cb Luide'], 'alta', 'pendente', 'Comando', 'Verificar pendências antes da inspeção.', null],
            ['Fechamento do relatório semanal', $iso($hoje->copy()->addDays(3)), '15:30', ['3º Sgt Rodrigues Silva', 'Sd EP Jones'], 'media', 'pendente', 'Adjunto', 'Consolidar missões concluídas e pendentes.', null],
            ['Atualização do livro de partes', $iso($hoje->copy()->subDay()), '16:00', ['Sd EP Edilson'], 'baixa', 'concluida', 'Adjunto', 'Registro finalizado.', 'Sd EP Edilson'],
        ];

        foreach ($exemplos as $i => $x) {
            Mission::create([
                'title' => $x[0],
                'date' => $x[1],
                'time' => $x[2],
                'responsibles' => $x[3],
                'priority' => $x[4],
                'status' => $x[5],
                'requester' => $x[6],
                'notes' => $x[7],
                'completed_by' => $x[8],
                'completed_at' => $x[5] === 'concluida' ? Carbon::now()->subHours($i + 2) : null,
            ]);
        }
    }
}
