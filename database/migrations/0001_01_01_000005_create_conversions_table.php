<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('click_id')->constrained('clicks')->cascadeOnDelete();
            $table->string('external_reference', 255)->nullable();
            $table->decimal('sale_amount', 12, 4);
            $table->decimal('commission_amount', 12, 4);
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'paid',
            ])->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
