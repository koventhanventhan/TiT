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
        if (!Schema::hasTable('institute_integrations')) {
            Schema::create('institute_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
                $table->string('provider', 32); // payhere | whatsapp | mail
                $table->text('credentials')->nullable(); // encrypted:array cast via APP_KEY
                $table->json('settings')->nullable(); // non-secret provider configuration
                $table->string('status', 32)->default('unconfigured'); // unconfigured | configured | verified | failing
                $table->timestamp('last_verified_at')->nullable();
                $table->text('last_error')->nullable(); // sanitised error summary, never secrets
                $table->timestamps();

                $table->unique(['institute_id', 'provider']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_integrations');
    }
};
