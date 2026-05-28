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

            $table->string('result');
            $table->string('duration')->nullable();

            $table->tinyInteger('rating')->default(5);
            $table->text('testimonial');

            $table->string('patient');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_studies');
    }
};