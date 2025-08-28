<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hes extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'hes_number',
        'executed_service_id',
        'reference_po_number',
        'reference_solped_number',
        'approval_date',
        'approver_name',
        'approver_position',
        'authorized_amount',
        'currency',
        'payment_method',
        'payment_days',
        'discount_percentage',
        'imputation_type',
        'state_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'executed_service_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'authorized_amount' => 'decimal:2',
        'payment_days' => 'integer',
        'discount_percentage' => 'decimal:2',
        'approval_date' => 'datetime',
    ];

    /**
     * Get the executed service for this HES.
     */
    public function executedService(): BelongsTo
    {
        return $this->belongsTo(ExecutedService::class);
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this HES.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this HES.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get invoices for this HES.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get attachments for this HES.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope for factoring payment method.
     */
    public function scopeFactoring($query)
    {
        return $query->where('payment_method', 'factoring');
    }
}