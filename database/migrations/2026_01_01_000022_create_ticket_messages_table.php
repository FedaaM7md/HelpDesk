<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_type', 20)->default('requester');
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->longText('body');
            $table->boolean('is_internal')->default(false);
            $table->string('message_id')->nullable()->unique();
            $table->string('in_reply_to')->nullable()->index();
            $table->string('source', 20)->default('web');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index(['ticket_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
