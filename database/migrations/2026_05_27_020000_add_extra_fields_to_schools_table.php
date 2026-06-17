<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('motto')->nullable();
            $table->unsignedInteger('established_year')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'motto',
                'established_year',
                'principal_name',
                'facebook_url',
                'twitter_url',
                'linkedin_url',
                'instagram_url',
            ]);
        });
    }
};
