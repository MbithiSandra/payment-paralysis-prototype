<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('kra_pin', 20);
            $table->date('month');
            $table->decimal('revenue', 14, 2);
            $table->decimal('expenses', 14, 2);
            $table->timestamps();

            $table->foreign('kra_pin')->references('kra_pin')->on('smes')->cascadeOnDelete();
            $table->unique(['kra_pin', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};