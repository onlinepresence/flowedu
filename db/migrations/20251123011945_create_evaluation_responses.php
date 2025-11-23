<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEvaluationResponses extends AbstractMigration
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
        $table = $this->table('evaluation_responses', ['id' => false, 'primary_key' => ['id']]);
        
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('form_id', 'biginteger', ['signed' => false])
              ->addColumn('student_id', 'biginteger', ['signed' => false])
              ->addColumn('teacher_id', 'biginteger', ['signed' => false, 'comment' => 'The teacher being evaluated.'])
              ->addColumn('student_department_id', 'biginteger', ['signed' => false, 'comment' => 'Department at time of submission (Denormalization for stats).'])
              ->addColumn('response_code', 'string', ['limit' => 50, 'comment' => 'System-generated unique identifier for this response.'])
              ->addIndex(['response_code'], ['unique' => true])
              
              // Status and Timestamps
              ->addColumn('status', 'enum', ['values' => ['draft', 'submitted'], 'default' => 'draft', 'comment' => 'Allows saving incomplete work.'])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'comment' => 'Time the response was first created/started.'])
              ->addColumn('submitted_at', 'datetime', ['null' => true, 'comment' => 'Time the student clicked final submit.'])
              
              // Constraints
              ->addIndex(['form_id', 'student_id'], ['unique' => true, 'name' => 'idx_unique_student_response']) // Ensures a student only evaluates a form once
              
              // Foreign Keys
              // Assuming 'users' table holds students and teachers
              ->addForeignKey('form_id', 'evaluation_forms', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('student_id', 'users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE']) 
              ->addForeignKey('teacher_id', 'users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
              ->addForeignKey('student_department_id', 'departments', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
              ->create();
    }
}
