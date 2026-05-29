<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for 'audit_trails' table.
 */
class AuditTrail extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'audit_trails';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'ip_address'
    ];

    /**
     * Get the user that generated this audit log.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
