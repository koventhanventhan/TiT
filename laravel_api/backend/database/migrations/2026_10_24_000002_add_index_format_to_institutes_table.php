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
        if (Schema::hasTable('institutes')) {
            Schema::table('institutes', function (Blueprint $table) {
                if (!Schema::hasColumn('institutes', 'index_format')) {
                    $table->string('index_format', 20)->nullable()->after('index_seq_start');
                }
            });

            // Update institutes with no issued index numbers to default TIT prefix, seq start 1, format grade
            $institutes = DB::table('institutes')->get();
            foreach ($institutes as $inst) {
                $hasIssuedIndex = DB::table('users')
                    ->where('institute_id', $inst->id)
                    ->whereNotNull('index_number')
                    ->exists();

                if (!$hasIssuedIndex) {
                    DB::table('institutes')
                        ->where('id', $inst->id)
                        ->update([
                            'index_format' => 'grade',
                            'index_prefix' => 'TIT',
                            'index_seq_start' => 1,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('institutes') && Schema::hasColumn('institutes', 'index_format')) {
            Schema::table('institutes', function (Blueprint $table) {
                $table->dropColumn('index_format');
            });
        }
    }
};
