<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTransport extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'quote_transport';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'quote_id',
        'driver_name',
        'driver_license',
        'vehicle_plate',
        'vehicle_model',
        'vehicle_capacity',
        'includes_insurance',
        'transport_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quote_id' => 'integer',
        'includes_insurance' => 'boolean',
    ];

    /**
     * Get the quote that owns this transport.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}