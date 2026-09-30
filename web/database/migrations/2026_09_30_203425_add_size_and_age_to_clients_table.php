<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->unsignedInteger('employees')->nullable();
        $table->decimal('years_in_operation', 4, 1)->nullable();
    });
}

public function down(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn(['employees', 'years_in_operation']);
    });
}
};
