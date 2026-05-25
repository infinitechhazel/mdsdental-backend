<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('settings_id')->constrained('settings')->onDelete('cascade');

            $table->string('key');
            // e.g. cash, gcash, qrph, bpi

            $table->string('display_name');
            // display name

            $table->string('type');
            // cash | ewallet | bank | qr

            // optional depending on type
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();

            // for QR-based payments
            $table->string('qr_code')->nullable();

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
