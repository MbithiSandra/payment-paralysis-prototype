<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('client_code', 20);
            $table->string('invoice_number', 50);
            $table->decimal('amount', 14, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->timestamps();

            $table->foreign('client_code')->references('client_code')->on('clients')->cascadeOnDelete();
            $table->unique(['client_code', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};