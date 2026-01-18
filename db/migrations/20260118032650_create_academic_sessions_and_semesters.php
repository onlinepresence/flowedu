<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAcademicSessionsAndSemesters extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Create academic_sessions table if it doesn't exist
        if (!$this->hasTable('academic_sessions')) {
            $sessions = $this->table('academic_sessions');
            // Using default Phinx ID (usually signed integer)
            $sessions->addColumn('name', 'string', ['limit' => 20])
                  ->addColumn('start_date', 'date')
                  ->addColumn('end_date', 'date')
                  ->addColumn('is_current', 'boolean', ['default' => false])
                  ->addTimestamps()
                  ->create();
        }

        // Create semesters table
        if (!$this->hasTable('semesters')) {
            $semesters = $this->table('semesters');
            // Match the default ID type of academic_sessions (signed integer)
            $semesters->addColumn('academic_session_id', 'integer') 
                  ->addColumn('name', 'string', ['limit' => 50])
                  ->addColumn('start_date', 'date')
                  ->addColumn('end_date', 'date')
                  ->addColumn('is_active', 'boolean', ['default' => false])
                  ->addTimestamps()
                  ->addForeignKey('academic_session_id', 'academic_sessions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->create();
        }
    }
}
