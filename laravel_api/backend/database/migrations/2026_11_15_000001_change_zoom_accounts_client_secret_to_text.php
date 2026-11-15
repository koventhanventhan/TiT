<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('zoom_accounts')) {
            Schema::table('zoom_accounts', function (Blueprint $table) {
                $table->text('client_secret')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safety: Do not shrink back to VARCHAR(255) if any rows contain encrypted
        // strings longer than 255 characters. Doing so in MariaDB strict mode throws
        // SQLSTATE[22001] (1406 Data too long) or causes silent truncation and unrecoverable
        // ciphertext corruption.
        if (Schema::hasTable('zoom_accounts')) {
            $hasLongRows = DB::table('zoom_accounts')
                ->whereRaw('LENGTH(client_secret) > 255')
                ->exists();

            if ($hasLongRows) {
                return;
            }

            Schema::table('zoom_accounts', function (Blueprint $table) {
                $table->string('client_secret', 255)->nullable(false)->change();
            });
        }
    }
};
