<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();

            $table->string('category'); // Dental / Aesthetic
            $table->string('treatment');

            $table->string('before_image');
            $table->string('after_image');

            $table->text('result');

            $table->string('duration')->nullable();

            $table->tinyInteger('rating')->nullable()->default(5);

            $table->text('testimonial')->nullable();

            $table->string('patient')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_studies');
    }
};