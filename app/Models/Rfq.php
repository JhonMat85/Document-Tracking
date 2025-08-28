<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'rfq_number',
        'request_id',
        'received_date',
        'quote_deadline',
        'detailed_description',
        'state_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'received_date' => 'datetime',
        'quote_deadline' => 'datetime',
    ];

    /**
     * Get the request for this RFQ.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this RFQ.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this RFQ.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get quotes for this RFQ.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get attachments for this RFQ.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope for pending quotes by deadline.
     */
    public function scopePendingQuote($query)
    {
        return $query->where('quote_deadline', '>=', now());
    }
}