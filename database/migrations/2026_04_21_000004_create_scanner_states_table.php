<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scanner_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->enum('active_mode', ['idle', 'batch_in', 'single_in', 'out', 'check_stock'])->default('idle');
            $table->foreignId('target_item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_states');
    }
};
