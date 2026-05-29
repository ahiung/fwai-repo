<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for 'kantor_cabang' table.
 */
class KantorCabang extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'kantor_cabang';

    /**
     * @var array
     */
    protected $fillable = [
        'office_code',
        'name',
        'office_type',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'phone',
        'email',
        'latitude',
        'longitude',
        'manager_id',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'manager_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Get the user who manages this office branch.
     *
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the user who created this record.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
