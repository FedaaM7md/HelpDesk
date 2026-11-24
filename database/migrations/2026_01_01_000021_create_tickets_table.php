<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('subject');
            $table->text('description');
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('normal');
            $table->string('channel', 20)->default('portal');
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_locale', 5)->default('ar');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('first_response_breached_at')->nullable();
            $table->timestamp('resolution_breached_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index('requester_email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
