<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Pro
            $table->string('slug')->unique(); // free, pro
            $table->string('stripe_price_id')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->string('billing_period')->default('monthly'); // monthly, yearly
            $table->json('features'); // Feature limits
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};