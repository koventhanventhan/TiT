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
        Schema::create('zoom_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('zoom_link');
            $table->dateTime('scheduled_at');
            $table->string('subject')->nullable();
            $table->string('grade')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('scheduled_at');
        });

        Schema::create('zoom_schedule_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['zoom_schedule_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_schedule_teacher');
        Schema::dropIfExists('zoom_schedules');
    }
};
