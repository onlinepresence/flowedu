<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table): void {
            $table->decimal('registration_fee', 12, 2)->default(0)->after('examination_fee');
            $table->decimal('ict_fee', 12, 2)->default(0)->after('registration_fee');
            $table->decimal('id_card_fee', 12, 2)->default(0)->after('ict_fee');
            $table->decimal('facility_maintenance_fee', 12, 2)->default(0)->after('id_card_fee');
            $table->decimal('utility_fee', 12, 2)->default(0)->after('facility_maintenance_fee');
            $table->decimal('field_trip_fee', 12, 2)->default(0)->after('utility_fee');
            $table->decimal('internship_fee', 12, 2)->default(0)->after('field_trip_fee');
            $table->decimal('src_dues', 12, 2)->default(0)->after('internship_fee');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table): void {
            $table->dropColumn([
                'registration_fee',
                'ict_fee',
                'id_card_fee',
                'facility_maintenance_fee',
                'utility_fee',
                'field_trip_fee',
                'internship_fee',
                'src_dues',
            ]);
        });
    }
};
