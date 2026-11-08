<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversion extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'click_id',
        'external_reference',
        'sale_amount',
        'commission_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_amount'       => 'decimal:4',
            'commission_amount' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(Click::class);
    }
}
