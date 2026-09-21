<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->string('client_code', 20)->primary();
            $table->string('kra_pin', 20);
            $table->string('client_name');
            $table->string('client_kra_pin', 20)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('sector')->nullable();
            $table->date('relationship_start_date')->nullable();
            $table->timestamps();

            $table->foreign('kra_pin')->references('kra_pin')->on('smes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};