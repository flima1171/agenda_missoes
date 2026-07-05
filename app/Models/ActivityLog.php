<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trilha de auditoria "quem fez o quê". Cada linha é imutável: só tem
 * created_at (sem updated_at). O autor (user_id) é nullable de propósito —
 * ações de sistema (seeds, comandos artisan sem sessão) ficam sem autor.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public const UPDATED_AT = null;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'subject',
        'subject_id',
        'description',
    ];

    /**
     * Registra uma ação atribuída ao usuário logado (ou nula, se não houver).
     */
    public static function record(string $action, ?string $subject = null, ?int $subjectId = null, ?string $description = null): void
    {
        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject' => $subject,
            'subject_id' => $subjectId,
            'description' => $description,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
