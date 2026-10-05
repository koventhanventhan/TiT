<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // e.g. "1st Term", "2nd Term", "Mid-Term", "Final"
            $table->foreignId('institute_id')->nullable()->constrained('institutes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['name', 'institute_id']);
        });

        // Seed default terms
        DB::table('exam_terms')->insert([
            ['name' => '1st Term', 'institute_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '2nd Term', 'institute_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '3rd Term', 'institute_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_terms');
    }
};
