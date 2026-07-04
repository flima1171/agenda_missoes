<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
