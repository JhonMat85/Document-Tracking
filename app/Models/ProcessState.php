<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessState extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity',
        'code',
        'name',
        'description',
        'color',
        'display_order',
        'is_initial_state',
        'is_final_state',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_initial_state' => 'boolean',
        'is_final_state' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Scope for active states.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific entity.
     */
    public function scopeForEntity($query, string $entity)
    {
        return $query->where('entity', $entity);
    }
}