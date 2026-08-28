<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            /*
             * Public/business identifier.
             *
             * Example:
             * sm-gensan
             * manila
             * davao
             */
            $table->string('branch_id', 100)->unique();

            $table->string('name');
            $table->string('area')->nullable();

            $table->string('phone', 100)->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('hours')->nullable();

            $table->text('map_query')->nullable();
            $table->text('directions_url')->nullable();

            $table->text('blurb')->nullable();

            $table->text('facebook')->nullable();
            $table->text('instagram')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
