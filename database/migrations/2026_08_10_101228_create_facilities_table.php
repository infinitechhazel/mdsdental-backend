<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();

            $table->string('icon_name')->default('Wind');
            $table->string('label');
            $table->string('name');
            $table->text('description');

            $table->longText('bullets')->nullable();

            $table->enum('accent', ['cyan', 'blue'])
                ->default('cyan');

            $table->string('image_path')->nullable();

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};