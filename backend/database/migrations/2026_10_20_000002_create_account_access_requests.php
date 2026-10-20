<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_access_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->string('email')->index();
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->text('response_message')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('setup_token_hash', 64)->nullable()->unique();
            $table->timestamp('setup_token_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_access_requests');
    }
};
