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

    /**
     * Generar número de cotización único
     */
    public static function generateQuoteNumber(): string
    {
        $year = date('Y');
        $maxAttempts = 10;
        $attempt = 0;

        do {
            // Buscar todas las cotizaciones del año actual
            $quotesThisYear = self::where('quote_number', 'LIKE', "COT-{$year}-%")
                ->orderBy('quote_number', 'desc')
                ->pluck('quote_number')
                ->toArray();

            // Extraer los números secuenciales
            $sequentialNumbers = [];
            foreach ($quotesThisYear as $quoteNumber) {
                $parts = explode('-', $quoteNumber);
                if (count($parts) === 3) {
                    $sequentialNumbers[] = (int)$parts[2];
                }
            }

            // Obtener el siguiente número disponible
            $nextNumber = !empty($sequentialNumbers) ? max($sequentialNumbers) + 1 : 1;
            $generatedNumber = 'COT-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Verificar si ya existe
            $exists = self::where('quote_number', $generatedNumber)->exists();

            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            // Si no se pudo generar un número único, usar timestamp como respaldo
            $generatedNumber = 'COT-' . $year . '-' . date('mdHis');
        }

        return $generatedNumber;
    }

    /**
     * Validar si un número de cotización ya existe
     */
    public static function isQuoteNumberExists(string $quoteNumber, ?int $excludeId = null): bool
    {
        $query = self::where('quote_number', $quoteNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Generar un nuevo número de cotización para una cotización existente (revisiones)
     */
    public function generateRevisionNumber(): string
    {
        $baseNumber = $this->quote_number;

        // Si ya es una revisión, incrementar la versión
        if (preg_match('/-REV(\d+)$/', $baseNumber, $matches)) {
            $revisionNumber = (int)$matches[1] + 1;
            $newNumber = preg_replace('/-REV\d+$/', "-REV{$revisionNumber}", $baseNumber);
        } else {
            // Primera revisión
            $newNumber = $baseNumber . '-REV1';
        }

        // Asegurar que sea único
        $counter = 1;
        $originalNumber = $newNumber;
        while (self::isQuoteNumberExists($newNumber, $this->id)) {
            $newNumber = $originalNumber . '-' . $counter;
            $counter++;
        }

        return $newNumber;
    }
}