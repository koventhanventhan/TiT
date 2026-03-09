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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('type')->default('event'); // event, meeting, task, reminder, deadline
            $table->string('priority')->default('low'); // low, medium, high
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->string('duration')->nullable(); // e.g., "1 hour", "30 minutes"
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('attendees')->nullable(); // comma-separated or JSON
            $table->json('reminders')->nullable(); // array of reminder strings
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
