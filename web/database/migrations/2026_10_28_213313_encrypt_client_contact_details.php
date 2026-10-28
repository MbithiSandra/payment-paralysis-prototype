<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['client_kra_pin', 'contact_person', 'phone', 'email'];

    public function up(): void
    {
        // encrypted text is long, so the columns must hold more than 20 characters
        Schema::table('clients', function (Blueprint $table) {
            foreach (self::FIELDS as $field) {
                $table->text($field)->nullable()->change();
            }
        });

        // encrypt the details already saved
        foreach (DB::table('clients')->get() as $client) {
            $changes = [];
            foreach (self::FIELDS as $field) {
                $value = $client->$field;
                if ($value === null || $value === '') {
                    continue;
                }
                try {
                    Crypt::decryptString($value);            // already encrypted, leave it
                } catch (\Throwable $e) {
                    $changes[$field] = Crypt::encryptString($value);
                }
            }
            if ($changes) {
                DB::table('clients')->where('client_code', $client->client_code)->update($changes);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('clients')->get() as $client) {
            $changes = [];
            foreach (self::FIELDS as $field) {
                $value = $client->$field;
                if ($value === null || $value === '') {
                    continue;
                }
                try {
                    $changes[$field] = Crypt::decryptString($value);
                } catch (\Throwable $e) {
                    // not encrypted, leave it
                }
            }
            if ($changes) {
                DB::table('clients')->where('client_code', $client->client_code)->update($changes);
            }
        }
    }
};