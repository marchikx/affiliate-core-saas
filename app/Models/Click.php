<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Click extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'affiliate_link_id',
        'visitor_id',
        'ip_address',
        'referer_url',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function affiliateLink(): BelongsTo
    {
        return $this->belongsTo(AffiliateLink::class);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(Conversion::class);
    }
}
