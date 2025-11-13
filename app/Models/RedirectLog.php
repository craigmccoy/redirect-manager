<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectLog extends Model
{
    use HasFactory;
    
    const UPDATED_AT = null;

    protected $fillable = [
        'redirect_id',
        'request_domain',
        'request_path',
        'request_method',
        'request_url',
        'destination_url',
        'status_code',
        'ip_address',
        'user_agent',
        'referer',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the redirect that owns the log.
     */
    public function redirect(): BelongsTo
    {
        return $this->belongsTo(Redirect::class);
    }

    /**
     * Scope to get logs within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get logs for a specific redirect.
     */
    public function scopeForRedirect($query, $redirectId)
    {
        return $query->where('redirect_id', $redirectId);
    }
}
