<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Postos/graduações válidos, na hierarquia (do mais alto ao mais baixo).
     * Chave = abreviação salva no banco/exibida na UI; valor = nome por extenso.
     *
     * @var array<string, string>
     */
    public const POSTOS = [
        'Cel' => 'Coronel',
        'TC' => 'Tenente-Coronel',
        'Maj' => 'Major',
        'Cap' => 'Capitão',
        '1º Ten' => '1º Tenente',
        '2º Ten' => '2º Tenente',
        'Asp' => 'Aspirante',
        'ST' => 'Sub-Tenente',
        '1º Sgt' => '1º Sargento',
        '2º Sgt' => '2º Sargento',
        '3º Sgt' => '3º Sargento',
        'Cb' => 'Cabo',
        'Sd EP' => 'Soldado EP',
        'Sd EV' => 'Soldado EV',
        'AL' => 'Aluno',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'posto_graduacao',
        'nome_guerra',
        'username',
        'password',
        'is_admin',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Nome curto para exibir na UI/trilha (ex.: "quem concluiu"). Usa o nome de
     * guerra quando houver; senão cai no nome completo.
     */
    public function nomeExibicao(): string
    {
        $nome = $this->nome_guerra ?: $this->name;

        return $this->posto_graduacao ? trim($this->posto_graduacao.' '.$nome) : $nome;
    }
}
