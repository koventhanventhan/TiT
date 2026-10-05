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
        Schema::create('zoom_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('account_id')->nullable();
            $table->string('client_id');
            $table->string('client_secret');
            $table->integer('max_concurrent')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_accounts');
    }
};
