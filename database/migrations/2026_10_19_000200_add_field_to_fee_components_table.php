<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_components', function (Blueprint $table) {
            $table->string('field')->nullable()->unique();
        });

        $raw = DB::table('settings')
            ->where('setting_key', 'finance.extra_fee_keys')
            ->value('setting_value');

        $selected = is_string($raw) ? json_decode($raw, true) : null;
        $selectedFields = is_array($selected) && $selected !== [] ? $selected : null;
        $now = now();

        $components = [
            ['name' => 'Tuition Fee', 'field' => 'tuition_fee', 'default_percentage' => 70.00],
            ['name' => 'Library Fee', 'field' => 'library_fee', 'default_percentage' => 5.00],
            ['name' => 'Laboratory Fee', 'field' => 'lab_fee', 'default_percentage' => 8.00],
            ['name' => 'Medical Fee', 'field' => 'medical_fee', 'default_percentage' => 5.00],
            ['name' => 'Sports Fee', 'field' => 'sports_fee', 'default_percentage' => 4.00],
            ['name' => 'Examination Fee', 'field' => 'examination_fee', 'default_percentage' => 8.00],
            ['name' => 'Registration Fee', 'field' => 'registration_fee', 'default_percentage' => 2.00],
            ['name' => 'ICT Fee', 'field' => 'ict_fee', 'default_percentage' => 2.00],
            ['name' => 'ID Card Fee', 'field' => 'id_card_fee', 'default_percentage' => 0.50],
            ['name' => 'Facility Maintenance Fee', 'field' => 'facility_maintenance_fee', 'default_percentage' => 0.50],
            ['name' => 'Utility Fee', 'field' => 'utility_fee', 'default_percentage' => 0.50],
            ['name' => 'Field Trip / Practicum Fee', 'field' => 'field_trip_fee', 'default_percentage' => 0.50],
            ['name' => 'Internship / Attachment Fee', 'field' => 'internship_fee', 'default_percentage' => 0.50],
            ['name' => 'SRC / Student Union Dues', 'field' => 'src_dues', 'default_percentage' => 0.50],
        ];

        foreach ($components as $component) {
            $isActive = $component['field'] === 'tuition_fee'
                || $selectedFields === null
                || in_array($component['field'], $selectedFields, true);

            $existing = DB::table('fee_components')
                ->where('name', $component['name'])
                ->first();

            if ($existing !== null) {
                DB::table('fee_components')
                    ->where('id', $existing->id)
                    ->update([
                        'field' => $component['field'],
                        'default_percentage' => $component['default_percentage'],
                        'is_active' => $isActive,
                        'is_system' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('fee_components')->insert([
                'name' => $component['name'],
                'field' => $component['field'],
                'default_percentage' => $component['default_percentage'],
                'is_active' => $isActive,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('fee_components', function (Blueprint $table) {
            $table->dropUnique(['field']);
            $table->dropColumn('field');
        });
    }
};
