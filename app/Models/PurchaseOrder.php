<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'po_number',
        'solped_number',
        'quote_id',
        'issue_date',
        'validity_date',
        'scheduled_execution_date',
        'assigned_technician',
        'reception_area',
        'authorized_amount',
        'currency',
        'special_conditions',
        'is_urgent',
        'state_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quote_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'authorized_amount' => 'decimal:2',
        'is_urgent' => 'boolean',
        'issue_date' => 'datetime',
        'validity_date' => 'datetime',
        'scheduled_execution_date' => 'datetime',
    ];

    /**
     * Get the quote for this purchase order.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this purchase order.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get executed services for this purchase order.
     */
    public function executedServices(): HasMany
    {
        return $this->hasMany(ExecutedService::class);
    }

    /**
     * Get attachments for this purchase order.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope for urgent purchase orders.
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }
}