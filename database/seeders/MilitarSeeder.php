<?php

namespace Database\Seeders;

use App\Models\Militar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MilitarSeeder extends Seeder
{
    /**
     * Popula a tabela `militares` a partir da lista que antes era o array fixo
     * `$painelPeople` em resources/views/painel.blade.php. "Toda a seção" NÃO
     * entra aqui: não é um militar (não pode ser promovido/inativado), continua
     * sendo uma opção fixa somada na view.
     */
    public function run(): void
    {
        $militares = [
            ['posto_graduacao' => 'Asp', 'nome_guerra' => 'Araújo'],
            ['posto_graduacao' => '3º Sgt', 'nome_guerra' => 'Rodrigues Silva'],
            ['posto_graduacao' => 'Cb', 'nome_guerra' => 'Luide'],
            ['posto_graduacao' => 'Sd EP', 'nome_guerra' => 'Jones'],
            ['posto_graduacao' => 'Sd EP', 'nome_guerra' => 'Ferreira Lima'],
            ['posto_graduacao' => 'Sd EP', 'nome_guerra' => 'Edilson'],
        ];

        foreach ($militares as $ordem => $dados) {
            Militar::firstOrCreate(
                ['posto_graduacao' => $dados['posto_graduacao'], 'nome_guerra' => $dados['nome_guerra']],
                ['ativo' => true, 'ordem' => $ordem + 1]
            );
        }
    }
}
