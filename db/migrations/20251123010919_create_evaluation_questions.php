<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEvaluationQuestions extends AbstractMigration
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
        $table = $this->table('evaluation_questions', ['id' => false, 'primary_key' => ['id']]);
        
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('form_id', 'biginteger', ['signed' => false])
              ->addColumn('question_text', 'text')
              // The question_order is necessary for deterministic display and navigation (1st question, 2nd question, etc.)
              ->addColumn('question_order', 'integer', ['comment' => 'Used for deterministic sequencing of questions on the form.'])
              ->addColumn('rating_type', 'enum', [
                  'values' => ['scale_5', 'scale_10', 'text_short', 'text_long', 'boolean'], 
                  'default' => 'scale_5'
              ])
              ->addColumn('is_required', 'boolean', ['default' => 1])
              
              // Auditing and Soft Delete
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('deleted_at', 'datetime', ['null' => true, 'comment' => 'Soft delete timestamp.'])
              ->addColumn('deleted_by', 'biginteger', ['signed' => false, 'null' => true])
              
              // Foreign Key
              ->addForeignKey('form_id', 'evaluation_forms', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('deleted_by', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE'])
              ->create();
              
        // Track who last edited the form (last_edited_by) on evaluation_forms 
        // is generally sufficient, as questions are part of the form's content.
    }
}
