<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('ready')->default(false);
            $table->boolean('is_admit')->default(true);
            $table->timestamps();
        });

        Schema::create('school_licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->unique()->constrained('schools')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('package_tier', ['core', 'professional', 'complete'])->default('complete');
            $table->unsignedInteger('max_active_students')->nullable();
            $table->date('licence_start')->nullable();
            $table->date('licence_end')->nullable();
            $table->date('support_until')->nullable();
            $table->text('notes')->nullable();
            $table->string('external_ref')->nullable();
            $table->text('licence_key')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_licences');
        Schema::dropIfExists('schools');
    }
};
