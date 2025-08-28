<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'quote_number',
        'request_id',
        'rfq_id',
        'destination_contact_id',
        'service_description',
        'pickup_address',
        'delivery_address',
        'service_start_date',
        'service_end_date',
        'subtotal',
        'tax_percentage',
        'tax_amount',
        'total',
        'currency',
        'proposed_payment_method',
        'version',
        'parent_quote_id',
        'generation_date',
        'sent_date',
        'response_date',
        'expiration_date',
        'state_id',
        'template_used',
        'created_by',
        'updated_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_id' => 'integer',
        'rfq_id' => 'integer',
        'destination_contact_id' => 'integer',
        'parent_quote_id' => 'integer',
        'state_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'version' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'service_start_date' => 'datetime',
        'service_end_date' => 'datetime',
        'generation_date' => 'datetime',
        'sent_date' => 'datetime',
        'response_date' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    /**
     * Get the request for this quote.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the RFQ for this quote.
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * Get the destination contact.
     */
    public function destinationContact(): BelongsTo
    {
        return $this->belongsTo(ClientContact::class, 'destination_contact_id');
    }

    /**
     * Get the parent quote (for versions).
     */
    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'parent_quote_id');
    }

    /**
     * Get child quote versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Quote::class, 'parent_quote_id');
    }

    /**
     * Get the current state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(ProcessState::class, 'state_id');
    }

    /**
     * Get the user who created this quote.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this quote.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get quote details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(QuoteDetail::class);
    }

    /**
     * Get quote transport information.
     */
    public function transport(): HasOne
    {
        return $this->hasOne(QuoteTransport::class);
    }

    /**
     * Get purchase orders for this quote.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get attachments for this quote.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}