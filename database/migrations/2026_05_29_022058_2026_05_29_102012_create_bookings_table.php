<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');      // who booked
            $table->unsignedBigInteger('service_id');   // service/treatment booked
            $table->dateTime('booking_date');           // scheduled date/time
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            $table->text('notes')->nullable();          // optional notes

            $table->timestamps();

            // Foreign keys (optional, if you have users/services tables)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
