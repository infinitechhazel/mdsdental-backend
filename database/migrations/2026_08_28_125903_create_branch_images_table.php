<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_images', function (Blueprint $table) {
            $table->id();

            /*
             */
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            /*
             * clinic or team
             */
            $table->enum('type', [
                'clinic',
                'team',
            ]);

            /*
             * Example:
             *
             * images/branches/sm-gensan/clinic/uuid.jpg
             */
            $table->text('path');

            $table->string('alt')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            /*
             * Useful when retrieving images for a branch.
             */
            $table->index([
                'branch_id',
                'type',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_images');
    }
};
