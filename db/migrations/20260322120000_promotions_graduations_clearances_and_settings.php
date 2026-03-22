<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PromotionsGraduationsClearancesAndSettings extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('promotions')) {
            $t = $this->table('promotions', ['id' => false, 'primary_key' => ['id']]);
            $t->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
                ->addColumn('student_id', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('from_level', 'integer', ['null' => false])
                ->addColumn('to_level', 'integer', ['null' => false])
                ->addColumn('academic_session_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('promoted_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('promotion_date', 'date', ['null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['student_id', 'academic_session_id', 'from_level', 'to_level'], ['unique' => true, 'name' => 'uniq_promotion_session_transition'])
                ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('academic_session_id', 'academic_sessions', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('promoted_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('graduations')) {
            $g = $this->table('graduations', ['id' => false, 'primary_key' => ['id']]);
            $g->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
                ->addColumn('student_id', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('graduation_date', 'date', ['null' => false])
                ->addColumn('academic_session_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('graduated_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('status', 'string', ['limit' => 32, 'default' => 'graduated'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['student_id'])
                ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('academic_session_id', 'academic_sessions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('graduated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('student_clearances')) {
            $c = $this->table('student_clearances', ['id' => false, 'primary_key' => ['id']]);
            $c->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
                ->addColumn('student_id', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('department_key', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('status', 'enum', [
                    'values' => ['pending', 'cleared', 'not_required'],
                    'default' => 'pending',
                    'null' => false,
                ])
                ->addColumn('cleared_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('cleared_at', 'datetime', ['null' => true])
                ->addColumn('notes', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['student_id', 'department_key'], ['unique' => true, 'name' => 'uniq_student_department_clearance'])
                ->addIndex(['department_key'])
                ->addForeignKey('student_id', 'students', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('cleared_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if ($this->hasTable('settings')) {
            $exists = $this->fetchRow(
                "SELECT id FROM settings WHERE setting_key = 'students.promotion_mode' LIMIT 1"
            );
            if ($exists === false || $exists === null) {
                $this->table('settings')->insert([
                    [
                        'category' => 'students',
                        'setting_key' => 'students.promotion_mode',
                        'setting_value' => 'auto',
                        'data_type' => 'string',
                        'description' => 'Student promotion: auto (cron) or manual (admin bulk)',
                        'updated_by' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                ])->save();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('settings')) {
            $this->execute("DELETE FROM settings WHERE setting_key = 'students.promotion_mode'");
        }
        if ($this->hasTable('student_clearances')) {
            $this->table('student_clearances')->drop()->save();
        }
        if ($this->hasTable('graduations')) {
            $this->table('graduations')->drop()->save();
        }
        if ($this->hasTable('promotions')) {
            $this->table('promotions')->drop()->save();
        }
    }
}
