<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Redirect extends Model
{
    use HasFactory;
    protected $fillable = [
        'source_type',
        'source_domain',
        'source_path',
        'destination',
        'preserve_path',
        'preserve_query_string',
        'force_https',
        'case_sensitive',
        'trailing_slash_mode',
        'status_code',
        'is_active',
        'active_from',
        'active_until',
        'priority',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'preserve_path' => 'boolean',
        'preserve_query_string' => 'boolean',
        'force_https' => 'boolean',
        'case_sensitive' => 'boolean',
        'status_code' => 'integer',
        'priority' => 'integer',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    /**
     * Get the logs for the redirect.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RedirectLog::class);
    }

    /**
     * Scope to get active redirects (including schedule checks).
     */
    public function scopeActive($query)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('active_from')
                  ->orWhere('active_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('active_until')
                  ->orWhere('active_until', '>=', $now);
            });
    }

    /**
     * Check if redirect is currently scheduled to be active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->active_from && $this->active_from->isAfter($now)) {
            return false;
        }

        if ($this->active_until && $this->active_until->isBefore($now)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get domain redirects.
     */
    public function scopeDomainType($query)
    {
        return $query->where('source_type', 'domain');
    }

    /**
     * Scope to get URL redirects.
     */
    public function scopeUrlType($query)
    {
        return $query->where('source_type', 'url');
    }

    /**
     * Scope to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Get the full source URL or domain.
     */
    public function getSourceAttribute(): string
    {
        if ($this->source_type === 'domain') {
            return $this->source_domain;
        }
        
        return $this->source_path;
    }
}
