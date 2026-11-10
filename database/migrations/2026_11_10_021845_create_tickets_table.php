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

            // Ticket reference
            $table->string('reference')->unique();

            // Customer information
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');

            // Ticket status
            $table->string('status')->default('new');

            // AI classification
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->string('sentiment')->nullable();
            $table->string('summary', 200)->nullable();
            $table->string('language', 10)->nullable();

            // Classification source
            $table->string('category_source')->nullable();
            $table->string('priority_source')->nullable();

            // AI processing status
            $table->string('ai_status')->default('pending');

            // Agent reply
            $table->longText('reply')->nullable();

            $table->timestamps();

            // Dashboard filtering indexes
            $table->index(['category', 'priority', 'status']);
            $table->index('ai_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};