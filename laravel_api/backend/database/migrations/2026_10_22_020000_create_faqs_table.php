<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->nullable()->index();
                $table->text('question');
                $table->text('answer');
                $table->string('language', 10)->default('en')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();

                $table->foreign('institute_id')
                    ->references('id')
                    ->on('institutes')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
