<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            
            // Sender context (e.g. sent from HOD on behalf of department or faculty)
            $table->string('sender_entity_type')->default('user'); // 'user', 'department', 'faculty'
            $table->unsignedBigInteger('sender_entity_id')->nullable();
            
            // Recipient context
            $table->string('recipient_type'); // 'user', 'department', 'faculty', 'role'
            $table->unsignedBigInteger('recipient_entity_id')->nullable();
            $table->foreignId('recipient_role_id')->nullable()->constrained('user_roles')->nullOnDelete();
            
            $table->string('confidentiality_level')->default('internal'); // 'public', 'internal', 'confidential'
            $table->string('status')->default('draft'); // 'draft', 'pending_signature', 'sent', 'archived'
            
            // Signature routing
            $table->foreignId('signing_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        Schema::create('memo_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained('memos')->cascadeOnDelete();
            
            $table->string('from_entity_type')->nullable();
            $table->unsignedBigInteger('from_entity_id')->nullable();
            
            $table->string('to_entity_type')->nullable();
            $table->unsignedBigInteger('to_entity_id')->nullable();
            
            $table->foreignId('forwarded_by')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // 'sent', 'forwarded', 'returned', 'signed', 'acknowledged', 'approved'
            $table->text('remarks')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('memo_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained('memos')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        // Standard Laravel database notifications table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('memo_attachments');
        Schema::dropIfExists('memo_tracking');
        Schema::dropIfExists('memos');
    }
};
