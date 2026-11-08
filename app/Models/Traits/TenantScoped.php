<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $user = auth()->user();

            if ($user && $user->tenant_id !== null) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    $user->tenant_id
                );
            }
        });

        static::creating(function (Model $model): void {
            $user = auth()->user();

            if ($user && $user->tenant_id !== null && ! $model->isDirty('tenant_id')) {
                $model->setAttribute('tenant_id', $user->tenant_id);
            }
        });
    }
}
