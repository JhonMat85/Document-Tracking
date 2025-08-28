<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_number',
        'hes_id',
        'issue_date',
        'sent_to_client_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total',
        'currency',
        'accounting_area',
        'state_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hes_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'datetime',
        'sent_to_client_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    /**
     * Get the HES for this invoice (REQUIRED).
     */
    public function hes(): BelongsTo
    {
        return $this->belongsTo(Hes::class);
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this invoice.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get attachments for this invoice.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereDoesntHave('payments', function ($query) {
                $query->where('net_received_amount', '>=', function ($subQuery) {
                    $subQuery->selectRaw('invoices.total')
                        ->from('invoices')
                        ->whereRaw('invoices.id = payments.invoice_id');
                });
            });
    }
}