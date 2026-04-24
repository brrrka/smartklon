<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->string('epc_id')->primary();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->enum('status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
