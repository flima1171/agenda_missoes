<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Militar extends Model
{
    /**
     * Nome da tabela ("Militar" pluraliza errado em inglês — "militars").
     *
     * @var string
     */
    protected $table = 'militares';

    /**
     * Campos preenchíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'posto_graduacao',
        'nome_guerra',
        'ativo',
        'ordem',
        'telegram_id',
        'telefone',
    ];

    /**
     * Conversões de tipo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    /**
     * Filtra apenas os militares ativos, na ordem de exibição configurada.
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true)->orderBy('ordem');
    }

    /**
     * Nome pronto para exibição (ex.: "3º Sgt Rodrigues Silva").
     *
     * As missões guardam este texto como "foto" do momento da atribuição (snapshot),
     * então promover/renomear um militar aqui NÃO reescreve missões já criadas.
     */
    public function nomeExibicao(): string
    {
        return trim($this->posto_graduacao.' '.$this->nome_guerra);
    }
}
