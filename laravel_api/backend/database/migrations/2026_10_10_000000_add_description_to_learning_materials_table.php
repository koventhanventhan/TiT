<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('learning_materials', 'description')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('learning_materials', 'description')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
