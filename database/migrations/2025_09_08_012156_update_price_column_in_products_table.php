<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
      public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Change price column to DECIMAL(15,2)
            $table->decimal('price', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert back to DECIMAL(8,2) if rolled back
            $table->decimal('price', 8, 2)->change();
        });
    }
};
