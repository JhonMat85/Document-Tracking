<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientContact extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_id',
        'full_name',
        'position',
        'department',
        'phone',
        'email',
        'is_primary_contact',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'client_id' => 'integer',
        'is_primary_contact' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the client that owns this contact.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope for active contacts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary_contact', true);
    }
}