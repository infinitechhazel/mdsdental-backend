<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // User relation (nullable for walk-ins)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reservation_number')->unique();

            // Guest info
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Reservation details
            $table->date('date');
            $table->time('time');
            $table->unsignedInteger('guests');

            $table->string('package')->nullable();
            $table->string('occasion')->nullable();
            $table->string('dining_preference')->nullable();
            $table->text('special_requests')->nullable();

            // Pricing
            $table->decimal('reservation_fee', 12, 2)->default(0);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0); 
            $table->decimal('total_fee', 12, 2)->default(0);

            // Payment
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt')->nullable(); // stored file path
            $table->enum('payment_status', [
                'pending',
                'partially_paid',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            // Reservation status
            $table->enum('reservation_status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed'
            ])->default('pending');

            // Walk-in flag
            $table->boolean('is_walkin')->default(false);

            $table->timestamps();

            // Indexes (performance optimization)
            $table->index('user_id');
            $table->index('date');
            $table->index('reservation_number');
            $table->index('reservation_status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
