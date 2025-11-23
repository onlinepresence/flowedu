<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEvaluationForms extends AbstractMigration
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
        $table = $this->table('evaluation_forms', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        
        $table->addColumn('id', 'biginteger', ['identity' => true,'signed' => false])
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('academic_year', 'string', ['limit' => 9, 'comment' => 'e.g., 2025/2026'])
              ->addColumn('unique_code', 'string', ['limit' => 50, 'comment' => 'System-generated unique identifier for the form'])
              ->addIndex(['unique_code'], ['unique' => true])
              
              // Scheduling and Control
              ->addColumn('start_time', 'datetime')
              ->addColumn('end_time', 'datetime')
              ->addColumn('control_type', 'enum', ['values' => ['auto', 'manual'], 'default' => 'auto', 'comment' => 'Determines system/cron control over start/stop.'])
              ->addColumn('is_active', 'boolean', ['default' => 0, 'comment' => '1=Currently open, 0=Closed or pending.'])
              
              // Auditing
              ->addColumn('created_by', 'biginteger', ['signed' => false, 'comment' => 'Admin ID who created the form.'])
              ->addColumn('last_edited_by', 'biginteger', ['signed' => false, 'null' => true, 'comment' => 'Admin ID who last updated the form.'])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              // Foreign Key
              ->addForeignKey('created_by', 'users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
              ->create();
    }
}
