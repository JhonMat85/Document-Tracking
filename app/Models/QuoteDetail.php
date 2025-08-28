<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'quote_id',
        'item_order',
        'quantity',
        'unit_of_measure',
        'item_description',
        'unit_price',
        'item_subtotal',
        'item_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quote_id' => 'integer',
        'item_order' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_subtotal' => 'decimal:2',
    ];

    /**
     * Get the quote that owns this detail.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}