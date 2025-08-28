<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_editable',
        'group_name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_editable' => 'boolean',
        'value' => 'string',
    ];

    /**
     * Cast value based on type.
     */
    public function getValueAttribute($value)
    {
        return match($this->type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}