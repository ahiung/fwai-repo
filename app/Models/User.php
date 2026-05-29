<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for 'users' table.
 */
class User extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'name',
        'role_id'
    ];

    /**
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Get the role associated with the user.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
