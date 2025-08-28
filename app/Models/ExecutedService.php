<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExecutedService extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'purchase_order_id',
        'start_date',
        'end_date',
        'main_technician',
        'work_team',
        'work_description',
        'incidents',
        'execution_hours',
        'state_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'purchase_order_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'execution_hours' => 'decimal:2',
        'work_team' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get the purchase order for this executed service.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this executed service.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this executed service.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get HES records for this executed service.
     */
    public function hes(): HasMany
    {
        return $this->hasMany(Hes::class);
    }

    /**
     * Get attachments for this executed service.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}