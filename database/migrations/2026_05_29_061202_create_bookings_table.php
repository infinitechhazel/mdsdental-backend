<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('patient_name');
            $table->string('patient_email')->nullable();
            $table->string('phone')->nullable();

            $table->string('service'); // Dental Cleaning, Botox, etc.

            $table->dateTime('scheduled_at'); // appointment time

            $table->enum('status', [
                'Pending',
                'Confirmed',
                'In Progress',
                'Completed',
                'Cancelled'
            ])->default('Pending');

            $table->decimal('amount', 10, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};