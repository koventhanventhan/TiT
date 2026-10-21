<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assistant_visitor_logs')) {
            Schema::create('assistant_visitor_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->nullable()->index();
                $table->string('visitor_hash', 64)->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('status', 32)->index();
                $table->string('language', 10)->default('en');
                $table->text('question_redacted')->nullable();
                $table->integer('prompt_tokens')->default(0);
                $table->integer('completion_tokens')->default(0);
                $table->integer('total_tokens')->default(0);
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
        Schema::dropIfExists('assistant_visitor_logs');
    }
};
