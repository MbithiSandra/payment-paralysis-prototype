<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('client_code', 20);

            // what the system said
            $table->string('recommended_tier', 10);
            $table->decimal('probability_late', 6, 4);
            $table->string('model_used', 20);
            $table->text('recommended_action');

            // what the owner actually did
            $table->string('decision', 30);              // approve, approve_with_conditions, decline
            $table->text('override_reason')->nullable();
            $table->boolean('followed_recommendation');

            $table->timestamps();
            $table->foreign('client_code')->references('client_code')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_decisions');
    }
};